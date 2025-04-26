<?php

namespace App\Filament\Widgets;


use Filament\Actions\Action;
use Filament\Widgets\Widget;

class QuickActions extends Widget
{
    protected static string $view = 'filament.widgets.quick-actions';

    protected int | string | array $columnSpan = 'full';

    public function createOrder(): Action
    {
        return Action::make('createOrder')
            ->label('Buat Pesanan Baru')
            ->url(route('filament.admin.resources.orders.create'))
            ->icon('heroicon-o-plus')
            ->size('lg')
            ->color('success');
    }

    public function viewOrder(): Action
    {
        return Action::make('viewOrder')
            ->label('Order')
            ->url(route('filament.admin.resources.orders.index'))
            ->icon('heroicon-o-clipboard-document-list')
            ->size('lg')
            ->color('purple');
    }

    public function creatProduct(): Action
    {
        return Action::make('creatProduct')
            ->label('Menu Baru')
            ->url(route('filament.admin.resources.products.create'))
            ->icon('heroicon-o-bookmark')
            ->size('lg')
            ->color('primary');
    }
}
