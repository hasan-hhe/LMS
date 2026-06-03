<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\MemberController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login']);
Route::middleware('auth:sanctum')->group(function () {
    Route::prefix('member')->group(function () {    
    Route::post('/register', [MemberController::class, 'register']);
    Route::post('/get-members', [MemberController::class, 'index']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/update-member/{id}', [MemberController::class, 'updateMember']);
    Route::post('/control-state/{id}', [MemberController::class, 'ControlAccountState']);
    Route::post('/update-participe-date/{id}', [MemberController::class, 'updateParticipeDate']);
    Route::post('/get/{id}', [MemberController::class, 'get']);
    });
});
