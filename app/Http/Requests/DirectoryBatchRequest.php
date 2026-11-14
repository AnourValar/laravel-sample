<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DirectoryBatchRequest extends FormRequest
{
    /**
     * @var string
     */
    protected string $configPath = 'models.directory.batch';

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return array_merge(
            [
                'ids' => ['required', 'list', 'max:100'],
                    'ids.*' => ['required', 'integer', 'min:1', 'distinct'],
            ],
            config($this->configPath)[$this->route('entity')],
        );
    }

    /**
     * @return void
     */
    protected function prepareForValidation(): void
    {
        if (! isset(config($this->configPath)[$this->route('entity')])) {
            abort(404);
        }
    }
}
