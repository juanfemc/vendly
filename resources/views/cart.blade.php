<h1>🛒 Carrito</h1>

@foreach($cart as $item)
    <div style="margin-bottom:10px;">
        {{ $item['name'] }} x{{ $item['quantity'] }} 
        - ${{ $item['price'] * $item['quantity'] }}
    </div>
@endforeach

<br>

<a href="/cart/whatsapp" style="background:#25D366; color:white; padding:10px; text-decoration:none;">
    Finalizar por WhatsApp
</a>