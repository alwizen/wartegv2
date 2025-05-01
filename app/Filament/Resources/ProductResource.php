<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages;
use App\Filament\Resources\ProductResource\RelationManagers;
use App\Models\Product;
use App\Models\Category;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Str;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $navigationIcon = 'heroicon-o-bookmark';

    protected static ?string $navigationGroup = 'Master Data';

    protected static ?string $navigationLabel = 'Menu';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('category_id')
                ->relationship('category', 'name')
                ->label('Kategori')
                ->required()
                ->live()
                ->afterStateUpdated(function ($state, Forms\Set $set) {
                    if ($state) {
                        $category = Category::find($state);
                        if ($category) {
                            // Generate product code based on category prefix
                            $prefix = $category->code_prefix;
                            $latestProduct = Product::where('product_code', 'like', $prefix . '%')
                                ->orderBy('product_code', 'desc')
                                ->first();

                            $nextNumber = '01';

                            if ($latestProduct) {
                                $lastCode = $latestProduct->product_code;
                                $lastNumber = (int) Str::substr($lastCode, 2, 2);
                                $nextNumber = str_pad($lastNumber + 1, 2, '0', STR_PAD_LEFT);
                            }

                            $productCode = $prefix . $nextNumber;
                            $set('product_code', $productCode);
                        }
                    }
                }),
            Forms\Components\TextInput::make('product_code')
                ->required()
                ->label('Kode Produk')
                ->unique(ignoreRecord: true)
                ->disabled()
                ->dehydrated()
                ->maxLength(4),
            Forms\Components\TextInput::make('name')
                ->label('Nama Menu')
                ->required()
                ->maxLength(255),
            // Forms\Components\Textarea::make('description')
            //     ->maxLength(65535)
            //     ->columnSpanFull(),
            Forms\Components\TextInput::make('price')
                ->required()
                ->label('Harga')
                ->numeric()
                ->prefix('Rp'),
        ]);
}
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('product_code')
                    ->searchable(),
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('price')
                    ->money('IDR'),
                Tables\Columns\TextColumn::make('category.name')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
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
            'index' => Pages\ManageProducts::route('/'),
        ];
    }
}
