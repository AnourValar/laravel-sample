<?php

use Illuminate\Support\Facades\Route;
use Spatie\Health\Http\Controllers\HealthCheckResultsController;

//--------------------------------
// Файлы
//--------------------------------

Route::controller(App\Http\Controllers\FileController::class)->group(function () {
    Route::any('/file/{file_virtual}/download/{filename}', 'download')->middleware('throttle:lax')->name('file.download'); // acl inside
});


//--------------------------------
// Auth
//--------------------------------

// Аутентификация
Route::prefix('/web')->controller(App\Http\Controllers\Auth\AuthenticatedSessionController::class)->group(function () {
    Route::get('/redirect', 'redirect');

    Route::get('/login', 'login')->middleware('guest')->name('login');
    Route::post('/login', 'store')->middleware('guest', 'throttle:strict');
    Route::post('/logout', 'destroy')->middleware('auth', 'auth.session', 'throttle:strict')->name('logout');
});


//--------------------------------
// Admin
//--------------------------------

Route::prefix('/web/admin')->name('admin.')->can('admin')->middleware('auth', 'auth.session', 'throttle:lax', 'web.locale')->group(function () {
    // admin
    Route::controller(App\Http\Controllers\Admin\DashboardController::class)
        ->group(function () {
            Route::any('/', 'index')->name('index');
        });

    // admin/user
    Route::prefix('/user')
        ->name('user.')
        ->controller(App\Http\Controllers\Admin\UserController::class)
        ->group(function () {
            Route::any('/', 'index')->can('admin.user.read')->name('index');
            Route::any('/autocomplete', 'autocomplete')->middleware('web.select2')->name('autocomplete');

            Route::any('/add', 'add')->can('admin.user.create')->name('add');
            Route::any('/render_role', 'renderRole')->can('admin.user.read|admin.user.create')->name('render_role');
            Route::post('/create', 'create')->can('admin.user.create')->name('create');

            Route::any('/edit/{user}', 'edit')->can('admin.user.read')->name('edit')->withTrashed();
            Route::post('/update/{user}', 'update')->can('admin.user.update')->name('update')->withTrashed();
            Route::post('/totp_generate/{user}', 'totpGenerate')->can('admin.user.update')->name('totp_generate')->withTrashed();
            Route::post('/totp_revoke/{user}', 'totpRevoke')->can('admin.user.update')->name('totp_revoke')->withTrashed();
            Route::post('/notification/sync/{user}', 'notificationSync')->can('admin.user.update')->name('notification.sync')->withTrashed();

            Route::post('/delete/{user}', 'delete')->can('admin.user.delete')->name('delete')->withTrashed();
            Route::post('/restore/{user}', 'restore')->can('admin.user.delete')->name('restore')->withTrashed();

            Route::post('/login_as/{user}', 'loginAs')->can('admin.user.login_as')->name('login_as');
        });

    // admin/ticket
    Route::prefix('/ticket')
        ->name('ticket.')
        ->controller(App\Http\Controllers\Admin\TicketController::class)
        ->group(function () {
            Route::any('/', 'index')->can('admin.ticket.read')->name('index');

            Route::any('/edit/{ticket}', 'edit')->can('admin.ticket.read')->name('edit');
            Route::post('/update/{ticket}', 'update')->can('admin.ticket.update')->name('update');
        });

    // admin/directory
    Route::prefix('/directory')
        ->name('directory.')
        ->controller(App\Http\Controllers\Admin\DirectoryController::class)
        ->group(function () {
            Route::any('/', 'index')->can('admin.directory.read')->name('index');
            Route::any('/render_type', 'renderType')->can('admin.directory.read|admin.directory.create')->name('render_type');

            Route::any('/add', 'add')->can('admin.directory.create')->name('add');
            Route::post('/create', 'create')->can('admin.directory.create')->name('create');

            Route::any('/edit/{directory}', 'edit')->can('admin.directory.read')->name('edit');
            Route::post('/update/{directory}', 'update')->can('admin.directory.update')->name('update');

            Route::post('/delete/{directory}', 'delete')->can('admin.directory.delete')->name('delete');
        });

    // admin/file
    Route::prefix('/file')
        ->name('file.')
        ->controller(App\Http\Controllers\Admin\FileController::class)
        ->group(function () {
            Route::post('/upload/{entity}/{entity_id}/{name}', 'upload')->name('upload'); // acl inside
            Route::post('/delete/{file_virtual}', 'delete')->name('delete'); // acl inside
        });

    // admin/field
    Route::prefix('/field')
        ->name('field.')
        ->controller(App\Http\Controllers\Admin\FieldController::class)
        ->group(function () {
            Route::post('/update/{field}/user/{user}', 'update')->can('admin.field.update')->name('update')->withTrashed();
        });

    // admin/setting
    Route::prefix('/setting')
        ->name('setting.')
        ->controller(App\Http\Controllers\Admin\SettingController::class)
        ->group(function () {
            Route::any('/', 'index')->can('admin.setting.read')->name('index');

            Route::post('/update', 'update')->can('admin.setting.update')->name('update');
        });

    // admin/journal
    Route::prefix('/journal')
        ->name('journal.')
        ->controller(AnourValar\EloquentJournal\Http\Controllers\Web\JournalController::class)
        ->group(function () {
            Route::any('/', 'index')->can('admin.administration')->name('index');
        });

    // admin/administration
    Route::prefix('/administration')
        ->name('administration.')
        ->can('admin.administration')
        ->group(function () {
            // spatie/laravel-health
            Route::get('diagnostic', HealthCheckResultsController::class)->middleware('diagnostic')->name('diagnostic');
            //Route::get('debug', AnourValar\LaravelHealth\Http\Controllers\DebuggerController::class);
        });
});
