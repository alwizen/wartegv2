<?php

namespace App\Filament\Pages;

use App\Models\Order;
use App\Models\Product;
use Carbon\Carbon;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Form;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;
use Filament\Pages\Page;
use Filament\Tables\Contracts\HasTable;

class SalesReport extends Page implements HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-document-chart-bar';
    protected static ?string $navigationGroup = 'Laporan';
    protected static ?string $title = 'Laporan Penjualan';
    protected static ?string $slug = 'reports/sales';
    protected static ?int $navigationSort = 1;

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'start_date' => now()->startOfMonth()->format('Y-m-d'),
            'end_date' => now()->format('Y-m-d'),
            'report_type' => 'sales',
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Filter Laporan')
                    ->schema([
                        DatePicker::make('start_date')
                            ->label('Tanggal Mulai')
                            ->required(),
                        DatePicker::make('end_date')
                            ->label('Tanggal Akhir')
                            ->required(),
                        Select::make('report_type')
                            ->label('Jenis Laporan')
                            ->options([
                                'sales' => 'Laporan Penjualan',
                                'products' => 'Laporan Produk Terlaris',
                                'payments' => 'Laporan Pembayaran',
                            ])
                            ->required(),
                    ])
                    ->columns(3),
            ])
            ->statePath('data');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getTableQuery())
            ->columns($this->getTableColumns())
            ->paginated(false);
    }

    public function getTableQuery()
    {
        if (!isset($this->data['report_type'])) {
            return Order::query()->whereNull('id');
        }

        $startDate = $this->data['start_date'] ?? now()->startOfMonth()->format('Y-m-d');
        $endDate = $this->data['end_date'] ?? now()->format('Y-m-d');

        switch ($this->data['report_type']) {
            case 'sales':
                return Order::query()
                    ->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
                    ->orderBy('created_at', 'desc');

            case 'products':
                return DB::table('order_items')
                    ->join('products', 'order_items.product_id', '=', 'products.id')
                    ->join('orders', 'order_items.order_id', '=', 'orders.id')
                    ->whereBetween('orders.created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
                    ->select(
                        'products.product_code',
                        'products.name',
                        DB::raw('SUM(order_items.quantity) as total_quantity'),
                        DB::raw('SUM(order_items.subtotal) as total_sales')
                    )
                    ->groupBy('products.id', 'products.product_code', 'products.name')
                    ->orderByRaw('SUM(order_items.quantity) DESC');

            case 'payments':
                return Order::query()
                    ->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
                    ->select(
                        'payment_method',
                        'payment_status',
                        DB::raw('COUNT(*) as total_orders'),
                        DB::raw('SUM(total_amount) as total_amount')
                    )
                    ->groupBy('payment_method', 'payment_status')
                    ->orderBy('payment_method')
                    ->orderBy('payment_status');

            default:
                return Order::query()->whereNull('id');
        }
    }

    public function getTableColumns(): array
    {
        if (!isset($this->data['report_type'])) {
            return [];
        }

        switch ($this->data['report_type']) {
            case 'sales':
                return [
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
                    TextColumn::make('payment_method')
                        ->label('Metode Pembayaran')
                        ->badge(),
                    TextColumn::make('payment_status')
                        ->label('Status Pembayaran')
                        ->badge()
                        ->colors([
                            'success' => 'paid',
                            'warning' => 'pending',
                            'danger' => 'overdue',
                        ]),
                    TextColumn::make('created_at')
                        ->label('Tanggal')
                        ->dateTime()
                        ->sortable(),
                ];

            case 'products':
                return [
                    TextColumn::make('product_code')
                        ->label('Kode Produk')
                        ->searchable(),
                    TextColumn::make('name')
                        ->label('Nama Produk')
                        ->searchable(),
                    TextColumn::make('total_quantity')
                        ->label('Jumlah Terjual')
                        ->sortable(),
                    TextColumn::make('total_sales')
                        ->label('Total Penjualan')
                        ->money('IDR')
                        ->sortable(),
                ];

            case 'payments':
                return [
                    TextColumn::make('payment_method')
                        ->label('Metode Pembayaran')
                        ->badge(),
                    TextColumn::make('payment_status')
                        ->label('Status')
                        ->badge()
                        ->colors([
                            'success' => 'paid',
                            'warning' => 'pending',
                            'danger' => 'overdue',
                        ]),
                    TextColumn::make('total_orders')
                        ->label('Jumlah Order')
                        ->sortable(),
                    TextColumn::make('total_amount')
                        ->label('Total Jumlah')
                        ->money('IDR')
                        ->sortable(),
                ];

            default:
                return [];
        }
    }

    protected function getFormActions(): array
    {
        return [
            \Filament\Forms\Components\Actions\Action::make('generateReport')
                ->label('Generate Laporan')
                ->submit('generateReport'),
        ];
    }

    protected function getViewData(): array
    {
        return [
            'startDate' => $this->data['start_date'] ?? now()->startOfMonth()->format('Y-m-d'),
            'endDate' => $this->data['end_date'] ?? now()->format('Y-m-d'),
        ];
    }

    protected static string $view = 'filament.pages.sales-report';
}
