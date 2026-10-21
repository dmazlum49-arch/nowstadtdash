<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ValidationService
{
    // Common email unique validation rule
    public function emailUniqueRules($userId = null)
    {
        return [
            'required',
            'email',
            'max:100',
            Rule::unique('users', 'email')->ignore($userId)->whereNull('deleted_at'),
        ];
    }

    // Common email exists validation rule
    public function emailExistsRules()
    {
        return [
            'required',
            'email',
            'max:100',
            Rule::exists('users', 'email')->whereNull('deleted_at'),
        ];
    }

    // Common email exists validation rule
    public function emailLogsExistsRules()
    {
        return [
            'required',
            'email',
            'max:100',
            Rule::exists('user_email_logs', 'email'), // Unique email validation
        ];
    }

    // Common password validation rule
    public function passwordRules()
    {
        return [
            'required',
            'min:6',
            // 'regex:/[A-Z]/', // At least one uppercase letter
            // 'regex:/[a-z]/', // At least one lowercase letter
            // 'regex:/[0-9]/', // At least one digit
            // 'regex:/[@$!%*#?&]/', // At least one special character
        ];
    }

    // Common email exists validation rule
    public function mobileRules(Request $request, $userId = null)
    {
        return [
            'nullable',
            'numeric',
            'digits_between:8,15',
            Rule::unique('users')->where('country_code', $request->country_code)->ignore($userId)->whereNull('deleted_at'), // Unique email validation
        ];
    }

    public function mobileOnlyRules()
    {
        return [
            'required',
            'numeric',
            'digits_between:8,15',
        ];
    }
}
