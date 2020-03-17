<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FacultyRequest extends FormRequest
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
    public function rules(Request $request)
    {
        $rules = [
          'title' => 'required|string'
        ];

      switch ($this->getMethod())
      {
        case 'POST':
          return $rules;
        case 'PUT':
          return [
            'faculty_id' => 'required|integer|exists:faculties,id', 
          ] + $rules; 
        case 'DELETE':
          return [
              'faculty_id' => 'required|integer|exists:faculties,id'
          ];
      }
    }
}
