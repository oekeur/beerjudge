<?php

namespace App\Http\Requests;

use App\Http\Controllers\ScoresheetController;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreScoresheetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'client_uuid' => ['required', 'uuid'],
            'entry_id' => ['required', 'integer', 'exists:entries,id'],
            'aroma' => ['required', 'integer', 'min:0', 'max:12'],
            'appearance' => ['required', 'integer', 'min:0', 'max:3'],
            'flavour' => ['required', 'integer', 'min:0', 'max:20'],
            'mouthfeel' => ['required', 'integer', 'min:0', 'max:5'],
            'overall' => ['required', 'integer', 'min:0', 'max:10'],
            'aroma_notes' => ['nullable', 'string', 'max:2000'],
            'appearance_notes' => ['nullable', 'string', 'max:2000'],
            'flavour_notes' => ['nullable', 'string', 'max:2000'],
            'mouthfeel_notes' => ['nullable', 'string', 'max:2000'],
            'overall_notes' => ['nullable', 'string', 'max:2000'],
            'descriptors' => ['nullable', 'array'],
            'descriptors.*' => ['string', 'in:'.implode(',', ScoresheetController::descriptorSlugs())],
            'status' => ['required', 'in:draft,submitted'],
        ];
    }
}
