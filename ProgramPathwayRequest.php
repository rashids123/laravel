<?php

namespace App\Http\Requests;

use App\Models\ProgramPathway;

use Illuminate\Foundation\Http\FormRequest;

class ProgramPathwayRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $alertableTypes = implode( ',', array_keys(ProgramPathway::ALERTABLE_TYPES));

        return [
            'program_id' => "required|exists:programs,id",
            'alertable_type' => 'required|in:' . $alertableTypes
        ];
    }
}
