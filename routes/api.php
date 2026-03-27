<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Calendar\CalendarController;
use App\Http\Controllers\Calendar\CalendarEventController;
use App\Http\Controllers\User\ProfileController;
use Illuminate\Support\Facades\Route;


/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/
Route::middleware('web')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/logout', [AuthController::class, 'logout']);
});

Route::middleware('auth:sanctum')->group(function (){
    Route::get('/check-auth', function () { return auth()->user(); });

    Route::get('/user', [AuthController::class, 'user']);
    Route::put('/profile', [ProfileController::class, 'update']); // 更新
    Route::delete('/profile', [ProfileController::class, 'destroy']);    // 注销

    Route::get('/calendars', [CalendarController::class, 'index']);
    Route::post('/calendar', [CalendarController::class, 'store']);  // 注意：新增用 POST
    Route::delete('/calendars', [CalendarController::class, 'destroy']);

    Route::get('/calendar_event', [CalendarEventController::class, 'getDefaultEvents']);
    Route::get('/calendar_events', [CalendarEventController::class, 'index']);
    Route::post('/calendar_event', [CalendarEventController::class, 'store']);   // 注意是 POST，标准 REST 用 POST 创建
    Route::patch('/calendar_event/{id}', [CalendarEventController::class, 'update']);
    Route::delete('/calendar_events', [CalendarEventController::class, 'destroy']); // 批量删除，通过 ids 参数
});

