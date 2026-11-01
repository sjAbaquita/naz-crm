<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Subtask;

class UpdateSubtaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Only allow if user is the creator of the subtask
        $subtask = $this->route('subtask');
        if (is_numeric($subtask)) {
            $subtask = Subtask::find($subtask);
        }
        return $subtask && $this->user()->id === $subtask->created_by;
    }

    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'is_completed' => ['sometimes', 'boolean'],
        ];
    }
}
