@extends('layouts.admin')

@section('content')
<div class="header">
    <h2>Crear testimonio</h2>
    <a href="{{ route('admin.testimonials.index') }}" class="btn btn-secondary">Volver</a>
</div>

@if ($errors->any())
    <div class="flash error">
        @foreach ($errors->all() as $error)
            <div>{{ $error }}</div>
        @endforeach
    </div>
@endif

<div class="list-card">
    <form method="POST" action="{{ route('admin.testimonials.store') }}">
        @csrf

        @include('admin.testimonials.form', ['testimonial' => null])

        <button class="btn">Guardar testimonio</button>
    </form>
</div>
@endsection
