<?php

namespace App\Filament\Pages;

use App\Models\Order;
use Carbon\Carbon;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\BulkAction;
use Filament\Tables\Columns\Summarizers\Sum;
use Illuminate\Support\Facades\DB;
use Filament\Notifications\Notification;

class SalesReport extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-document-chart-bar';

    protected static ?string $navigationLabel = 'Laporan Penjualan';

    protected static ?string $navigationGroup = 'Laporan';

    protected static ?int $navigationSort = 10;

    protected static string $view = 'filament.pages.sales-report';

    public $startDate = null;
    public $endDate = null;
    public $paymentStatus = null;

    public function mount(): void
    {
        $this->startDate = Carbon::now()->startOfMonth()->toDateString();
        $this->endDate = Carbon::now()->endOfMonth()->toDateString();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Filter Laporan')
                    ->schema([
                        DatePicker::make('startDate')
                            ->label('Tanggal Mulai')
                            ->default(Carbon::now()->startOfMonth())
                            ->live(),
                        DatePicker::make('endDate')
                            ->label('Tanggal Akhir')
                            ->default(Carbon::now()->endOfMonth())
                            ->live(),
                        Select::make('paymentStatus')
                            ->label('Status Pembayaran')
                            ->options([
                                'all' => 'Semua',
                                'paid' => 'Lunas',
                                'pending' => 'Belum Lunas',
                            ])
                            ->default('all')
                            ->live(),
                    ])
                    ->columns(3),
            ]);
    }

    public function filter(): void
    {
        $this->resetTable();
    }

    protected function getTableQuery(): Builder
    {
        $query = Order::query();

        if ($this->startDate) {
            $query->whereDate('order_date', '>=', $this->startDate);
        }

        if ($this->endDate) {
            $query->whereDate('order_date', '<=', $this->endDate);
        }

        if ($this->paymentStatus && $this->paymentStatus !== 'all') {
            $query->where('payment_status', $this->paymentStatus);
        }

        return $query;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getTableQuery())
            ->columns([
                TextColumn::make('order_number')
                    ->label('No. Pesanan')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('order_date')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->sortable(),
                TextColumn::make('customer_name')
                    ->label('Nama Customer')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('payment_status')
                    ->label('Status Pembayaran')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'paid' => 'success',
                        'pending' => 'warning',
                        'overdue' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'paid' => 'Lunas',
                        'pending' => 'Belum Lunas',
                        'overdue' => 'Jatuh Tempo',
                        default => $state,
                    }),
                TextColumn::make('total_amount')
                    ->label('Total')
                    ->money('IDR')
                    ->sortable()
                    ->summarize(
                        Sum::make()
                            ->label('Total Penjualan')
                            ->formatStateUsing(fn(string $state): string => 'Rp ' . number_format((float)$state, 0, ',', '.')),
                    ),
            ])
            ->filters([
                SelectFilter::make('payment_status')
                    ->label('Status Pembayaran')
                    ->options([
                        'paid' => 'Lunas',
                        'pending' => 'Belum Lunas',
                    ]),

                Filter::make('order_date')
                    ->form([
                        DatePicker::make('created_from')
                            ->label('Dari Tanggal'),
                        DatePicker::make('created_until')
                            ->label('Sampai Tanggal'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn(Builder $query, $date): Builder => $query->whereDate('order_date', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn(Builder $query, $date): Builder => $query->whereDate('order_date', '<=', $date),
                            );
                    }),
            ])

            ->bulkActions([
                \Filament\Tables\Actions\BulkAction::make('export')
                    ->label('Export')
                    ->icon('heroicon-o-document-arrow-down')
                    ->action(function ($records) {
                        // Buat nama file dengan timestamp
                        $timestamp = now()->format('Y-m-d_H-i-s');
                        $filename = "laporan-penjualan_{$timestamp}.csv";

                        return response()->streamDownload(function () use ($records) {
                            $csv = fopen('php://output', 'w');

                            // Header
                            fputcsv($csv, [
                                'No Pesanan',
                                'Tanggal',
                                'Nama Customer',
                                'Status Pembayaran',
                                'Total',
                                'Jumlah Item'
                            ]);

                            // Data
                            foreach ($records as $order) {
                                fputcsv($csv, [
                                    $order->order_number,
                                    $order->order_date->format('d/m/Y'),
                                    $order->customer_name,
                                    $order->payment_status == 'paid' ? 'Lunas' : 'Belum Lunas',
                                    $order->total_amount,
                                    $order->orderItems->count(),
                                ]);
                            }

                            fclose($csv);
                        }, $filename);
                    }),
            ])
            ->defaultSort('order_date', 'desc')
            ->defaultPaginationPageOption(50);
    }

//     public function exportPdf()
//     {
//         $exporter = new Exports\SalesReportExport(
//             $this->startDate,
//             $this->endDate,
//             $this->paymentStatus ?? 'all'
//         );

//         return $exporter->downloadPdf();
//     }

//     public function exportCsv()
//     {
//         $exporter = new Exports\SalesReportExport(
//             $this->startDate,
//             $this->endDate,
//             $this->paymentStatus ?? 'all'
//         );

//         return $exporter->exportCsv();
//     }
}
