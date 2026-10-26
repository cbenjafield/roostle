<?php

namespace App\Http\Requests\Internal;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreMessageRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $maxKilobytes = config('roostle.max_message_kilobytes');

        return [
            'inbox_id' => ['required', 'integer', 'exists:inboxes,id'],
            'message_id' => ['nullable', 'string', 'max:998'],
            'from_address' => ['required', 'string', 'max:320'],
            'from_name' => ['nullable', 'string', 'max:255'],
            'to' => ['array'],
            'to.*.address' => ['required', 'string', 'max:320'],
            'to.*.name' => ['nullable', 'string', 'max:255'],
            'cc' => ['array'],
            'cc.*.address' => ['required', 'string', 'max:320'],
            'cc.*.name' => ['nullable', 'string', 'max:255'],
            'subject' => ['nullable', 'string', 'max:998'],
            'text_body' => ['nullable', 'string'],
            'html_body' => ['nullable', 'string'],
            'headers' => ['array'],
            'headers.*.name' => ['required', 'string', 'max:255'],
            'headers.*.value' => ['present', 'nullable', 'string'],
            'received_at' => ['nullable', 'date'],
            'raw' => ['required', 'file', 'max:'.$maxKilobytes],
            'attachments' => ['array'],
            'attachments.*' => ['file', 'max:'.$maxKilobytes],
        ];
    }
}
