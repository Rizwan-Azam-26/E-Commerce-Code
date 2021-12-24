<?php

namespace App\Http\Requests;

use Hash;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PasswordValidateRequest extends FormRequest
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
        if ($this->getMethod() == Request::METHOD_PUT) {
            return [
                'old_password' => [
                    'required',
                    'current_password:web'
                ],
                'new_password' => [
                    'required',
                    'confirmed',
                    'min:8',
                    function ($attribute, $value, $fail) {
                        if (!empty(\request('old_password'))) {
                            if ($value == \request('old_password')) {
                                $fail("The new password and old password must be different");
                            }
                        }
                    }
                ],

            ];
        }
        return [];
    }

    public function messages()
    {
        return array_merge(parent::messages(),
            ['old_password.current_password' => 'Old password does not match to you password']);
    }
}
