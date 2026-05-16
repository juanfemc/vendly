<?php

namespace App\Http\Requests;

use App\Models\Store;
use Illuminate\Foundation\Http\FormRequest;

class CheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $isReservationStore = $this->checkoutStore()?->isReservationStore() ?? false;

        return [
            'email' => ['nullable', 'email', 'max:255'],
            'name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:255'],
            'address' => ['required', 'string', 'max:255'],
            'apartment' => ['nullable', 'string', 'max:255'],
            'neighborhood' => ['required', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:255'],
            'region' => ['nullable', 'string', 'max:255'],
            'document' => ['required', 'string', 'max:255'],
            'shipping_method' => ['nullable', 'string', 'max:20'],
            ...self::reservationRules($isReservationStore),
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public static function reservationRules(bool $required = true): array
    {
        $presenceRule = $required ? 'required' : 'nullable';

        return [
            'reservation_date' => [$presenceRule, 'date', 'after_or_equal:today'],
            'reservation_time' => [$presenceRule, 'date_format:H:i'],
        ];
    }

    private function checkoutStore(): ?Store
    {
        $slug = $this->input('store') ?: $this->query('store');

        if (! $slug) {
            return null;
        }

        return Store::where('slug', $slug)->first();
    }
}
