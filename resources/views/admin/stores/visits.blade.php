@extends('layouts.admin')

@section('content')
<div class="header">
    <h2>Visitas por tienda</h2>
    <a href="/admin/stores" class="btn btn-secondary">Volver a tiendas</a>
</div>

<div class="grid" style="margin-bottom:16px;">
    <div class="card">
        <span style="display:block; color:#6b7280; font-size:14px; font-weight:700; margin-bottom:8px;">Visitas totales</span>
        <strong style="font-size:34px;">{{ number_format($totalVisits ?? 0, 0, ',', '.') }}</strong>
    </div>

    <div class="card">
        <span style="display:block; color:#6b7280; font-size:14px; font-weight:700; margin-bottom:8px;">Tiendas con visitas</span>
        <strong style="font-size:34px;">{{ $stores->total() }}</strong>
    </div>
</div>

<div class="list-card">
    @if($stores->isNotEmpty())
        <div style="width:100%; overflow-x:auto;">
            <table style="width:100%; min-width:720px; border-collapse:collapse;">
                <thead>
                    <tr>
                        <th style="padding:12px 10px; border-bottom:1px solid #e5e7eb; text-align:left;">Tienda</th>
                        <th style="padding:12px 10px; border-bottom:1px solid #e5e7eb; text-align:left;">Usuario</th>
                        <th style="padding:12px 10px; border-bottom:1px solid #e5e7eb; text-align:left;">URL</th>
                        <th style="padding:12px 10px; border-bottom:1px solid #e5e7eb; text-align:right;">Visitas</th>
                        <th style="padding:12px 10px; border-bottom:1px solid #e5e7eb; text-align:right;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($stores as $store)
                        <tr>
                            <td style="padding:12px 10px; border-bottom:1px solid #e5e7eb;">
                                <strong>{{ $store->name }}</strong><br>
                                <span style="color:#6b7280; font-size:13px;">{{ $store->businessTypeLabel() }}</span>
                            </td>
                            <td style="padding:12px 10px; border-bottom:1px solid #e5e7eb;">
                                {{ $store->user->name ?? 'Sin usuario' }}
                            </td>
                            <td style="padding:12px 10px; border-bottom:1px solid #e5e7eb;">
                                <a href="{{ url('/' . $store->slug) }}" target="_blank" rel="noopener noreferrer">/{{ $store->slug }}</a>
                            </td>
                            <td style="padding:12px 10px; border-bottom:1px solid #e5e7eb; text-align:right;">
                                <strong>{{ number_format($store->views_count ?? 0, 0, ',', '.') }}</strong>
                            </td>
                            <td style="padding:12px 10px; border-bottom:1px solid #e5e7eb; text-align:right;">
                                <a href="{{ route('admin.stores.edit', $store) }}" class="btn">Editar</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="admin-pagination" style="margin-top:16px;">
            {{ $stores->links('pagination::bootstrap-4') }}
        </div>
    @else
        <p>Aun no hay tiendas con visitas registradas.</p>
    @endif
</div>
@endsection
