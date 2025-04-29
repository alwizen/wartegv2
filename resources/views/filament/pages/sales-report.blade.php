<x-filament::page>
    <form wire:submit="filter" class="space-y-6">
        {{ $this->form }}
        <div class="flex items-center space-x-4">
            <x-filament::button type="submit">
                Filter
            </x-filament::button>
        </div>
    </form>
    
    <div class="mt-4">
        {{ $this->table }}
    </div>
    
    <div class="mt-6 p-4 bg-white dark:bg-gray-800 rounded-lg shadow">
        <h3 class="text-lg font-medium text-gray-900 dark:text-white">Ringkasan Penjualan</h3>
        <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-3">
            @php
                // Buat query dasar sekali
                $baseQuery = \App\Models\Order::query();
                
                if ($startDate) {
                    $baseQuery->whereDate('order_date', '>=', $startDate);
                }
                if ($endDate) {
                    $baseQuery->whereDate('order_date', '<=', $endDate);
                }
                if ($paymentStatus && $paymentStatus !== 'all') {
                    $baseQuery->where('payment_status', $paymentStatus);
                }
                
                // Hitung total pesanan dan penjualan
                $totalOrders = $baseQuery->count();
                $totalSales = $baseQuery->sum('total_amount');
                
                // Clone query dasar untuk menghitung status terpisah
                $paidOrders = (clone $baseQuery)->where('payment_status', 'paid')->sum('total_amount');
                $pendingOrders = (clone $baseQuery)->where('payment_status', 'pending')->sum('total_amount');
                
                // Hitung persentase
                $paidPercentage = $totalSales > 0 ? ($paidOrders / $totalSales) * 100 : 0;
                $pendingPercentage = $totalSales > 0 ? ($pendingOrders / $totalSales) * 100 : 0;
            @endphp
            
            <div class="bg-blue-50 dark:bg-blue-900/20 overflow-hidden rounded-lg px-4 py-5 sm:p-6">
                <dt class="truncate text-sm font-medium text-gray-500 dark:text-gray-400">Total Pesanan</dt>
                <dd class="mt-1 text-3xl font-semibold tracking-tight text-gray-900 dark:text-white">{{ $totalOrders }}</dd>
            </div>
            
            <div class="bg-green-50 dark:bg-green-900/20 overflow-hidden rounded-lg px-4 py-5 sm:p-6">
                <dt class="truncate text-sm font-medium text-gray-500 dark:text-gray-400">Total Penjualan</dt>
                <dd class="mt-1 text-3xl font-semibold tracking-tight text-gray-900 dark:text-white">Rp {{ number_format($totalSales, 0, ',', '.') }}</dd>
            </div>
            
            <div class="bg-gray-50 dark:bg-gray-700 overflow-hidden rounded-lg px-4 py-5 sm:p-6">
                <dt class="truncate text-sm font-medium text-gray-500 dark:text-gray-400">Status Pembayaran</dt>
                <dd class="mt-1 flex items-baseline justify-between">
                    <div class="flex flex-col">
                        <span class="text-green-600 dark:text-green-400">Lunas: Rp {{ number_format($paidOrders, 0, ',', '.') }} ({{ round($paidPercentage) }}%)</span>
                        <span class="text-red-600 dark:text-red-400">Belum: Rp {{ number_format($pendingOrders, 0, ',', '.') }} ({{ round($pendingPercentage) }}%)</span>
                    </div>
                    {{-- <div class="bg-gray-100 dark:bg-gray-600 rounded-full h-2 w-20">
                        <div class="bg-green-500 h-2 rounded-full" style="width: {{ $paidPercentage }}%"></div>
                    </div> --}}
                </dd>
            </div>
        </div>
    </div>
</x-filament::page>