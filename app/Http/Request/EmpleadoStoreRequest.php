<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class EmpleadoStoreRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'nombres' => ['required','string','max:120'],
            'apellido_paterno' => ['required','string','max:80'],
            'apellido_materno' => ['nullable','string','max:80'],
            'fecha_nacimiento' => ['nullable','date'],

            'puesto' => ['required','string'], // enum en db
            'puesto_detalle' => ['nullable','string','max:120'],
            'numero_empleado' => ['nullable','string','max:40'],
            'observaciones' => ['nullable','string'],
        ];
    }
}
