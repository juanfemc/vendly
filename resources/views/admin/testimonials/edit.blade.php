@extends('layouts.admin')

@section('content')
<div class="header">
    <h2>Editar testimonio</h2>
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
    <form method="POST" action="{{ route('admin.testimonials.update', $testimonial) }}">
        @csrf
        @method('PUT')

        @include('admin.testimonials.form', ['testimonial' => $testimonial])

        <button class="btn">Actualizar testimonio</button>
    </form>
</div>
@endsection
