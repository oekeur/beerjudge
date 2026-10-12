<?php

namespace App\Http\Requests;

use App\Enums\ScoresheetStatus;
use App\Models\Scoresheet;
use App\Services\DescriptorService;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
        $scoring = $this->input('status') === ScoresheetStatus::Submitted->value ? 'required' : 'nullable';

        return [
            'client_uuid' => ['required', 'uuid'],
            'entry_id' => ['required', 'integer', 'exists:entries,id'],
            'aroma' => [$scoring, 'integer', 'min:0', 'max:'.Scoresheet::SECTION_MAX['aroma']],
            'appearance' => [$scoring, 'integer', 'min:0', 'max:'.Scoresheet::SECTION_MAX['appearance']],
            'flavour' => [$scoring, 'integer', 'min:0', 'max:'.Scoresheet::SECTION_MAX['flavour']],
            'mouthfeel' => [$scoring, 'integer', 'min:0', 'max:'.Scoresheet::SECTION_MAX['mouthfeel']],
            'overall' => [$scoring, 'integer', 'min:0', 'max:'.Scoresheet::SECTION_MAX['overall']],
            'aroma_notes' => ['nullable', 'string', 'max:2000'],
            'appearance_notes' => ['nullable', 'string', 'max:2000'],
            'flavour_notes' => ['nullable', 'string', 'max:2000'],
            'mouthfeel_notes' => ['nullable', 'string', 'max:2000'],
            'overall_notes' => ['nullable', 'string', 'max:2000'],
            'descriptors' => ['nullable', 'array'],
            'descriptors.*' => ['string', 'in:'.implode(',', DescriptorService::slugs())],
            'client_revision' => ['nullable', 'integer:strict', 'min:0', 'max:2147483647'],
            'status' => ['required', Rule::enum(ScoresheetStatus::class)],
        ];
    }
}
