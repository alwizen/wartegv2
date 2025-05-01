@php
    $width = 45;
    // Helper function untuk perataan dan pemotongan string
    function padTextRight($text, $length) {
        if (mb_strlen($text) > $length) {
            return mb_substr($text, 0, $length);
        }
        return $text . str_repeat(' ', $length - mb_strlen($text));
    }
    
    function padTextLeft($text, $length) {
        if (mb_strlen($text) > $length) {
            return mb_substr($text, 0, $length);
        }
        return str_repeat(' ', $length - mb_strlen($text)) . $text;
    }
    
    function centerText($text, $width) {
        if (mb_strlen($text) > $width) {
            return mb_substr($text, 0, $width);
        }
        $padding = ($width - mb_strlen($text)) / 2;
        return str_repeat(' ', floor($padding)) . $text . str_repeat(' ', ceil($padding));
    }
@endphp
{{ str_repeat('+', $width) }}
{{ centerText('* Warung Nasi NANI / BAYU *', $width) }}
{{ centerText('TPI Pelabuhan Tegal Kota', $width) }}
{{ centerText('0815-6951-180', $width) }}

NOTA PESANAN #{{ $order->order_number }}
Tanggal Pesanan: {{ date('d/m/Y', strtotime($order->order_date)) }}
{{ str_repeat('=', $width) }}
Pelanggan: {{ $order->customer_name }}
{{ str_repeat('-', $width) }}
ITEM                        QTY         TOTAL
{{ str_repeat('-', $width) }}
@foreach ($orderItems as $item)
@php
    $name = padTextRight($item->product->name, 22);
    $qty = padTextLeft($item->quantity . 'x', 7);
    $price = 'Rp' . number_format($item->subtotal, 0, ',', '.');
    $price = padTextLeft($price, 13);
@endphp
{{ $name }} {{ $qty }} {{ $price }}
@endforeach
{{ str_repeat('-', $width) }}
{{ padTextRight('TOTAL:', 30) }} {{ padTextLeft('Rp' . number_format($order->total_amount, 0, ',', '.'), 13) }}
{{ str_repeat('-', $width) }}
Status: {{ $order->payment_status == 'paid' ? 'LUNAS' : 'BELUM LUNAS' }}
@if($order->payment_method == 'tempo')
Jatuh tempo: {{ date('d/m/Y', strtotime($order->payment_due_date)) }}
@endif
{{ str_repeat('+', $width) }}
{{ centerText('Terima kasih atas pesanan Anda', $width) }}
{{ str_repeat('+', $width) }}