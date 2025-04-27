<?php
namespace App\Filament\Widgets;

use App\Models\Order;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class OrderStatsOverview extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        // Get the last 7 days
        $dates = collect();
        for ($i = 6; $i >= 0; $i--) {
            $dates->push(Carbon::today()->subDays($i)->format('Y-m-d'));
        }

        // Get data for each date in the last 7 days
        $totalOrders = $dates->map(function ($date) {
            return Order::whereDate('created_at', $date)->count();
        });

        $totalAmount = $dates->map(function ($date) {
            return Order::whereDate('created_at', $date)->sum('total_amount');
        });

        $pendingOrders = $dates->map(function ($date) {
            return Order::whereDate('created_at', $date)->where('payment_status', 'pending')->count();
        });

        $pendingAmount = $dates->map(function ($date) {
            return Order::whereDate('created_at', $date)->where('payment_status', 'pending')->sum('total_amount');
        });

        $paidOrders = $dates->map(function ($date) {
            return Order::whereDate('created_at', $date)->where('payment_status', 'paid')->count();
        });

        $paidAmount = $dates->map(function ($date) {
            return Order::whereDate('created_at', $date)->where('payment_status', 'paid')->sum('total_amount');
        });

        return [
            Stat::make('Total Pesanan', $totalOrders->sum())
                ->description('Total nominal: Rp ' . number_format($totalAmount->sum(), 0, ',', '.'))
                ->descriptionIcon('heroicon-m-clipboard-document-check')
                ->chart($totalOrders->toArray())
                ->color('primary'),

            Stat::make('Pesanan Lunas', $paidOrders->sum())
                ->description('Total nominal: Rp ' . number_format($paidAmount->sum(), 0, ',', '.'))
                ->descriptionIcon('heroicon-m-check-badge')
                ->chart($paidOrders->toArray())
                ->color('success'),

            Stat::make('Belum Lunas', $pendingOrders->sum())
                ->description('Total nominal: Rp ' . number_format($pendingAmount->sum(), 0, ',', '.'))
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->chart($pendingOrders->toArray())
                ->color('warning'),
        ];
    }
}
