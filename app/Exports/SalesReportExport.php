<?php

namespace App\Exports;

use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Illuminate\Support\Enumerable; // នាំចូលវា

class SalesReportExport implements FromCollection, WithHeadings, WithMapping
{
    protected $month;

    public function __construct($month = null)
    {
        $this->month = $month;
    }

 
    public function collection(): Enumerable
    {
        $query = DB::table('orders')->where('status', '!=', 'cancelled');
        
        if ($this->month) {
            $query->whereYear('created_at', substr($this->month, 0, 4))
                  ->whereMonth('created_at', substr($this->month, 5, 2));
        }

        return $query->select(
            DB::raw('DATE(created_at) as date'),
            DB::raw('SUM(total) as total_sales'),
            DB::raw('COUNT(id) as orders')
        )
        ->groupBy('date')
        ->orderBy('date', 'desc')
        ->get();
    }

    public function headings(): array
    {
        return [
            'Date',
            'Total Sales ($)',
            'Orders',
        ];
    }

    public function map($report): array
    {
        return [
            $report->date,
            $report->total_sales,
            $report->orders,
        ];
    }
}