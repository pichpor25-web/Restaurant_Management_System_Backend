<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf; 
use App\Exports\SalesReportExport;
use Maatwebsite\Excel\Facades\Excel;
class ReportController extends Controller
{
    public function summary(Request $request)
    {
        $month = $request->query('month'); 
        
        $query = DB::table('orders')->where('status', '!=', 'cancelled');

        
        if ($month) {
            $query->whereYear('created_at', substr($month, 0, 4))
                  ->whereMonth('created_at', substr($month, 5, 2));
        }

        $totalRevenue = (clone $query)->sum('total');
        $totalOrders = (clone $query)->count();

        // គណនាតាមថ្ងៃ (Daily Reports)
        $dailyReports = (clone $query)
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('SUM(total) as total_sales'),
                DB::raw('COUNT(id) as orders')
            )
            ->groupBy('date')
            ->orderBy('date', 'desc')
            ->limit(31) 
            ->get();

        return response()->json([
            'totalRevenue' => $totalRevenue,
            'totalOrders' => $totalOrders,
            'dailyReports' => $dailyReports,
        ]);
    }

    
   // app/Http/Controllers/Api/ReportController.php

public function downloadPdf(Request $request)
{
    $month = $request->query('month');
    
    $query = DB::table('orders')->where('status', '!=', 'cancelled');
    if ($month) {
        $query->whereYear('created_at', substr($month, 0, 4))
              ->whereMonth('created_at', substr($month, 5, 2));
    }

    $totalRevenue = (clone $query)->sum('total');
    $totalOrders = (clone $query)->count();
    $dailyReports = (clone $query)
        ->select(
            DB::raw('DATE(created_at) as date'),
            DB::raw('SUM(total) as total_sales'), // ប្តូរត្រង់នេះទៅជា total_sales
            DB::raw('COUNT(id) as orders')
        )
        ->groupBy('date')
        ->orderBy('date', 'desc')
        ->get();

    $pdf = Pdf::loadView('reports.pdf', compact('totalRevenue', 'totalOrders', 'dailyReports', 'month'));

    return $pdf->download('sales-report-' . ($month ?? 'all') . '.pdf');
}
public function downloadExcel(Request $request)
{
    $month = $request->query('month');
    return Excel::download(new SalesReportExport($month), 'sales-report-' . ($month ?? 'all') . '.xlsx');
}
}