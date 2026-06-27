<?php

namespace App\Http\Requests;

use App\Models\ColombiaLocation;
use App\Models\Store;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if (! ColombiaLocation::hasCatalog()) {
            return;
        }

        $location = ColombiaLocation::where('city_code', (string) $this->input('city_code'))
            ->where('department_code', (string) $this->input('department_code'))
            ->first();

        if (! $location) {
            return;
        }

        $this->merge([
            'city' => $location->city_name,
            'region' => $location->department_name,
            'department_code' => $location->department_code,
        ]);
    }

    public function rules(): array
    {
        $store = $this->checkoutStore();
        $isReservationStore = $store?->isReservationStore() ?? false;
        $usesColombiaLocations = ColombiaLocation::hasCatalog();
        $requiresTermsAcceptance = $store?->requiresTermsAcceptance() ?? false;

        return [
            'email' => ['nullable', 'email', 'max:255'],
            'name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:255'],
            'address' => ['required', 'string', 'max:255'],
            'apartment' => ['nullable', 'string', 'max:255'],
            'neighborhood' => ['required', 'string', 'max:255'],
            'department_code' => [$usesColombiaLocations ? 'required' : 'nullable', 'string', 'max:8'],
            'city_code' => array_filter([
                $usesColombiaLocations ? 'required' : 'nullable',
                'string',
                'max:12',
                $usesColombiaLocations
                    ? Rule::exists('colombia_locations', 'city_code')->where('department_code', (string) $this->input('department_code'))
                    : null,
            ]),
            'city' => [$usesColombiaLocations ? 'nullable' : 'required', 'string', 'max:255'],
            'region' => ['nullable', 'string', 'max:255'],
            'document' => ['required', 'string', 'max:255'],
            'shipping_method' => ['nullable', 'string', 'max:20'],
            ...self::reservationRules($isReservationStore),
            'notes' => ['nullable', 'string', 'max:1000'],
            'terms_acceptance' => [$requiresTermsAcceptance ? 'accepted' : 'nullable'],
        ];
    }

    public function messages(): array
    {
        return [
            'terms_acceptance.accepted' => 'Debes aceptar los terminos y condiciones de la tienda para continuar.',
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
