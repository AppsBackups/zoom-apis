<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\SuperAdminController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\TokenHistoryController;
use Illuminate\Routing\RouteUri;
use App\Http\Controllers\EventController;
use App\Http\Controllers\AlertController;
use App\Http\Controllers\MetricController;


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
    Route::post('/admin/remove-tokens', [SuperAdminController::class, 'removeTokensFromAdmin']);
    Route::get('/superadmin/collectdata', [SuperAdminController::class, 'data']);
    Route::post('/superadmin/admin/status', [SuperAdminController::class, 'updateAdminStatus']);
    Route::post('/superadmin/admin/get', [SuperAdminController::class, 'getAdminById']);
    Route::post('/superadmin/registerevent', [EventController::class, 'registerEvent']);
    Route::post('/superadmin/eventlist', [EventController::class, 'eventList']);
    Route::post('/superadmin/togglelive', [EventController::class, 'toggleLive']);
    Route::post('/superadmin/updateevent', [EventController::class, 'updateEvent']);
    Route::post('/superadmin/deleteevent', [EventController::class, 'deleteEvent']);
    Route::post('/superadmin/getevent', [EventController::class, 'getEventById']);
    Route::get('/superadmin/allusers', [SuperAdminController::class, 'allusers']);
    Route::get('/superadmin/myusers', [SuperAdminController::class, 'myusers']);
    Route::get('/superadmin/alert', [AlertController::class, 'superAlert']);
    Route::post('/superadmin/alertupdate', [AlertController::class, 'superAlertUpdate']);
    Route::get('/superadmin/metrics', [MetricController::class, 'getMetrics']);
    Route::post('/superadmin/metricsupdate', [MetricController::class, 'updateMetrics']);


    // Admin routes
    Route::post('/admin/user', [AdminController::class, 'createUser']);
    Route::get('/admin/users', [AdminController::class, 'listUsers']);
    Route::put('/admin/users/{user_id}', [AdminController::class, 'editUser']);
    Route::delete('/admin/users/{user_id}', [AdminController::class, 'deleteUser']);
    Route::get('/admin/collectdata', [AdminController::class, 'dashboardStats']);
    Route::post('/admin/user/status', [AdminController::class, 'updateUserStatus']);
    Route::post('/admin/user/get', [AdminController::class, 'getUserById']);
    Route::post('/admin/updatelevel', [AdminController::class, 'updateUserLevel']);
    Route::post('/admin/eventlist', [EventController::class, 'eventList']);
    Route::get('/admin/getevents', [AdminController::class, 'eventList']);
    Route::post('/admin/togglelive', [EventController::class, 'toggleLive']);
    Route::get('/admin/expiredusers', [AdminController::class, 'expiredUsers']);
    Route::post('/admin/updateevent', [EventController::class, 'updateEvent']);



    Route::get('/user/alert', [AlertController::class, 'clientAlert']);
    Route::get('/user/events', [EventController::class, 'listLiveEvents']);
});

Route::post('/eventjwt', [EventController::class, 'generateEventJwt']);

Route::middleware('auth:sanctum')->get(
    '/token-history',
    [TokenHistoryController::class, 'index']
);
