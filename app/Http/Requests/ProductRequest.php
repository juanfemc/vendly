<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'price' => ['required', 'numeric'],
            'category' => ['nullable', 'string', 'max:255'],
            'material' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'features' => ['nullable', 'string'],
            'sizes' => ['nullable', 'string', 'max:1000'],
            'colors' => ['nullable', 'string', 'max:1000'],
            'image' => ['nullable', 'image', 'max:2048'],
        ];
    }

    public function baseData(): array
    {
        return $this->safe()->only(['name', 'category', 'material', 'price', 'description']);
    }
}
