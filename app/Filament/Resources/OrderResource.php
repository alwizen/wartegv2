<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OrderResource\Pages;
use App\Filament\Resources\OrderResource\RelationManagers;
use App\Models\Order;
use App\Models\Product;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Str;
use Stringable;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

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
                        Forms\Components\TextInput::make('customer_name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('customer_phone')
                            ->tel()
                            ->maxLength(255),
                        Forms\Components\Textarea::make('notes')
                            ->maxLength(65535)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

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
                                    ->prefix('Rp')
                                    ->disabled()
                                    ->dehydrated()
                                    ->required(),
                                Forms\Components\TextInput::make('subtotal')
                                    ->numeric()
                                    ->prefix('Rp')
                                    ->disabled()
                                    ->dehydrated()
                                    ->required(),
                            ])
                            ->columns(4)
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
                        Forms\Components\TextInput::make('total_amount')
                            ->numeric()
                            ->prefix('Rp')
                            ->disabled()
                            ->dehydrated()
                            ->required(),
                        Forms\Components\Select::make('payment_method')
                            ->options([
                                'transfer' => 'Transfer',
                                'tempo' => 'Tempo',
                            ])
                            ->default('transfer')
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(function ($state, Set $set) {
                                if ($state === 'tempo') {
                                    // Set due date to 1 week from now
                                    $set('payment_due_date', Carbon::now()->addWeek()->format('Y-m-d'));
                                    $set('payment_status', 'pending');
                                } else {
                                    $set('payment_due_date', null);
                                }
                            }),
                        Forms\Components\DatePicker::make('payment_due_date')
                            ->visible(fn(Get $get) => $get('payment_method') === 'tempo')
                            ->minDate(fn() => now())
                            ->maxDate(fn() => now()->addWeek())
                            ->required(fn(Get $get) => $get('payment_method') === 'tempo'),
                        Forms\Components\Select::make('payment_status')
                            ->options([
                                'paid' => 'Lunas',
                                'pending' => 'Belum Lunas',
                                'overdue' => 'Jatuh Tempo',
                            ])
                            ->default('pending')
                            ->required(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('order_number')
                    ->searchable(),
                Tables\Columns\TextColumn::make('customer_name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('total_amount')
                    ->money('IDR')
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('payment_method')
                    ->colors([
                        'primary' => 'transfer',
                        'warning' => 'tempo',
                    ]),
                Tables\Columns\BadgeColumn::make('payment_status')
                    ->colors([
                        'success' => 'paid',
                        'warning' => 'pending',
                        'danger' => 'overdue',
                    ]),
                Tables\Columns\TextColumn::make('payment_due_date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('payment_status')
                    ->options([
                        'paid' => 'Lunas',
                        'pending' => 'Belum Lunas',
                        'overdue' => 'Jatuh Tempo',
                    ]),
                Tables\Filters\SelectFilter::make('payment_method')
                    ->options([
                        'transfer' => 'Transfer',
                        'tempo' => 'Tempo',
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
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOrders::route('/'),
            'create' => Pages\CreateOrder::route('/create'),
            'edit' => Pages\EditOrder::route('/{record}/edit'),
        ];
    }
}
