<?php

namespace App\Filament\Pages;

use Filament\Forms;
use Filament\Tables;
use App\Models\Order;
use Filament\Pages\Page;
use Filament\Tables\Table;
use Filament\Actions\Action;
use Illuminate\Support\Carbon;
use Filament\Infolists\Infolist;
use Filament\Support\Colors\Color;
use Filament\Tables\Filters\Filter;
use Filament\Resources\Components\Tab;
use Filament\Notifications\Notification;
use Filament\Tables\Filters\SelectFilter;
// use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class PaymentReport extends Page implements Tables\Contracts\HasTable
{
    use Tables\Concerns\InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-document-chart-bar';

    protected static ?string $navigationGroup = 'Laporan';

    protected static ?string $title = 'Laporan Pembayaran';

    protected static ?string $navigationLabel = 'Laporan Pembayaran';

    protected static ?int $navigationSort = 1;

    protected static string $view = 'filament.pages.payment-report';

    public function table(Table $table): Table
    {
        return $table
            ->query(Order::query())
            ->columns([
                Tables\Columns\TextColumn::make('order_number')
                    ->label('Nomor Order')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('order_date')
                    ->label('Tanggal Order')
                    ->date('d/m/Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('customer_name')
                    ->label('Pelanggan')
                    ->searchable()
                    ->sortable(),
                // Tables\Columns\TextColumn::make('payment_method')
                //     ->label('Metode Pembayaran')
                //     ->badge()
                //     ->formatStateUsing(fn (string $state): string => match ($state) {
                //         'cash' => 'Tunai',
                //         'tempo' => 'Tempo',
                //         default => $state,
                //     })
                //     ->color(fn (string $state): string => match ($state) {
                //         'cash' => 'success',
                //         'tempo' => 'warning',
                //         default => 'gray',
                //     }),
                Tables\Columns\TextColumn::make('payment_status')
                    ->label('Status Pembayaran')
                    ->badge()
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'paid' => 'Lunas',
                        'pending' => 'Tertunda',
                        'overdue' => 'Jatuh Tempo',
                        default => $state,
                    })
                    ->color(fn(string $state): string => match ($state) {
                        'paid' => 'success',
                        'pending' => 'warning',
                        'overdue' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('total_amount')
                    ->label('Total Pembayaran')
                    ->money('IDR')
                    ->sortable(),
            ])->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('payment_status')
                    ->label('Status Pembayaran')
                    ->options([
                        'paid' => 'Lunas',
                        'pending' => 'Tertunda',
                    ]),
                Filter::make('order_date')
                    ->form([
                        Forms\Components\DatePicker::make('order_date_from')
                            ->label('Dari Tanggal'),
                        Forms\Components\DatePicker::make('order_date_until')
                            ->label('Sampai Tanggal'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['order_date_from'],
                                fn(Builder $query, $date): Builder => $query->whereDate('order_date', '>=', $date),
                            )
                            ->when(
                                $data['order_date_until'],
                                fn(Builder $query, $date): Builder => $query->whereDate('order_date', '<=', $date),
                            );
                    }),

            ])
            ->actions([
                Tables\Actions\Action::make('markAsPaid')
                    ->label('Tandai Lunas')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn(Order $record) => $record->payment_status !== 'paid')
                    ->action(function (Order $record) {
                        $record->payment_status = 'paid';
                        $record->save();

                        Notification::make()
                            ->title('Pembayaran berhasil diubah menjadi lunas!')
                            ->success()
                            ->send();
                    }),

                Tables\Actions\Action::make('print')
                    ->label('Cetak Nota')
                    ->icon('heroicon-o-printer')
                    ->action(function (Order $record) {
                        // Generate print view
                        return response()->streamDownload(function () use ($record) {
                            echo view('orders.print-receipt', [
                                'order' => $record,
                                'orderItems' => $record->orderItems,
                            ])->render();
                        }, $record->order_number . '.txt');
                    })
                    ->tooltip('Print struk pesanan'),
            ])

            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('markAsPaid')
                        ->label('Tandai Lunas')
                        ->icon('heroicon-o-check-circle')
                        ->action(function (Collection $records) {
                            $records->each(function ($record) {
                                $record->payment_status = 'paid';
                                $record->save();
                            });

                            Notification::make()
                                ->title('Pembayaran berhasil diubah menjadi lunas!')
                                ->success()
                                ->send();
                        }),
                    Tables\Actions\BulkAction::make('markAsPending')
                        ->label('Tandai Belum Lunas')
                        ->icon('heroicon-o-exclamation-circle')
                        ->color('danger')
                        ->action(function (Collection $records) {
                            $records->each(function ($record) {
                                $record->payment_status = 'pending';
                                $record->save();
                            });

                            Notification::make()
                                ->title('Pembayaran berhasil diubah menjadi belum lunas!')
                                ->warning()
                                ->send();
                        }),
                ]),
            ]);
    }
}
