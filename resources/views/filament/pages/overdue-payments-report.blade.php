<x-filament-panels::page>
<x-filament::section>
             <div class="mb-4">
                 <h2 class="text-lg font-bold">Daftar Pembayaran Jatuh Tempo</h2>
                 <p class="text-sm text-gray-500">Menampilkan semua pesanan dengan metode pembayaran tempo yang sudah melewati tenggat waktu</p>
             </div>
         </x-filament::section>

         <x-filament::section>
             {{ $this->table }}
         </x-filament::section>
</x-filament-panels::page>
