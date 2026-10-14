<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Basis für die Admin-Formulare. Der Zugriff ist bereits per auth-Middleware geschützt.
 */
abstract class AdminFormRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Regeln für das Bild aus der Drag-&-Drop-Zone.
     *
     * @return list<string>
     */
    protected function imageRules(bool $required = false): array
    {
        return [
            $required ? 'required' : 'nullable',
            'image',
            'mimes:jpg,jpeg,png,gif,webp',
            'max:'.config('havoc.uploads.max_kilobytes'),
        ];
    }
}
