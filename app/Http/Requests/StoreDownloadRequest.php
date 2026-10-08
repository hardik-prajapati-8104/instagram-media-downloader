<?php

namespace App\Http\Requests;

use App\Exceptions\MediaException;
use App\Services\Instagram\InstagramUrlValidator;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreDownloadRequest extends FormRequest
{
    private string $errorCode = 'invalid_url';

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'url' => ['required', 'string', 'max:2048', function (string $attr, mixed $value, \Closure $fail) {
                try {
                    app(InstagramUrlValidator::class)->normalize((string) $value);
                } catch (MediaException $e) {
                    $this->errorCode = $e->errorCode;
                    $fail($e->userMessage());
                }
            }],
            'type' => ['nullable', 'string', 'in:video,reels,photo,carousel,audio,media'],
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => $validator->errors()->first(),
            'error' => ['code' => $this->errorCode],
        ], 422));
    }
}
