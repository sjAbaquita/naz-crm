<?php

namespace App\Http\Requests\Attachment;

use Illuminate\Foundation\Http\FormRequest;

class StoreAttachmentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', [\App\Models\Attachment::class, $this->route('current_team')]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
		$allowedMimeTypes = [
			'image/*',
			'application/pdf',
			'video/*',

			// Microsoft Word
			'application/msword',
			'application/vnd.openxmlformats-officedocument.wordprocessingml.document',

			// Microsoft Excel
			'application/vnd.ms-excel',
			'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',

			// Microsoft PowerPoint
			'application/vnd.ms-powerpoint',
			'application/vnd.openxmlformats-officedocument.presentationml.presentation',
		];

        return [
            'file' => ['required', 'file', 'max:10240', 'mimetypes:' . implode(',', $allowedMimeTypes)],
        ];
    }
}
