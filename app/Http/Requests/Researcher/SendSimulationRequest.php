<?php

namespace App\Http\Requests\Researcher;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Validator;

class SendSimulationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'research_key' => ['required', 'string'],
            'time_limit_value' => ['nullable', 'integer', 'min:1'],
            'time_limit_unit' => ['nullable', 'string', 'in:minutes,hours'],
            'respondents' => ['required', 'array', 'min:1'],
            'respondents.*.name' => ['nullable', 'string', 'max:255'],
            'respondents.*.class_group' => ['nullable', 'string', 'max:255'],
            'respondents.*.email' => ['required', 'email', 'max:255'],
            'respondents.*.whatsapp_number' => ['nullable', 'string', 'max:32'],
        ];
    }

    /**
     * Verify the research key against the researcher's stored hash (second
     * authorisation layer for the send action).
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $user = $this->user();

            if ($user === null || blank($user->research_key_hash)) {
                $validator->errors()->add('research_key', 'Research key belum diatur. Silakan atur terlebih dahulu.');

                return;
            }

            if (! Hash::check((string) $this->input('research_key'), $user->research_key_hash)) {
                $validator->errors()->add('research_key', 'Research key tidak valid.');
            }
        });
    }
}
