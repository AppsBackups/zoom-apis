<?php


use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\SuperAdminController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\TokenHistoryController;

Route::post('/login', [AuthController::class, 'login']);


Route::middleware('auth:sanctum')->group(function () {
    // Superadmin routes
    Route::post('/superadmin/admin', [SuperAdminController::class, 'createAdmin']);
    Route::post('/superadmin/user', [SuperAdminController::class, 'createUser']);
    Route::post('/superadmin/assign-tokens', [SuperAdminController::class, 'assignTokensToAdmin']);
    Route::get('/admins', [SuperAdminController::class, 'listAdmins']);
    Route::get('/admins/{admin_id}/users', [SuperAdminController::class, 'listUsersUnderAdmin']);
    Route::put('/admins/{admin_id}', [SuperAdminController::class, 'editAdmin']);
    Route::put('/users/{user_id}', [SuperAdminController::class, 'editUser']);
    Route::delete('/admins/{admin_id}', [SuperAdminController::class, 'deleteAdmin']);
    Route::delete('/users/{user_id}', [SuperAdminController::class, 'deleteUser']);
    Route::post('/admin/remove-tokens', [AdminController::class, 'removeTokensFromAdmin']);



    Route::post('/admin/user', [AdminController::class, 'createUser']);
    Route::get('/admin/users', [AdminController::class, 'listUsers']);
    Route::put('/admin/users/{user_id}', [AdminController::class, 'editUser']);
    Route::delete('/admin/users/{user_id}', [AdminController::class, 'deleteUser']);
});


Route::middleware('auth:sanctum')->get(
    '/token-history',
    [TokenHistoryController::class, 'index']
);
