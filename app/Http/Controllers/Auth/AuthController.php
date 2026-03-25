<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    // 注册
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        // 注册后自动登录
        Auth::login($user);
        $request->session()->regenerate();

        return response()->json($user,200);
    }

    // 登录
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (!Auth::attempt($request->only('email', 'password'), $request->boolean('remember'))) {
            throw ValidationException::withMessages([
                'email' => ['提供的凭据不正确。'],
            ]);
        }

        $request->session()->regenerate();

        return response()->json(Auth::user());
    }

    // 登出
    public function logout(Request $request)
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return response()->json(['message' => '已登出'])
            ->withCookie(cookie()->forget('laravel_session'));
    }

    // 获取当前用户
    public function user(Request $request)
    {
        \Log::info('Auth check', ['user' => $request->user(), 'session_id' => session()->getId()]);
        return response()->json($request->user());
    }
}
