<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }
    
    /**
     * Get custom messages for validator errors.
     *
     * @return array
     */
    public function messages()
    {
        return [
            'name.required' => '姓名不能为空',
            'email.required' => '邮箱地址不能为空',
            'email.email' => '请输入有效的邮箱地址',
            'email.unique' => '该邮箱已被注册',
            'password.required' => '密码不能为空',
            'password.min' => '密码至少需要8个字符',
            'password.confirmed' => '密码确认不匹配',
        ];
    }
}