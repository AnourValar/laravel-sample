<?php

use Illuminate\Support\Facades\Route;

//--------------------------------
// User based (SPA) + S2S
//--------------------------------

// Auth
Route::prefix('/auth')
    ->controller(App\Http\Controllers\Api\AuthController::class)
    ->group(function () {
        Route::post('/confirm/pow', 'confirmPow')->middleware('throttle:lax');
        Route::post('/confirm/email', 'confirmEmail')->middleware('throttle:strict'); // @TODO: PoW ?
        Route::post('/confirm/phone', 'confirmPhone')->middleware('throttle:strict', 'confirm.pow');

        Route::post('/fa/phone', 'faPhone')->middleware('throttle:strict', 'confirm.phone.input');
        Route::post('/fa/email', 'faEmail')->middleware('throttle:strict', 'confirm.email.input');
        Route::post('/fa/password', 'faPassword')->middleware('throttle:strict');
        Route::post('/fa/session', 'faSession')->can('cabinet')->middleware('auth:sanctum', 'throttle:strict');
        Route::post('/fa/totp', 'faTotp')->middleware('throttle:strict');

        Route::post('/change/password', 'changePassword')->middleware('throttle:strict'); // confirm.fa inside
        Route::post('/register', 'register')->middleware('throttle:strict'); // confirm.fa inside
        Route::post('/login', 'login')->middleware('throttle:strict'); // confirm.fa inside
        Route::get('/state', 'getState')->middleware('auth:sanctum', 'throttle:lax');
    });

// User
Route::prefix('/user')
    ->controller(App\Http\Controllers\Api\UserController::class)
    ->can('cabinet')
    ->middleware('auth:sanctum')
    ->group(function () {
        Route::any('/', 'index')->middleware('throttle:lax');
        Route::any('/index/export/{format}', 'export')->middleware('throttle:strict');
        Route::get('/{user}', 'edit')->middleware('throttle:lax');

        Route::post('/create', 'create')->middleware('throttle:strict');
        Route::post('/update/{user}', 'update')->middleware('throttle:strict');
        Route::post('/update/email/{user}', 'updateEmail')->middleware('throttle:strict', 'confirm.email.input:user.email,new_email', 'confirm.fa.my:cryptograms_fa,1,session');
        Route::post('/update/phone/{user}', 'updatePhone')->middleware('throttle:strict', 'confirm.phone.input:user.phone,new_phone', 'confirm.fa.my:cryptograms_fa,1,session');
        Route::post('/update/telegram/{user}', 'updateTelegram')->middleware('throttle:strict');
        Route::post('/revoke/email/{user}', 'revokeEmail')->middleware('throttle:strict', 'confirm.fa.my:cryptograms_fa,1,session');
        Route::post('/revoke/phone/{user}', 'revokePhone')->middleware('throttle:strict', 'confirm.fa.my:cryptograms_fa,1,session');
        Route::post('/delete/{user}', 'delete')->middleware('throttle:strict', 'confirm.fa.my:cryptograms_fa,1,session');
        Route::post('/restore/{user}', 'restore')->middleware('throttle:strict');

        Route::post('/totp/generate', 'totpGenerate')->middleware('throttle:strict');
        Route::post('/totp/update', 'totpUpdate')->middleware('throttle:strict', 'confirm.totp.input:new_totp', 'confirm.fa.my:cryptograms_fa,1,session');
        Route::post('/totp/revoke', 'totpRevoke')->middleware('throttle:strict', 'confirm.fa.my:cryptograms_fa,1,session');
    });

// UserNotification
Route::prefix('/user/notification')
    ->controller(App\Http\Controllers\Api\UserNotificationController::class)
    ->can('cabinet')
    ->middleware('auth:sanctum')
    ->group(function () {
        Route::get('/index/{user}', 'notificationIndex')->middleware('throttle:lax');
        Route::post('/sync/{user}', 'notificationSync')->middleware('throttle:strict');
    });

// UserToken
Route::prefix('/user/token')
    ->controller(App\Http\Controllers\Api\UserTokenController::class)
    ->can('cabinet')
    ->middleware('auth:sanctum')
    ->group(function () {
        Route::get('/index', 'tokenIndex')->middleware('throttle:lax');
        Route::post('/delete/{token}', 'tokenDelete')->middleware('throttle:strict');
        Route::post('/delete_except/{token}', 'tokenDeleteExcept')->middleware('throttle:strict');
        Route::post('/push/{token}', 'tokenPush')->middleware('throttle:strict');
    });

// Notification
Route::prefix('/notification')
    ->controller(App\Http\Controllers\Api\NotificationController::class)
    ->can('cabinet')
    ->middleware('auth:sanctum')
    ->group(function () {
        Route::any('/', 'index')->middleware('throttle:lax');
        Route::any('/{notification}', 'edit')->middleware('throttle:lax');

        Route::post('/mark_read/all', 'markReadAll')->middleware('throttle:strict');
        Route::post('/mark_read/{notification}', 'markRead')->middleware('throttle:strict');

        Route::post('/personal_message/{user}', 'personalMessage')->middleware('throttle:strict');
    });

// Directory
Route::prefix('/directory')
    ->controller(App\Http\Controllers\Api\DirectoryController::class)
    //->can('cabinet')
    //->middleware('auth:sanctum')
    ->group(function () {
        Route::any('/', 'index')->middleware('throttle:lax'); // acl inside
        Route::get('/{directory}', 'edit')->middleware('throttle:lax'); // acl inside

        Route::post('/create', 'create')->can('cabinet')->middleware('auth:sanctum', 'throttle:strict');
        Route::post('/update/{directory}', 'update')->can('cabinet')->middleware('auth:sanctum', 'throttle:strict');
        Route::post('/delete/{directory}', 'delete')->can('cabinet')->middleware('auth:sanctum', 'throttle:strict');

        Route::post('/attach/{entity}', 'attach')->can('cabinet')->middleware('auth:sanctum', 'throttle:strict');
        Route::post('/detach/{entity}', 'detach')->can('cabinet')->middleware('auth:sanctum', 'throttle:strict');
        Route::post('/set/{entity}', 'set')->can('cabinet')->middleware('auth:sanctum', 'throttle:strict');
    });

// Field
Route::prefix('/field')
    ->controller(App\Http\Controllers\Api\FieldController::class)
    ->can('cabinet')
    ->middleware('auth:sanctum')
    ->group(function () {
        Route::any('/index/user/{user}', 'index')->middleware('throttle:lax');

        Route::post('/{name}/save/user/{user}', 'save')->middleware('throttle:lax');
        Route::post('/{name}/upload/user/{user}', 'upload')->middleware('throttle:lax');
        Route::post('/{name}/delete/user/{user}', 'delete')->middleware('throttle:lax');
        Route::post('/{name}/update/user/{user}', 'update')->middleware('throttle:lax');
    });

// Ticket
Route::prefix('/ticket')
    ->controller(App\Http\Controllers\Api\TicketController::class)
    //->can('cabinet')
    //->middleware('auth:sanctum')
    ->group(function () {
        Route::any('/', 'index')->can('cabinet')->middleware('auth:sanctum', 'throttle:lax');
        Route::get('/{ticket}', 'edit')->can('cabinet')->middleware('auth:sanctum', 'throttle:lax');

        Route::post('/create', 'create')->middleware('throttle:lax'); // acl inside | @TODO: PoW for guest?
        Route::post('/update/{ticket}', 'update')->can('cabinet')->middleware('auth:sanctum', 'throttle:strict');
    });

// File
Route::prefix('/file')
    ->controller(App\Http\Controllers\Api\FileController::class)
    ->can('cabinet')
    ->middleware('auth:sanctum')
    ->group(function () {
        Route::post('/upload/{entity}/{entity_id}/{name}', 'upload')->middleware('throttle:strict'); // acl inside
        Route::post('/delete/{file_virtual}', 'delete')->middleware('throttle:strict'); // acl inside
    });

// Analytics
Route::prefix('/analytics')
    ->controller(App\Http\Controllers\Api\AnalyticsController::class)
    //->can('cabinet')
    //->middleware('auth:sanctum')
    ->group(function () {
        Route::any('/{type}', 'index')->middleware('throttle:lax'); // acl inside
    });

// Journal
Route::prefix('/journal')
    ->controller(AnourValar\EloquentJournal\Http\Controllers\Api\JournalController::class)
    ->can('cabinet')
    ->middleware('auth:sanctum')
    ->group(function () {
        Route::any('/', 'index')->middleware('throttle:lax');
    });


//--------------------------------
// Etc
//--------------------------------

Route::any('/health-ping', AnourValar\LaravelHealth\Http\Controllers\HealthPingController::class)->name('health-ping');
