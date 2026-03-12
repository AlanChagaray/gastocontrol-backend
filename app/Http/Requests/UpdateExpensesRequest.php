<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateExpensesRequest extends FormRequest
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
        return [
            'category_id' => [
                'sometimes',
                'required',
                'exists:categories,id',
                Rule::exists('categories', 'id')->where(function ($query) {
                    $query->where('user_id', $this->user()->id);
                }),
            ],
            'amount' => 'sometimes|required|numeric|min:1',
            'merchant' => 'nullable|string|max:255',
            'expense_date' => 'sometimes|required|date',
            'notes' => 'nullable|string|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'category_id.required' => 'La categoría es obligatoria.',
            'category_id.exists' => 'La categoría seleccionada no existe o no pertenece al usuario.',
            'amount.required' => 'El monto es obligatorio.',
            'amount.numeric' => 'El monto debe ser un número.',
            'amount.min' => 'El monto debe ser al menos 1.',
            'merchant.string' => 'El comerciante debe ser una cadena de texto.',
            'merchant.max' => 'El comerciante no puede tener más de 255 caracteres.',
            'expense_date.required' => 'La fecha del gasto es obligatoria.',
            'expense_date.date' => 'La fecha del gasto debe ser una fecha válida.',
            'notes.string' => 'Las notas deben ser una cadena de texto.',
            'notes.max' => 'Las notas no pueden tener más de 1000 caracteres.',
        ];
    }
}
