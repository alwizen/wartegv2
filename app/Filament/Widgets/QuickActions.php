<?php

namespace App\Filament\Widgets;


use Filament\Actions\Action;
use Filament\Widgets\Widget;

class QuickActions extends Widget
{
    protected static string $view = 'filament.widgets.quick-actions';

    protected int|string|array $columnSpan = 'full';

    public function getActions(): array
    {
        return [
            Action::make('pos-orders')
                ->label('Buat Pesanan Baru (Tab Mode)')
                ->url(route('filament.admin.pages.pos-orders'))
                ->icon('heroicon-o-device-phone-mobile')
                ->size('lg')
                ->color('info'),

            Action::make('createOrder')
                ->label('Buat Pesanan Baru')
                ->url(route('filament.admin.resources.orders.create'))
                ->icon('heroicon-o-inbox-arrow-down')
                ->size('lg')
                ->color('success'),

            Action::make('viewOrder')
                ->label('Daftar Pesanan')
                ->url(route('filament.admin.resources.orders.index'))
                ->icon('heroicon-o-clipboard-document-list')
                ->size('lg')
                ->color('danger'),

            Action::make('createProduct') 
                ->label('Daftar Menu')
                ->url(route('filament.admin.resources.products.index'))
                ->icon('heroicon-o-list-bullet')
                ->size('lg')
                ->color('primary'),

            Action::make('payment-report')
                ->label('Laporan Pembayaran')
                ->url(route('filament.admin.pages.payment-report'))
                ->icon('heroicon-o-credit-card')
                ->size('lg')
                ->color('warning'),

                Action::make('sales-report')
                ->label('Laporan Penjualan')
                ->url(route('filament.admin.pages.sales-report'))
                ->icon('heroicon-o-chart-bar')
                ->size('lg')
                ->color('gray'),

        ];
    }
}
