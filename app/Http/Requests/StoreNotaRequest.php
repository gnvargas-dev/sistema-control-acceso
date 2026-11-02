<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreNotaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->rol === 'profesor';
    }

    public function rules(): array
    {
        return [
            'alumno_id' => ['required', 'integer', 'exists:alumnos,id'],
            'materia_id' => ['required', 'integer', 'exists:materias,id'],
            'valor' => ['required', 'integer', 'between:1,5'],
            'periodo' => ['required', 'string', 'max:50'],
            'observacion' => ['nullable', 'string'],
        ];
    }
}
