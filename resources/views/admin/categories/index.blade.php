@extends('layouts.admin')

@section('content')
<div class="header">
    <h2>Categorias</h2>
</div>

@if (session('success'))
    <div class="flash success">{{ session('success') }}</div>
@endif

@if ($errors->any())
    <div class="flash error">
        @foreach ($errors->all() as $error)
            <div>{{ $error }}</div>
        @endforeach
    </div>
@endif

<div class="list-card">
    <h3 style="margin-top:0;">Crear categoria</h3>
    <form method="POST" action="{{ route('admin.categories.store') }}">
        @csrf
        <input type="text" name="name" value="{{ old('name') }}" placeholder="Nombre de la categoria" required>
        <button class="btn" type="submit">Agregar categoria</button>
    </form>
</div>

@if ($categories->isEmpty())
    <div class="list-card">No hay categorias registradas.</div>
@endif

@foreach ($categories as $category)
    <div class="list-card">
        <strong>{{ $category->name }}</strong>

        <div style="display:flex; flex-wrap:wrap; gap:10px; margin-top:14px;">
            <a href="{{ route('admin.categories.edit', $category) }}" class="btn">Editar</a>

            <form method="POST" action="{{ route('admin.categories.destroy', $category) }}" onsubmit="return confirm('¿Eliminar esta categoria? Los productos quedaran sin categoria.');">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-secondary">Eliminar</button>
            </form>
        </div>
    </div>
@endforeach
@endsection
