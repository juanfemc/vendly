@php($testimonial = $testimonial ?? null)

<label class="field-label" for="testimonial_name">Nombre</label>
<input id="testimonial_name" type="text" name="name" value="{{ old('name', $testimonial?->name) }}" placeholder="Nombre de la persona" required>

<label class="field-label" for="testimonial_role">Rol o tipo de negocio</label>
<input id="testimonial_role" type="text" name="role" value="{{ old('role', $testimonial?->role) }}" placeholder="Ej: Moda y accesorios">

<label class="field-label" for="testimonial_initials">Iniciales</label>
<input id="testimonial_initials" type="text" name="initials" value="{{ old('initials', $testimonial?->initials) }}" placeholder="Ej: LM" maxlength="8">

<label class="field-label" for="testimonial_quote">Testimonio</label>
<textarea id="testimonial_quote" name="quote" rows="5" placeholder="Escribe el testimonio" required>{{ old('quote', $testimonial?->quote) }}</textarea>

<label class="field-label" for="testimonial_sort_order">Orden</label>
<input id="testimonial_sort_order" type="number" name="sort_order" value="{{ old('sort_order', $testimonial?->sort_order ?? 0) }}" placeholder="Orden">

<label style="display:flex; align-items:center; gap:10px; margin-bottom:12px;">
    <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $testimonial?->is_active ?? true)) style="width:auto; margin:0;">
    Activo
</label>
