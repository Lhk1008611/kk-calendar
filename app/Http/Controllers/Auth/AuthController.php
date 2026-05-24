<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Calendar;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    // 注册
    public function register(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        // 使用事务
        try {
            $user = DB::transaction(function () use ($data) {
                // 1. 创建用户
                $user = User::create([
                    'name' => $data['name'],
                    'email' => $data['email'],
                    'password' => bcrypt($data['password']),
                ]);

                // 2. 为用户创建默认行事历
                Calendar::create([
                    'user_id' => $user->id,
                    'name' => '我的行事历',
                    'description' => '默认行事历',
                    'color' => '#3174ad',      // 默认蓝色
                    'is_default' => true,
                    'visibility' => 1,         // 仅自己
                ]);
                return $user;
            });
            // 注册后自动登录
            Auth::login($user);
            $request->session()->regenerate();
            return response()->json($user,200);
        } catch (\Throwable $e) {
            // 记录日志（可选）
            \Log::error('注册失败：' . $e->getMessage(), ['exception' => $e]);
            // 返回统一错误响应
            return response()->json([
                'message' => '注册失败',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
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
