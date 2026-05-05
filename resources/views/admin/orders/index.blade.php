@extends('layouts.admin')

@section('content')
<div class="header">
    <h2>Pedidos</h2>
</div>

@if (session('success'))
    <div class="flash success">{{ session('success') }}</div>
@endif

@if ($errors->any())
    <div class="flash error">{{ $errors->first() }}</div>
@endif

@if ($orders->isEmpty())
    <div class="list-card">No hay pedidos todavia.</div>
@endif

@foreach($orders as $order)
    <div class="list-card">
        <strong>{{ $order->customer_name ?: 'Sin nombre' }}</strong><br>
        Tel: {{ $order->customer_phone ?: 'Sin telefono' }}<br>
        Ciudad: {{ $order->customer_city ?: 'Sin ciudad' }}<br>
        Direccion: {{ $order->customer_address ?: 'Sin direccion' }}<br>
        Documento: {{ $order->customer_document ?: 'Sin documento' }}<br>
        @if (auth()->user()->isAdmin())
            Tienda: {{ $order->store?->name ?? 'Sin tienda' }}<br>
        @endif
        Total: ${{ $order->total }}<br>
        Estado: {{ $order->statusLabel() }}<br>
        @if ($order->store?->isReservationStore())
            Fecha deseada: {{ $order->reservation_date?->format('Y-m-d') ?: 'Sin fecha' }}<br>
            Hora deseada: {{ $order->reservation_time ?: 'Sin hora' }}<br>
        @endif
        @if ($order->notes)
            Notas: {{ $order->notes }}<br>
        @endif
        @if ($order->items->isNotEmpty())
            <br>
            <strong>Productos:</strong>
            <ul style="margin:8px 0 0; padding-left:18px;">
                @foreach ($order->items as $item)
                    <li>
                        {{ $item->displayName() }} x{{ $item->quantity }}
                        @if ($item->size)
                            - Talla: {{ $item->size }}
                        @endif
                        @if ($item->color)
                            - Color: {{ $item->color }}
                        @endif
                    </li>
                @endforeach
            </ul>
        @endif
        <br>
        <form method="POST" action="{{ route('admin.orders.status', $order) }}" style="margin-top:10px; display:flex; gap:10px; align-items:center; flex-wrap:wrap;">
            @csrf
            @method('PATCH')
            <label for="status-{{ $order->id }}">Cambiar estado</label>
            <select name="status" id="status-{{ $order->id }}">
                @foreach($statusOptions as $value => $label)
                    <option value="{{ $value }}" @selected($order->status === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <button type="submit" class="btn">Guardar</button>
        </form>

        <form method="POST" action="{{ route('admin.orders.destroy', $order) }}" style="margin-top:10px;" onsubmit="return confirm('¿Eliminar este pedido? Esta accion no se puede deshacer.');">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-danger">Eliminar pedido</button>
        </form>
    </div>
@endforeach
@endsection
