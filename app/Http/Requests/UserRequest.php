<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UserRequest extends FormRequest
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
            'name' => 'required|string',
            'email' => 'string|unique:users,email',
            'password' => 'required|string',
            'role_id' => 'required|integer|exists:roles,id',
            'department_id' => 'required|integer|exists:departments,id',
            'hidden' => 'boolean'
        ];

        switch ($this->getMethod())
        {
            case 'POST':
                return $rules;
            case 'PUT':
                return [
                        'id' => 'required|integer|exists:users,id',
                        'email' => [
                            Rule::unique('users')->ignore($this->email, 'email')
                        ]
                    ] + $rules;
            case 'DELETE':
                return [
                    'id' => 'required|integer|exists:users,id'
                ];
        }
    }
}
