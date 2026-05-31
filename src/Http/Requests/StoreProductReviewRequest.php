<?php

namespace Commero\Http\Requests;

use Commero\Support\Locales;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProductReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $isGuest = ! $this->user();

        return [
            'display_name' => ['required', 'string', 'max:255'],
            'email' => [$isGuest ? 'required' : 'nullable', 'email', 'max:255'],
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'title' => ['nullable', 'string', 'max:255'],
            'comment' => ['required', 'string', 'max:5000'],
            'photos' => ['nullable', 'array', 'max:10'],
            'photos.*' => ['image', 'max:5120'],
            'photo_alts' => ['nullable', 'array'],
            'photo_alts.*' => ['nullable', 'string', 'max:255'],
            'locale' => ['nullable', 'string', Rule::in(Locales::supported())],
        ];
    }
}
