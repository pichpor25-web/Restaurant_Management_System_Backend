<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function summary()
    {
        // ១. គណនាចំណូលសរុប (Total Revenue) និងចំនួន Order សរុប
        $totalRevenue = DB::table('orders')
            ->where('status', '!=', 'cancelled')
            ->sum('total');
            
        $totalOrders = DB::table('orders')
            ->where('status', '!=', 'cancelled')
            ->count();

        // ២. គណនាតាមថ្ងៃ (Daily Reports - ៧ ថ្ងៃចុងក្រោយ)
        $dailyReports = DB::table('orders')
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('SUM(total) as totalSales'),
                DB::raw('COUNT(id) as orders')
            )
            ->where('status', '!=', 'cancelled')
            ->groupBy('date')
            ->orderBy('date', 'desc')
            ->limit(7)
            ->get();

        return response()->json([
            'totalRevenue' => $totalRevenue,
            'totalOrders' => $totalOrders,
            'dailyReports' => $dailyReports,
        ]);
    }
}