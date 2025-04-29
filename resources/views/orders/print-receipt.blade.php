================================
            NANI -  BAYU
        Jl . TPI Tegal Kota
         0928928398982938
NOTA PESANAN #{{ $order->order_number }}
Tanggal Pesanan #{{ date('d/m/Y', strtotime($order->order_date)) }}
================================
Pelanggan: {{ $order->customer_name }}
--------------------------------
ITEM                       TOTAL
--------------------------------
@foreach ($orderItems as $item)
{{ $item->product->name }}
{{ $item->quantity }}x @Rp{{ number_format($item->price, 0, ',', '.') }}     Rp{{ number_format($item->subtotal, 0, ',', '.') }}
@endforeach
--------------------------------
TOTAL         Rp{{ number_format($order->total_amount, 0, ',', '.') }}
--------------------------------
Status: {{ $order->payment_status == 'paid' ? 'LUNAS' : 'BELUM LUNAS' }}
@if($order->payment_method == 'tempo')
Jatuh tempo: {{ date('d/m/Y', strtotime($order->payment_due_date)) }}
@endif
================================
 Terima kasih atas pesanan Anda
================================