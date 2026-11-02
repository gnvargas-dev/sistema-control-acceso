<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMateriaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->rol === 'administrador';
    }

    public function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:150'],
            'curso_id' => ['required', 'integer', 'exists:cursos,id'],
            'activa' => ['sometimes', 'boolean'],
        ];
    }
}
