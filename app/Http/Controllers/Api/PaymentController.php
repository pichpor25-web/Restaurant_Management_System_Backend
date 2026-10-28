<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\TelegramService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;
use Exception;

// Official Bakong KHQR Package Classes (from your screenshot)
use KHQR\BakongKHQR;
use KHQR\Helpers\KHQRData;
use KHQR\Models\IndividualInfo;

class PaymentController extends Controller
{
    /**
     * 1. Generate Bakong KHQR for an Order (Matching your checkout function)
     * POST /api/v1/payments/bakong/checkout/{orderId}
     */
    public function index()
{
    return response()->json(DB::table('payments')->orderByDesc('id')->paginate(50));
}
    public function checkout(int $orderId): JsonResponse
    {
        // 1. Fetch Order
        $order = DB::table('orders')->where('id', $orderId)->first();

        if (!$order) {
            return response()->json(['message' => 'Order not found'], 404);
        }

        if ($order->status === 'completed') {
            return response()->json(['message' => 'Order is already paid'], 400);
        }

        if (!is_numeric($order->total) || (float) $order->total <= 0) {
            return response()->json(['message' => 'Order total must be greater than zero'], 422);
        }

        $pendingPayment = DB::table('payments')
            ->where('order_id', $order->id)
            ->where('status', 'pending')
            ->latest('id')
            ->first();

        if ($pendingPayment) {
            // Replace pending payloads so refreshed checkouts use the current merchant settings and QR format.
            DB::table('payments')
                ->where('id', $pendingPayment->id)
                ->update([
                    'status' => 'expired',
                    'updated_at' => Carbon::now(),
                ]);
        }

        // 2. Prepare Merchant Information
        $merchant = new IndividualInfo(
            bakongAccountID: env('BAKONG_ACCOUNT_ID', 'bora_pen@bkrt'),
            merchantName: env('BAKONG_MERCHANT_NAME', 'Bora Pen'),
            merchantCity: env('BAKONG_MERCHANT_CITY', 'Phnom Penh'),
            currency: KHQRData::CURRENCY_USD,
            amount: (float) $order->total,
            expirationTimestamp: (string) ((int) round(microtime(true) * 1000) + 15 * 60 * 1000),
        );

        // 3. Generate QR and MD5 Hash
        $qrResponse = BakongKHQR::generateIndividual($merchant);

        $qrString = $qrResponse->data['qr'] ?? null;
        $md5Hash  = $qrResponse->data['md5'] ?? null;

        if (!$qrString || !$md5Hash || !BakongKHQR::verify($qrString)->isValid) {
            return response()->json(['message' => 'Failed to generate a valid Bakong KHQR'], 500);
        }

        $md5Hash = strtolower($md5Hash);

        // 4. Save pending payment record with MD5 hash stored in transaction_reference
        DB::table('payments')->insert([
            'order_id' => $order->id,
            'amount' => $order->total,
            'method' => 'qr',
            'status' => 'pending',
            'transaction_reference' => $md5Hash,
            'qr_payload' => $qrString,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        // 5. Return JSON to React POS UI
        return response()->json([
            'order_id'     => $order->id,
            'order_number' => $order->order_number,
            'amount'       => $order->total,
            'qr'           => $qrString,
            'md5'          => $md5Hash,
        ]);
    }

    /**
     * 2. Verify Transaction with NBC Bakong API (Matching your verifyTransaction function)
     * POST /api/v1/payments/bakong/verify
     */
    public function verifyTransaction(Request $request, TelegramService $telegram): JsonResponse
    {
        $validated = $request->validate([
            'md5' => ['required', 'string', 'regex:/^[a-fA-F0-9]{32}$/'],
        ]);

        $md5 = strtolower($validated['md5']);

        $payment = DB::table('payments')->where('transaction_reference', $md5)->first();
        if (!$payment) {
            return response()->json(['message' => 'Payment not found'], 404);
        }

        if ($payment->status === 'paid') {
            return response()->json(['paid' => true, 'message' => 'Payment was already confirmed']);
        }

        try {
            $token = env('BAKONG_TOKEN');

            if (!$token) {
                return response()->json(['error' => 'BAKONG_TOKEN is not configured in .env'], 500);
            }

            // Connect to Bakong SDK and check by MD5 Hash
            $bakong = new BakongKHQR($token);
            $result = $bakong->checkTransactionByMD5($md5);

            // Bakong transaction API responses are associative arrays with responseCode.
            if (isset($result['responseCode']) && (int) $result['responseCode'] === 0) {
                $now = Carbon::now();

                $orderId = DB::transaction(function () use ($md5, $now): ?int {
                    $lockedPayment = DB::table('payments')
                        ->where('transaction_reference', $md5)
                        ->lockForUpdate()
                        ->first();

                    if (!$lockedPayment || $lockedPayment->status === 'paid') {
                        return null;
                    }

                    DB::table('payments')->where('id', $lockedPayment->id)->update([
                        'status' => 'paid',
                        'paid_at' => $now,
                        'updated_at' => $now,
                    ]);

                    DB::table('orders')->where('id', $lockedPayment->order_id)->update([
                        'status' => 'completed',
                        'updated_at' => $now,
                    ]);

                    if (Schema::hasTable('dine_in_orders')) {
                        $dineIn = DB::table('dine_in_orders')->where('order_id', $lockedPayment->order_id)->first();
                        if ($dineIn && isset($dineIn->table_id)) {
                            DB::table('restaurant_tables')->where('id', $dineIn->table_id)->update([
                                'status' => 'available',
                                'updated_at' => $now,
                            ]);
                        }
                    }

                    return (int) $lockedPayment->order_id;
                });

                if ($orderId !== null) {
                    $order = DB::table('orders')->where('id', $orderId)->first();

                    if ($order) {
                        $orderNumber = e((string) $order->order_number);
                        $orderType = e(ucwords(str_replace('_', ' ', (string) $order->order_type)));

                        $telegram->sendMessage(
                            "<b>Payment received</b>\n\n"
                            . "<b>Order:</b> #{$orderNumber}\n"
                            . "<b>Type:</b> {$orderType}\n"
                            . "<b>Amount:</b> $" . number_format((float) $order->total, 2) . "\n"
                            . "<b>Paid at:</b> " . $now->format('d M Y, h:i A'),
                        );
                    }
                }

                return response()->json([
                    'paid'    => true,
                    'message' => 'Payment confirmed successfully',
                    'data'    => $result,
                ]);
            }

            // Not yet paid / pending in Bakong
            return response()->json([
                'paid'    => false,
                'message' => 'Payment pending or not found',
                'data'    => $result,
            ]);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
