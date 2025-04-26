<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class OrderActionsWidget extends BaseWidget
{
    protected ?string $heading = 'Pesanan';
    protected function getStats(): array
    {
        return [
            Stat::make('Buat Order', 'Buat Order Baru')
                ->description('Klik untuk membuat pesanan baru.')
                ->descriptionIcon('heroicon-o-shopping-cart')
                ->chart([7, 2, 10, 3, 15, 4, 170])
                ->color('success')
                ->extraAttributes([
                    'class' => 'cursor-pointer',
                    'wire:click' => 'redirectToCreateOrder',
                ]),
        ];
    }

    public function redirectToCreateOrder()
    {
        return redirect()->to(route('filament.admin.resources.orders.create'));
    }
}
