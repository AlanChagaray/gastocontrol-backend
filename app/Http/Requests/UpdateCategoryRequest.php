<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCategoryRequest extends FormRequest
{
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
        $categoryId = $this->route('category'); // El ID viene del route parameter
        
        return [
            'name' => [
                'sometimes',
                'required',
                'string',
                'max:100',
                Rule::unique('categories')->where(function ($query) {
                    return $query->where('user_id', $this->user()->id);
                })->ignore($categoryId)
            ],
            'icon' => 'nullable|string|max:50',
            'color' => 'nullable|string|max:7',
            'sort_order' => 'nullable|integer',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre de la categoría es obligatorio.',
            'name.string' => 'El nombre de la categoría debe ser una cadena de texto.',
            'name.max' => 'El nombre de la categoría no puede tener más de 100 caracteres.',
            'name.unique' => 'Ya existe una categoría con este nombre.',
            'icon.string' => 'El icono debe ser una cadena de texto.',
            'icon.max' => 'El icono no puede tener más de 50 caracteres.',
            'color.string' => 'El color debe ser una cadena de texto.',
            'color.max' => 'El color no puede tener más de 7 caracteres.',
            'sort_order.integer' => 'El orden de clasificación debe ser un número entero.',
        ];
    }
}
