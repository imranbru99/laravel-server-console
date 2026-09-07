<?php

namespace Imran\WebShell\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RunCommandRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'command' => ['required', 'string', 'max:4000'],
            'working_directory' => ['nullable', 'string', 'max:2048'],
            'timeout' => ['nullable', 'integer', 'min:1', 'max:3600'],
            'background' => ['sometimes', 'boolean'],
            'confirmed' => ['sometimes', 'boolean'],
            'source' => ['nullable', 'string', 'max:50'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function context(): array
    {
        return [
            'working_directory' => $this->input('working_directory'),
            'timeout' => $this->input('timeout'),
            'background' => $this->boolean('background'),
            'confirmed' => $this->boolean('confirmed'),
            'source' => $this->input('source', 'terminal'),
            'user_id' => $this->user()?->getAuthIdentifier(),
            'ip' => $this->ip(),
            'user_agent' => (string) $this->userAgent(),
        ];
    }
}
