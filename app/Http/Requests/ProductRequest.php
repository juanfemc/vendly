<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;
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
            'store_id' => $this->user()?->isAdmin()
                ? ['required', 'integer', Rule::exists('stores', 'id')]
                : ['nullable'],
            'name' => ['required', 'string', 'max:255'],
            'price' => ['required', 'numeric'],
            'category' => ['nullable', 'string', 'max:255'],
            'material' => ['nullable', 'string', 'max:255'],
            'stock_quantity' => ['nullable', 'integer', 'min:0', 'max:999999'],
            'is_sold_out' => ['nullable', 'boolean'],
            'has_offer' => ['nullable', 'boolean'],
            'offer_original_price' => ['nullable', 'required_if:has_offer,1', 'numeric', 'gt:price'],
            'description' => ['nullable', 'string'],
            'features' => ['nullable', 'string'],
            'sizes' => ['nullable', 'string', 'max:1000'],
            'colors' => ['nullable', 'string', 'max:1000'],
            'image' => ['nullable', 'image', 'max:2048'],
            'images' => ['nullable', 'array', 'max:8'],
            'images.*' => ['image', 'max:2048'],
            'remove_images' => ['nullable', 'array'],
            'remove_images.*' => ['string'],
        ];
    }

    public function baseData(): array
    {
        $data = $this->safe()->only(['name', 'category', 'material', 'price', 'description', 'stock_quantity', 'offer_original_price']);
        $data['is_sold_out'] = $this->boolean('is_sold_out');
        $data['has_offer'] = $this->boolean('has_offer');

        if (! $data['has_offer']) {
            $data['offer_original_price'] = null;
        }

        return $data;
    }
}
