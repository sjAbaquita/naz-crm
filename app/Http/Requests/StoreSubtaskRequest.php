<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Enums\TeamRole;

class StoreSubtaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Only allow if user is creator or assignee of the card
        $card = $this->route('card');
        $user = $this->user();
        return $user->id === $card->created_by || $user->id === $card->assigned_to;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
        ];
    }
}
