<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OrderResource\Pages;
use App\Filament\Resources\OrderResource\RelationManagers;
use App\Models\Order;
use App\Models\Product;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Model;
use Filament\Tables;
use Filament\Tables\Columns\Summarizers\Count;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Str;
use Stringable;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\HtmlString;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\RepeaterEntry;
use Filament\Infolists\Components\RepeatableEntry;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static ?string $recordTitleAttribute = 'customer_name';

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationLabel = 'Pesanan';

    protected static ?string $navigationGroup = 'Transaksi';


    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function getGloballySearchableAttributes(): array
    {
        return [
            'order_number',
            'customer_name',
            'order_date',
            'payment_status',
        ];
    }

    public static function getGlobalSearchResultTitle(Model $record): string | Htmlable
    {

        $paymentStatus = $record->payment_status;

        return new HtmlString(
            '<div class="flex flex-col">
            <span class="font-bold">' . e($record->customer_name) . '</span>
            <span class="text-sm text-gray-500">' .
                $record->order_date->format('d M Y') . ' · Rp ' . number_format($record->total_amount, 0, ',', '.') .
                '</span>
            <span class="text-sm text-' . ($record->payment_status ? 'green' : 'red') . '-500">' . $paymentStatus . '</span>
        </div>'
        );
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Pesanan')
                    ->schema([
                        Forms\Components\TextInput::make('order_number')
                            ->default(function () {
                                $date = Carbon::now()->format('dmy');
                                $randomStr = Str::random(3);
                                return $date . $randomStr;
                            })
                            ->disabled()
                            ->dehydrated()
                            ->required(),
                        DatePicker::make('order_date')
                            ->label('Tanggal Pesanan')
                            ->default(now())
                            ->required(),
                        Forms\Components\TextInput::make('customer_name')
                            ->required()
                            ->maxLength(255),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Item Pesanan')
                    ->schema([
                        Forms\Components\Repeater::make('orderItems')
                            ->relationship()
                            ->schema([
                                Forms\Components\Select::make('product_id')
                                    ->label('Produk')
                                    ->options(Product::all()->pluck('name', 'id'))
                                    ->required()
                                    ->reactive()
                                    ->afterStateUpdated(function ($state, callable $set) {
                                        if ($state) {
                                            $product = Product::find($state);
                                            if ($product) {
                                                $set('price', $product->price);
                                                $set('subtotal', $product->price);
                                            }
                                        }
                                    })
                                    ->searchable(),
                                Forms\Components\TextInput::make('quantity')
                                    ->numeric()
                                    ->default(1)
                                    ->required()
                                    ->reactive()
                                    ->afterStateUpdated(function ($state, callable $get, callable $set) {
                                        $price = $get('price');
                                        $set('subtotal', $price * $state);
                                    }),
                                Forms\Components\TextInput::make('price')                            
                                    ->numeric()
                                    ->disabled()
                                    ->prefix('Rp')
                                    ->dehydrated()
                                    ->required(),
                                Forms\Components\Hidden::make('subtotal')
                                    ->required(),
                            ])
                            ->columns(3)
                            ->required()
                            ->minItems(1)
                            ->createItemButtonLabel('Tambah Item')
                            ->afterStateUpdated(function (array $state, Forms\Set $set) {
                                // Calculate total from all items
                                $total = 0;
                                foreach ($state as $item) {
                                    if (isset($item['subtotal'])) {
                                        $total += $item['subtotal'];
                                    }
                                }
                                $set('total_amount', $total);
                            }),
                    ]),

                Forms\Components\Section::make('Pembayaran')
                    ->schema([
                        Forms\Components\Placeholder::make('order_summary')
                            ->label('Ringkasan Pesanan')
                            ->content(function (Get $get) {
                                $items = $get('orderItems');
                                if (!$items || empty($items)) {
                                    return 'Belum ada item yang ditambahkan';
                                }

                                $summary = "";
                                foreach ($items as $index => $item) {
                                    if (isset($item['product_id']) && isset($item['quantity']) && isset($item['subtotal'])) {
                                        $product = Product::find($item['product_id']);
                                        if ($product) {
                                            $summary .= '<div class="mb-2">' .
                                                '<span class="inline-flex items-center justify-center min-h-6 px-2 py-0.5 text-sm font-medium tracking-tight rounded-xl whitespace-normal bg-primary-50 text-primary-600 dark:bg-primary-500/20 dark:text-primary-400">'
                                                . $product->name . ' ' . $item['quantity'] . 'x' . ' Rp' . number_format($item['subtotal'], 0, ',', '.')
                                                . '</span></div>';
                                        }
                                    }
                                }
                                return new \Illuminate\Support\HtmlString($summary);
                            })
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('total_amount')
                            ->numeric()
                            ->prefix('Rp')
                            ->disabled()
                            ->dehydrated()
                            ->required(),
                        Forms\Components\TextInput::make('payment_method')
                            ->default('cash')
                            ->disabled()
                            ->dehydrated(true)
                            ->required()
                            ->maxLength(255),

                        Forms\Components\DatePicker::make('payment_due_date')
                            ->visible(fn(Get $get) => $get('payment_method') === 'tempo')
                            ->minDate(fn() => now())
                            ->maxDate(fn() => now()->addWeek())
                            ->required(fn(Get $get) => $get('payment_method') === 'tempo'),
                        Forms\Components\Select::make('payment_status')
                            ->options([
                                'paid' => 'Lunas',
                                'pending' => 'Belum Lunas',
                                // 'overdue' => 'Jatuh Tempo',
                            ])
                            ->default('pending'),
                    ])
                    ->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('#')
                    ->rowIndex(),
                Tables\Columns\TextColumn::make('order_date')
                    ->searchable(),
                Tables\Columns\TextColumn::make('order_number')
                    ->searchable(),
                Tables\Columns\TextColumn::make('customer_name')
                    ->searchable(),
                Tables\Columns\BadgeColumn::make('payment_status')
                    ->colors([
                        'success' => 'paid',
                        'warning' => 'pending',
                        'danger' => 'overdue',
                    ]),
                Tables\Columns\TextColumn::make('total_amount')
                    ->money('IDR')
                    ->sortable()
                    ->summarize(
                        Sum::make()
                            ->label('Total')
                            ->formatStateUsing(fn(string $state): string => 'Rp ' . number_format($state, 0, ',', '.')),
                    ),
                    
            ])->defaultSort('created_at', 'desc')
            
            ->filters([
                Tables\Filters\SelectFilter::make('payment_status')
                    ->options([
                        'paid' => 'Lunas',
                        'pending' => 'Belum Lunas',
                    ]),

                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('created_from'),
                        Forms\Components\DatePicker::make('created_until'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn($query) => $query->whereDate('created_at', '>=', $data['created_from']),
                            )
                            ->when(
                                $data['created_until'],
                                fn($query) => $query->whereDate('created_at', '<=', $data['created_until']),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('print')
                    ->label('Print')
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

                Tables\Actions\Action::make('view')
                    ->label('Lihat')
                    ->icon('heroicon-o-eye')
                    ->modalHeading('Detail Pesanan')
                    ->modalContent(function (Order $record) {
                        return \Filament\Infolists\Infolist::make()
                            ->record($record)
                            ->schema([
                                Section::make('Informasi Pemesan')
                                    ->schema([
                                        TextEntry::make('order_number')->label('No Pesanan'),
                                        TextEntry::make('order_date')->date('d M Y'),
                                        TextEntry::make('customer_name')->label('Nama Customer'),
                                    ])
                                    ->columns(3),
                                Section::make('Item Pesanan')
                                    ->schema([
                                        RepeatableEntry::make('orderItems')
                                            ->schema([
                                                TextEntry::make('product.name')->label('Produk'),
                                                TextEntry::make('quantity')->label('Jumlah'),
                                                TextEntry::make('subtotal')->money('IDR')->label('Subtotal'),
                                            ])
                                            ->columns(3)
                                            ->label('') // biar nggak ada label Repeatable-nya
                                            ->contained(false),
                                    ])
                                    ->columns(1),
                                Section::make('Pembayaran')
                                    ->schema([
                                        TextEntry::make('payment_status')->label('Status Pembayaran'),
                                        TextEntry::make('total_amount')->label('Total')->money('IDR'),
                                    ])
                                    ->columns(2),
                            ]);
                    }),

                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),

            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOrders::route('/'),
            'create' => Pages\CreateOrder::route('/create'),
            'edit' => Pages\EditOrder::route('/{record}/edit'),
            'view' => Pages\ViewOrder::route('/{record}'),
        ];
    }
}

