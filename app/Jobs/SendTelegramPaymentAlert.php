<?php

namespace App\Jobs;

use App\Models\Order;
use App\Services\TelegramService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendTelegramPaymentAlert implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public Order $order) {}

    public function handle(TelegramService $telegram): void
    {
        $currencySymbol = $this->order->currency === 'KHR' ? '៛' : '$';
        $formattedAmount = number_format($this->order->total_amount, $this->order->currency === 'KHR' ? 0 : 2);

        $message = "<b>💰 New Payment Received (Bakong KHQR)</b>\n\n"
                 ."<b>Order ID:</b> #{$this->order->id}\n"
                 ."<b>Amount:</b> {$formattedAmount} {$currencySymbol}\n"
                 .'<b>Time:</b> '.now()->format('d M Y, h:i A');

        $telegram->sendMessage($message);
    }
}
