@extends('layouts.admin')

@section('content')
<div class="header">
    <h2>Editar categoria</h2>
</div>

@if ($errors->any())
    <div class="flash error">
        @foreach ($errors->all() as $error)
            <div>{{ $error }}</div>
        @endforeach
    </div>
@endif

<div class="list-card">
    <form method="POST" action="{{ route('admin.categories.update', $category) }}">
        @csrf
        @method('PUT')

        <input type="text" name="name" value="{{ old('name', $category->name) }}" placeholder="Nombre de la categoria" required>
        <button class="btn" type="submit">Guardar cambios</button>
    </form>
</div>
@endsection
