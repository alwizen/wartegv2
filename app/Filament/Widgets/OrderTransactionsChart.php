<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

class OrderTransactionsChart extends ChartWidget
{
    protected static ?string $heading = 'Transaksi 7 hari terakhir';
    protected int | string | array $columnSpan = 'full';
    protected static ?int $sort = 2;
    protected function getData(): array
    {
        $data = $this->getTransactionsData();
        return [
            'datasets' => [
                [
                    'label' => 'Total Transaksi',
                    'data' => $data['totals'],
                    'backgroundColor' => '#36A2EB',
                    'borderColor' => '#36A2EB',
                ],
            ],
            'labels' => $data['labels'],
        ];
    }
    protected function getType(): string
    {
        return 'line';
    }
    private function getTransactionsData(): array
    {
        // Data transaksi 7 hari terakhir
        $orders = Order::where('created_at', '>=', now()->subDays(7))
            ->orderBy('created_at')
            ->get()
            ->groupBy(function ($order) {
                return Carbon::parse($order->created_at)->format('Y-m-d');
            });
        $labels = [];
        $totals = [];
        // Siapkan tanggal 7 hari terakhir
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $labels[] = now()->subDays($i)->format('d M');
            $total = $orders->get($date, collect())->sum('total_amount');
            $totals[] = $total;
        }
        return [
            'labels' => $labels,
            'totals' => $totals,
        ];
    }
}
