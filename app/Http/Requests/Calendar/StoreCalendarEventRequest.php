<?php

namespace App\Http\Requests\Calendar;

use App\Enums\EventLevel;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class StoreCalendarEventRequest extends FormRequest
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
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'owner_id' 		=> ['required', 'exists:users,id'],
            'title' 		=> ['required', 'string', 'max:255'],
            'description'   => ['nullable', 'string'],
            'location'     	=> ['nullable', 'string', 'max:255'],
            'event_level'   => [Rule::enum(EventLevel::class)],
            'start_date'    => ['required', 'date'],
            'end_date'    	=> ['nullable', 'date'],
        ];
    }

    protected function prepareForValidation()
    {
        $this->merge([
            'owner_id' => Auth::id(),
        ]);
    }
}
