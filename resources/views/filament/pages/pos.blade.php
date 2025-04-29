<x-filament-panels::page>
    <div class="flex flex-col md:flex-row gap-4">
        {{-- Produk dan Keranjang --}}
        <div class="w-full md:w-2/3">
            {{-- Form Pelanggan --}}
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4 mb-4">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label for="order_number" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Nomor Pesanan</label>
                        <input type="text" wire:model="order_number" id="order_number" disabled class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    </div>
                    
                    <div>
                        <label for="order_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Tanggal Pesanan</label>
                        <input type="date" wire:model="order_date" id="order_date" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    </div>
                    
                    <div>
                        <label for="customer_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Nama Pelanggan</label>
                        <input type="text" wire:model="customer_name" id="customer_name" placeholder="Masukkan nama pelanggan" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    </div>
                </div>
            </div>
            
            {{-- Daftar Produk --}}
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                <h2 class="text-lg font-bold mb-4">Produk</h2>
                
                {{-- Search Bar --}}
                <div class="mb-4">
                    <input type="text" wire:model.live.debounce.300ms="search" placeholder="Cari produk (minimal 3 huruf)..." class="block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                </div>
                
                {{-- Products Grid --}}
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                    @foreach($this->products as $product)
                    <div class="border rounded-lg overflow-hidden hover:shadow-md cursor-pointer transition-all" wire:click="addToCart({{ $product->id }})">
                        <div class="p-3 bg-gray-50 dark:bg-gray-700">
                            <h3 class="font-medium truncate">{{ $product->name }}</h3>
                            <p class="text-sm text-primary-600 dark:text-primary-400">Rp {{ number_format($product->price, 0, ',', '.') }}</p>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
        
        {{-- Cart and Checkout --}}
        <div class="w-full md:w-1/3">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4 sticky top-4">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-lg font-bold">Keranjang</h2>
                    <button wire:click="clearCart" class="text-red-500 text-sm">
                        Kosongkan
                    </button>
                </div>
                
                {{-- Cart Items --}}
                <div class="space-y-3 mb-4 max-h-96 overflow-y-auto">
                    @forelse($cart as $index => $item)
                    <div class="flex justify-between items-center border-b pb-2">
                        <div class="flex-1">
                            <h4 class="font-medium">{{ $item['name'] }}</h4>
                            <div class="flex items-center">
                                <button wire:click="updateQuantity({{ $index }}, {{ $item['quantity'] - 1 }})" class="bg-gray-200 dark:bg-gray-700 rounded-l px-2 py-1">-</button>
                                <input type="number" min="1" 
                                    wire:model="cart.{{ $index }}.quantity" 
                                    wire:change="updateQuantity({{ $index }}, $event.target.value)" 
                                    class="w-12 text-center border-y py-1 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                <button wire:click="updateQuantity({{ $index }}, {{ $item['quantity'] + 1 }})" class="bg-gray-200 dark:bg-gray-700 rounded-r px-2 py-1">+</button>
                                <span class="ml-2">× Rp {{ number_format($item['price'], 0, ',', '.') }}</span>
                            </div>
                        </div>
                        <div class="text-right">
                            <p>Rp {{ number_format($item['subtotal'], 0, ',', '.') }}</p>
                            <button wire:click="removeItem({{ $index }})" class="text-red-500 text-sm">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
                                </svg>
                            </button>
                        </div>
                    </div>
                    @empty
                    <div class="text-center py-4 text-gray-500">
                        Belum ada produk di keranjang
                    </div>
                    @endforelse
                </div>
                
                {{-- Totals --}}
                <div class="space-y-2">
                    <div class="flex justify-between items-center font-bold">
                        <span>Total</span>
                        <span>Rp {{ number_format($total, 0, ',', '.') }}</span>
                    </div>
                    
                    <div class="pt-2">
                        <label for="payment_status" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Status Pembayaran</label>
                        <select wire:model="payment_status" id="payment_status" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                            <option value="paid">Lunas</option>
                            <option value="pending">Belum Lunas</option>
                        </select>
                    </div>
                    
                    {{-- Checkout Button --}}
                    <button 
                        wire:click="checkout" 
                        class="w-full mt-4 bg-primary-600 hover:bg-primary-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline-primary transition-colors"
                        @if(empty($cart)) disabled @endif
                    >
                        Checkout
                    </button>
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>