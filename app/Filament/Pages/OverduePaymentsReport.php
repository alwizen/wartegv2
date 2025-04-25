<?php

namespace App\Filament\Pages;

use App\Models\Order;
use Filament\Pages\Page;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Actions\Action;
use Carbon\Carbon;

class OverduePaymentsReport extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-exclamation-triangle';
    protected static ?string $navigationGroup = 'Laporan';
    protected static ?string $title = 'Laporan Pembayaran Tempo';
    protected static ?string $slug = 'reports/overdue-payments';
    protected static ?int $navigationSort = 2;

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Order::query()
                    ->where('payment_method', 'tempo')
                    ->where(function ($query) {
                        $query->where('payment_status', 'overdue')
                            ->orWhere(function ($query) {
                                $query->where('payment_status', 'pending')
                                    ->whereDate('payment_due_date', '<', now());
                            });
                    })
                    ->orderBy('payment_due_date')
            )
            ->columns([
                TextColumn::make('order_number')
                    ->label('No. Order')
                    ->searchable(),
                TextColumn::make('customer_name')
                    ->label('Pelanggan')
                    ->searchable(),
                TextColumn::make('total_amount')
                    ->label('Total')
                    ->money('IDR')
                    ->sortable(),
                TextColumn::make('payment_due_date')
                    ->label('Jatuh Tempo')
                    ->date()
                    ->sortable(),
                TextColumn::make('days_overdue')
                    ->label('Terlambat (Hari)')
                    ->getStateUsing(function (Order $record) {
                        return Carbon::parse($record->payment_due_date)->diffInDays(now(), false);
                    })
                    ->sortable(),
            ])
            ->actions([
                Action::make('mark_as_paid')
                    ->label('Tandai Sudah Dibayar')
                    ->action(function (Order $record) {
                        $record->update(['payment_status' => 'paid']);
                        $this->notify('success', 'Pembayaran berhasil diperbarui');
                    })
                    ->icon('heroicon-o-check-circle')
                    ->color('success'),
                //Action::make('view')
                //  ->url(fn(Order $record): string => route('filament.admin.resources.orders.view', $record))
                //->icon('heroicon-o-eye'),
            ]);
    }

    protected static string $view = 'filament.pages.overdue-payments-report';
}
