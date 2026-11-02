<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUsuarioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->rol === 'administrador';
    }

    public function rules(): array
    {
        return [
            'cedula' => [
                'required',
                'string',
                'max:30',
                Rule::unique('usuarios', 'cedula')
                    ->ignore($this->route('usuario')),
            ],
            'password' => [
                $this->isMethod('POST') ? 'required' : 'nullable',
                'string',
                'min:8',
            ],
            'nombre' => ['required', 'string', 'max:150'],
            'rol' => [
                'required',
                Rule::in([
                    'alumno',
                    'profesor',
                    'administrador',
                ]),
            ],
            'activo' => ['sometimes', 'boolean'],
        ];
    }
}
