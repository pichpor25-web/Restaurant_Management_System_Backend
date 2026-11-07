<!DOCTYPE html>
<html>
<head>
    <title>Sales Report</title>
    <style>
        body { font-family: sans-serif; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .header { text-align: center; margin-bottom: 20px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Restaurant Sales Report</h1>
        <p>Month: {{ $month ?? 'All Time' }}</p>
    </div>
    <p><strong>Total Revenue:</strong> ${{ number_format($totalRevenue, 2) }}</p>
    <p><strong>Total Orders:</strong> {{ $totalOrders }}</p>

    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Total Sales</th>
                <th>Orders</th>
            </tr>
        </thead>
        <tbody>
            @foreach($dailyReports as $report)
            <tr>
                <td>{{ $report->date }}</td>
                <td>${{ number_format($report->total_sales, 2) }}</td>
                <td>{{ $report->orders }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>