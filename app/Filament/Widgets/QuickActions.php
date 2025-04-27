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
                ->label('Tambah Menu Baru')
                ->url(route('filament.admin.resources.products.create'))
                ->icon('heroicon-o-bookmark')
                ->size('lg')
                ->color('primary'),

            Action::make('payment-report')
                ->label('Laporan Pembayaran')
                ->url(route('filament.admin.pages.payment-report'))
                ->icon('heroicon-o-chart-bar')
                ->size('lg')
                ->color('warning'),

        ];
    }
}
