<?php

namespace App\Filament\Pages;

use App\Models\Order;
use App\Models\Product;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Livewire\Attributes\On;

class Pos extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-shopping-cart';
    protected static ?string $navigationLabel = 'POS Orders';
    protected static ?string $title = 'POS';
    protected static ?string $slug = 'pos-orders';
    protected static ?string $navigationGroup = 'Transaksi';

    protected static string $view = 'filament.pages.pos';

    public $cart = [];
    public $customer_name = '';
    public $order_date;
    public $order_number;
    public $total = 0;
    public $payment_status = 'pending';
    public $payment_method = 'cash';
    public $search = '';

    public function mount()
    {
        $this->order_date = now()->format('Y-m-d');
        $this->generateOrderNumber();
    }

    public function generateOrderNumber()
    {
        $date = Carbon::now()->format('dmy');
        $randomStr = Str::random(3);
        $this->order_number = $date . $randomStr;
    }

    public function getProductsProperty()
    {
        if (strlen($this->search) >= 3) {
            return Product::where('name', 'like', '%' . $this->search . '%')
                ->orWhere('description', 'like', '%' . $this->search . '%')
                ->get();
        }
        
        return Product::all();
    }

    public function addToCart($productId)
    {
        $product = Product::find($productId);
        
        if (!$product) {
            return;
        }

        foreach ($this->cart as $index => $item) {
            if ($item['product_id'] == $productId) {
                $this->cart[$index]['quantity']++;
                $this->cart[$index]['subtotal'] = $this->cart[$index]['quantity'] * $this->cart[$index]['price'];
                $this->calculateTotal();
                return;
            }
        }

        $this->cart[] = [
            'product_id' => $product->id,
            'name' => $product->name,
            'price' => $product->price,
            'quantity' => 1,
            'subtotal' => $product->price
        ];

        $this->calculateTotal();
    }

    public function updateQuantity($index, $quantity)
    {
        // Konversi ke integer untuk memastikan tipe data yang benar
        $quantity = (int) $quantity;
        
        if ($quantity <= 0) {
            $this->removeItem($index);
            return;
        }

        $this->cart[$index]['quantity'] = $quantity;
        $this->cart[$index]['subtotal'] = $this->cart[$index]['price'] * $quantity;
        $this->calculateTotal();
    }

    // Tambahkan metode ini untuk mendengarkan perubahan dari input quantity
    #[On('updateCart')]
    public function updateCart()
    {
        foreach ($this->cart as $index => $item) {
            $this->cart[$index]['subtotal'] = $this->cart[$index]['quantity'] * $this->cart[$index]['price'];
        }
        $this->calculateTotal();
    }

    public function removeItem($index)
    {
        unset($this->cart[$index]);
        $this->cart = array_values($this->cart);
        $this->calculateTotal();
    }

    public function clearCart()
    {
        $this->cart = [];
        $this->total = 0;
    }

    public function calculateTotal()
    {
        $this->total = 0;
        foreach ($this->cart as $item) {
            $this->total += $item['subtotal'];
        }
    }

    public function checkout()
    {
        $this->validate([
            'customer_name' => 'required',
            'order_date' => 'required|date',
            'cart' => 'required|array|min:1',
        ], [
            'customer_name.required' => 'Nama pelanggan wajib diisi',
            'cart.min' => 'Keranjang tidak boleh kosong',
        ]);

        $order = Order::create([
            'order_number' => $this->order_number,
            'order_date' => Carbon::parse($this->order_date),
            'customer_name' => $this->customer_name,
            'payment_status' => $this->payment_status,
            'payment_method' => $this->payment_method,
            'total_amount' => $this->total,
        ]);

        foreach ($this->cart as $item) {
            $order->orderItems()->create([
                'product_id' => $item['product_id'],
                'quantity' => $item['quantity'],
                'price' => $item['price'],
                'subtotal' => $item['subtotal'],
            ]);
        }

        $this->clearCart();
        $this->customer_name = '';
        $this->generateOrderNumber();
        
        Notification::make()
            ->title('Pesanan berhasil dibuat!')
            ->success()
            ->send();
        
        return redirect()->route('filament.admin.resources.orders.view', ['record' => $order->id]);
    }
}