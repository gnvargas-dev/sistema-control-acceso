<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAsistenciaRequest extends FormRequest
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
            'fecha' => ['required', 'date'],
            'estado' => [
                'required',
                Rule::in([
                    'presente',
                    'ausente',
                    'tarde',
                    'justificado',
                ]),
            ],
        ];
    }
}
