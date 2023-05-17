<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UsageRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'timestamp' => 'sometimes',
            'payload.customer.id' => ['required', 'exists:App\Models\Account,nirvana_id'],
            'is_manual' => ['sometimes', 'boolean', 'nullable'],
        ];
    }
}
