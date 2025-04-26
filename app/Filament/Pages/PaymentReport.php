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
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'paid' => 'Lunas',
                        'pending' => 'Tertunda',
                        'overdue' => 'Jatuh Tempo',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'paid' => 'success',
                        'pending' => 'warning',
                        'overdue' => 'danger',
                        default => 'gray',
                    }),
                // Tables\Columns\TextColumn::make('payment_due_date')
                //     ->label('Jatuh Tempo')
                //     ->date('d/m/Y')
                //     ->sortable()
                //     ->placeholder('-'),
                    // ->visible(fn ($livewire) => ! $livewire->activeTab || $livewire->activeTab === 'all' || $livewire->activeTab === 'tempo'),
                Tables\Columns\TextColumn::make('total_amount')
                    ->label('Total Pembayaran')
                    ->money('IDR')
                    ->sortable(),
            ])
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
                                fn (Builder $query, $date): Builder => $query->whereDate('order_date', '>=', $date),
                            )
                            ->when(
                                $data['order_date_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('order_date', '<=', $date),
                            );
                    }),
                // Filter::make('payment_due_date')
                //     ->form([
                //         Forms\Components\DatePicker::make('payment_due_date_from')
                //             ->label('Dari Tanggal'),
                //         Forms\Components\DatePicker::make('payment_due_date_until')
                //             ->label('Sampai Tanggal'),
                //     ])
                //     ->query(function (Builder $query, array $data): Builder {
                //         return $query
                //             ->when(
                //                 $data['payment_due_date_from'],
                //                 fn (Builder $query, $date): Builder => $query->whereDate('payment_due_date', '>=', $date),
                //             )
                //             ->when(
                //                 $data['payment_due_date_until'],
                //                 fn (Builder $query, $date): Builder => $query->whereDate('payment_due_date', '<=', $date),
                //             );
                //     }),
            ])
            ->actions([
                Tables\Actions\Action::make('markAsPaid')
                    ->label('Tandai Lunas')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (Order $record) => $record->payment_status !== 'paid')
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
                    Tables\Actions\BulkAction::make('markAsOverdue')
                        ->label('Tandai Jatuh Tempo')
                        ->icon('heroicon-o-exclamation-circle')
                        ->color('danger')
                        ->action(function (Collection $records) {
                            $records->each(function ($record) {
                                $record->payment_status = 'overdue';
                                $record->save();
                            });
                            
                            Notification::make()
                                ->title('Pembayaran berhasil diubah menjadi jatuh tempo!')
                                ->warning()
                                ->send();
                        }),
                ]),
            ]);
            // ->tabs([
            //     'all' => Tab::make('Semua')
            //         ->badge(fn () => Order::count()),
            //     'paid' => Tab::make('Lunas')
            //         ->badge(fn () => Order::where('payment_status', 'paid')->count())
            //         ->badgeColor('success')
            //         ->modifyQueryUsing(fn (Builder $query) => $query->where('payment_status', 'paid')),
            //     'pending' => Tab::make('Tertunda')
            //         ->badge(fn () => Order::where('payment_status', 'pending')->count())
            //         ->badgeColor('warning') 
            //         ->modifyQueryUsing(fn (Builder $query) => $query->where('payment_status', 'pending')),
            //     'overdue' => Tab::make('Jatuh Tempo')
            //         ->badge(fn () => Order::where('payment_status', 'overdue')->count())
            //         ->badgeColor('danger')
            //         ->modifyQueryUsing(fn (Builder $query) => $query->where('payment_status', 'overdue')),
            //     'tempo' => Tab::make('Tempo')
            //         ->badge(fn () => Order::where('payment_method', 'tempo')->count())
            //         ->badgeColor('warning')
            //         ->modifyQueryUsing(fn (Builder $query) => $query->where('payment_method', 'tempo')),
            // ])
            // ->defaultSort('order_date', 'desc');
    }

    // protected function getHeaderActions(): array
    // {
    //     return [
    //         Action::make('exportPdf')
    //             ->label('Export PDF')
    //             ->icon('heroicon-o-document-arrow-down')
    //             ->action(function () {
    //                 $orders = Order::query()
    //                     ->when($this->tableFilters, function ($query, $filters) {
    //                         foreach ($filters as $filter => $value) {
    //                             if ($filter === 'payment_method' && $value !== '') {
    //                                 $query->where('payment_method', $value);
    //                             }
    //                             if ($filter === 'payment_status' && $value !== '') {
    //                                 $query->where('payment_status', $value);
    //                             }
    //                             // Handle date filters
    //                             if ($filter === 'order_date') {
    //                                 if (!empty($value['order_date_from'])) {
    //                                     $query->whereDate('order_date', '>=', $value['order_date_from']);
    //                                 }
    //                                 if (!empty($value['order_date_until'])) {
    //                                     $query->whereDate('order_date', '<=', $value['order_date_until']);
    //                                 }
    //                             }
    //                             if ($filter === 'payment_due_date') {
    //                                 if (!empty($value['payment_due_date_from'])) {
    //                                     $query->whereDate('payment_due_date', '>=', $value['payment_due_date_from']);
    //                                 }
    //                                 if (!empty($value['payment_due_date_until'])) {
    //                                     $query->whereDate('payment_due_date', '<=', $value['payment_due_date_until']);
    //                                 }
    //                             }
    //                         }
    //                     })
    //                     ->when($this->activeTableTab !== null && $this->activeTableTab !== 'all', function ($query) {
    //                         if ($this->activeTableTab === 'paid') {
    //                             $query->where('payment_status', 'paid');
    //                         } elseif ($this->activeTableTab === 'pending') {
    //                             $query->where('payment_status', 'pending');
    //                         } elseif ($this->activeTableTab === 'overdue') {
    //                             $query->where('payment_status', 'overdue');
    //                         } elseif ($this->activeTableTab === 'tempo') {
    //                             $query->where('payment_method', 'tempo');
    //                         }
    //                     })
    //                     ->orderBy('order_date', 'desc')
    //                     ->get();

    //                 $pdf = Pdf::loadView('reports.payment-report', [
    //                     'orders' => $orders,
    //                     'date' => Carbon::now()->format('d/m/Y'),
    //                     'title' => 'Laporan Pembayaran',
    //                 ]);

    //                 return response()->streamDownload(function () use ($pdf) {
    //                     echo $pdf->output();
    //                 }, 'laporan-pembayaran-' . Carbon::now()->format('Y-m-d') . '.pdf');
    //             }),
    //         Action::make('exportExcel')
    //             ->label('Export Excel')
    //             ->icon('heroicon-o-table-cells')
    //             ->action(function () {
    //                 // Implement Excel export logic here
    //                 // You'll need to install and use a package like maatwebsite/excel
    //             }),
    //     ];
    // }

    // public function getWidgets(): array
    // {
    //     return [
    //         PaymentReportWidgets\PaymentOverview::class,
    //     ];
    // }

    // protected function getHeaderWidgets(): array
    // {
    //     return $this->getWidgets();
    // }
}