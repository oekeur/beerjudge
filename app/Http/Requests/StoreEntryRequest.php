<?php

namespace App\Http\Requests;

use App\Models\Competition;
use App\Models\Entry;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreEntryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        /** @var Competition $competition */
        $competition = $this->route('competition');

        return [
            'style_id' => [
                'required',
                'integer',
                'exists:styles,id',
                function (string $attribute, mixed $value, \Closure $fail) use ($competition) {
                    if (! $competition->styles()->where('styles.id', $value)->exists()) {
                        $fail(__('validation.style_not_in_competition'));
                    }
                },
            ],
            'brewer_notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /** @return array<int, \Closure> */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                /** @var Competition $competition */
                $competition = $this->route('competition');

                $duplicate = Entry::where('competition_id', $competition->id)
                    ->where('member_id', $this->user()?->id)
                    ->where('style_id', $this->input('style_id'))
                    ->exists();

                if ($duplicate) {
                    $validator->errors()->add('style_id', __('validation.entry_duplicate_style'));
                }
            },
        ];
    }
}
