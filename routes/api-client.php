<?php

use Illuminate\Support\Facades\Route;
use Jexactyl\Http\Controllers\Api\Client;
use Jexactyl\Http\Middleware\Activity\ServerSubject;
use Jexactyl\Http\Middleware\Activity\AccountSubject;
use Jexactyl\Http\Middleware\RequireTwoFactorAuthentication;
use Jexactyl\Http\Middleware\Api\Client\Server\ResourceBelongsToServer;
use Jexactyl\Http\Middleware\Api\Client\Server\AuthenticateServerAccess;

/*
|--------------------------------------------------------------------------
| Client Control API
|--------------------------------------------------------------------------
|
| Endpoint: /api/client
|
*/
Route::get('/', [Client\ClientController::class, 'index'])->name('api:client.index');
Route::get('/permissions', [Client\ClientController::class, 'permissions']);

Route::prefix('/account')->middleware(AccountSubject::class)->group(function () {
    Route::prefix('/')->withoutMiddleware(RequireTwoFactorAuthentication::class)->group(function () {
        Route::get('/', [Client\AccountController::class, 'index'])->name('api:client.account');
        Route::get('/two-factor', [Client\TwoFactorController::class, 'index']);
        Route::post('/two-factor', [Client\TwoFactorController::class, 'store']);
        Route::post('/two-factor/disable', [Client\TwoFactorController::class, 'delete']);
    });

    Route::get('/logs', [Client\AccountLogController::class, 'index'])->withoutMiddleware(RequireTwoFactorAuthentication::class);
    Route::delete('/logs', [Client\AccountLogController::class, 'delete'])->withoutMiddleware(RequireTwoFactorAuthentication::class);

    Route::post('/verify', [Client\AccountController::class, 'verify'])->name('api:client.account.verify');
    Route::post('/coupon', [Client\AccountController::class, 'coupon'])->name('api:client.account.coupon');

    Route::put('/email', [Client\AccountController::class, 'updateEmail'])->name('api:client.account.update-email');
    Route::put('/password', [Client\AccountController::class, 'updatePassword'])->name('api:client.account.update-password');
    Route::put('/username', [Client\AccountController::class, 'updateUsername'])->name('api:client.account.update-username');

    Route::get('/activity', Client\ActivityLogController::class)->name('api:client.account.activity');
    Route::get('/activity/latest', [Client\ActivityLogController::class, 'latest'])->name('api:client.account.activity');

    Route::prefix('/referrals')->group(function () {
        Route::get('/', [Client\ReferralsController::class, 'index']);
        Route::get('/activity', [Client\ReferralsController::class, 'activity']);

        Route::post('/', [Client\ReferralsController::class, 'store']);
        Route::put('/use-code', [Client\ReferralsController::class, 'use']);

        Route::delete('/{code}', [Client\ReferralsController::class, 'delete']);
    });

    Route::get('/discord', [Client\DiscordController::class, 'link'])->name('api:client.account.discord');
    Route::get('/discord/callback', [Client\DiscordController::class, 'callback'])->name('api:client.account.discord.callback');
    Route::post('/discord/unlink', [Client\DiscordController::class, 'unlink'])->name('api:client.account.discord.unlink');

    Route::get('/api-keys', [Client\ApiKeyController::class, 'index']);
    Route::post('/api-keys', [Client\ApiKeyController::class, 'store']);
    Route::delete('/api-keys/{identifier}', [Client\ApiKeyController::class, 'delete']);

    Route::prefix('/ssh-keys')->group(function () {
        Route::get('/', [Client\SSHKeyController::class, 'index']);
        Route::post('/', [Client\SSHKeyController::class, 'store']);
        Route::post('/remove', [Client\SSHKeyController::class, 'delete']);
    });

    Route::prefix('/tickets')->group(function () {
        Route::get('/', [Client\TicketController::class, 'index']);
        Route::get('/{id}', [Client\TicketController::class, 'view']);
        Route::get('/{id}/messages', [Client\TicketController::class, 'viewMessages']);

        Route::post('/', [Client\TicketController::class, 'new']);
        Route::post('/{id}/messages', [Client\TicketController::class, 'newMessage']);

        Route::delete('/{id}', [Client\TicketController::class, 'close']);
    });
});

/*
|--------------------------------------------------------------------------
| Client Control API
|--------------------------------------------------------------------------
|
| Endpoint: /api/client/store
|
*/
Route::group([
    'prefix' => '/store',
], function () {
    Route::get('/', [Client\Store\ResourceController::class, 'user'])->name('api:client:store.user');
    Route::get('/costs', [Client\Store\ResourceController::class, 'costs'])->name('api:client:store.costs');
    Route::get('/nodes', [Client\Store\ServerController::class, 'nodes'])->name('api:client:store.nests');
    Route::get('/nests', [Client\Store\ServerController::class, 'nests'])->name('api:client:store.nests');

    Route::group(['prefix' => '/create', 'middleware' => 'throttle:storefront'], function () {
        Route::post('/', [Client\Store\ServerController::class, 'store'])->name('api:client:store.create');
    });

    Route::post('/eggs', [Client\Store\ServerController::class, 'eggs'])->name('api:client:store.eggs');
    Route::post('/stripe', [Client\Store\StripeController::class, 'purchase'])->name('api:client:store.stripe');
    Route::post('/resources', [Client\Store\ResourceController::class, 'purchase'])->name('api:client:store.resources');

    Route::group(['prefix' => '/earn', 'middleware' => 'throttle:earn'], function () {
        Route::post('/', [Client\Store\ResourceController::class, 'earn'])->name('api:client:store.earn');
    });

    Route::group(['prefix' => '/paypal'], function () {
        Route::get('/callback', [Client\Store\PayPalController::class, 'callback'])->name('api:client:store.paypal.callback');
        Route::post('/', [Client\Store\PayPalController::class, 'purchase'])->name('api:client:store.paypal');
    });
});

/*
|--------------------------------------------------------------------------
| Client Control API
|--------------------------------------------------------------------------
|
| Endpoint: /api/client/servers/{server}
|
*/
Route::group([
    'prefix' => '/servers/{server}',
    'middleware' => [
        ServerSubject::class,
        AuthenticateServerAccess::class,
        ResourceBelongsToServer::class,
    ],
], function () {
    Route::get('/', [Client\Servers\ServerController::class, 'index'])->name('api:client:server.view');
    Route::get('/websocket', Client\Servers\WebsocketController::class)->name('api:client:server.ws');
    Route::get('/resources', Client\Servers\ResourceUtilizationController::class)->name('api:client:server.resources');
    Route::get('/activity', Client\Servers\ActivityLogController::class)->name('api:client:server.activity');

    Route::post('/background', [Client\Servers\ServerController::class, 'updateBackground']);
    Route::post('/command', [Client\Servers\CommandController::class, 'index']);
    Route::post('/power', [Client\Servers\PowerController::class, 'index']);

    // Routes for editing, deleting and renewing a server.
    Route::middleware(['throttle:server-edit'])->group(function () {
        Route::post('/edit', [Client\Servers\EditController::class, 'index'])->name('api:client:server.edit');
    });

    Route::middleware(['throttle:storefront'])->group(function () {
        Route::post('/renew', [Client\Servers\RenewalController::class, 'index'])->name('api:client:server.renew');
        Route::post('/delete', [Client\Servers\ServerController::class, 'delete'])->name('api:client:server.delete');
    });

    Route::post('/plugins', [Client\Servers\PluginController::class, 'index'])->name('api:client:server.plugins');
    Route::post('/plugins/install/{id}', [Client\Servers\PluginController::class, 'install'])->name('api:client:server.plugins');

    Route::group(['prefix' => '/analytics'], function () {
        Route::get('/', [Client\Servers\AnalyticsController::class, 'index']);
        Route::get('/messages', [Client\Servers\AnalyticsController::class, 'messages']);
    });

    Route::group(['prefix' => '/databases'], function () {
        Route::get('/', [Client\Servers\DatabaseController::class, 'index']);
        Route::post('/', [Client\Servers\DatabaseController::class, 'store']);
        Route::post('/{database}/rotate-password', [Client\Servers\DatabaseController::class, 'rotatePassword']);
        Route::delete('/{database}', [Client\Servers\DatabaseController::class, 'delete']);
    });

    Route::group(['prefix' => '/files'], function () {
        Route::get('/list', [Client\Servers\FileController::class, 'directory']);
        Route::get('/contents', [Client\Servers\FileController::class, 'contents']);
        Route::get('/download', [Client\Servers\FileController::class, 'download']);
        Route::put('/rename', [Client\Servers\FileController::class, 'rename']);
        Route::post('/copy', [Client\Servers\FileController::class, 'copy']);
        Route::post('/write', [Client\Servers\FileController::class, 'write']);
        Route::post('/compress', [Client\Servers\FileController::class, 'compress']);
        Route::post('/decompress', [Client\Servers\FileController::class, 'decompress']);
        Route::post('/delete', [Client\Servers\FileController::class, 'delete']);
        Route::post('/create-folder', [Client\Servers\FileController::class, 'create']);
        Route::post('/chmod', [Client\Servers\FileController::class, 'chmod']);
        Route::post('/pull', [Client\Servers\FileController::class, 'pull'])->middleware(['throttle:10,5']);
        Route::get('/upload', [Client\Servers\FileUploadController::class, '__invoke']);
    });

    Route::group(['prefix' => '/schedules'], function () {
        Route::get('/', [Client\Servers\ScheduleController::class, 'index']);
        Route::post('/', [Client\Servers\ScheduleController::class, 'store']);
        Route::get('/{schedule}', [Client\Servers\ScheduleController::class, 'view']);
        Route::post('/{schedule}', [Client\Servers\ScheduleController::class, 'update']);
        Route::post('/{schedule}/execute', [Client\Servers\ScheduleController::class, 'execute']);
        Route::delete('/{schedule}', [Client\Servers\ScheduleController::class, 'delete']);

        Route::post('/{schedule}/tasks', [Client\Servers\ScheduleTaskController::class, 'store']);
        Route::post('/{schedule}/tasks/{task}', [Client\Servers\ScheduleTaskController::class, 'update']);
        Route::delete('/{schedule}/tasks/{task}', [Client\Servers\ScheduleTaskController::class, 'delete']);
    });

    Route::group(['prefix' => '/network'], function () {
        Route::get('/allocations', [Client\Servers\NetworkAllocationController::class, 'index']);
        Route::post('/allocations', [Client\Servers\NetworkAllocationController::class, 'store']);
        Route::post('/allocations/{allocation}', [Client\Servers\NetworkAllocationController::class, 'update']);
        Route::post('/allocations/{allocation}/primary', [Client\Servers\NetworkAllocationController::class, 'setPrimary']);
        Route::delete('/allocations/{allocation}', [Client\Servers\NetworkAllocationController::class, 'delete']);
    });

    Route::group(['prefix' => '/users'], function () {
        Route::get('/', [Client\Servers\SubuserController::class, 'index']);
        Route::post('/', [Client\Servers\SubuserController::class, 'store']);
        Route::get('/{user}', [Client\Servers\SubuserController::class, 'view']);
        Route::post('/{user}', [Client\Servers\SubuserController::class, 'update']);
        Route::delete('/{user}', [Client\Servers\SubuserController::class, 'delete']);
    });

    Route::group(['prefix' => '/backups'], function () {
        Route::get('/', [Client\Servers\BackupController::class, 'index']);
        Route::post('/', [Client\Servers\BackupController::class, 'store']);
        Route::get('/{backup}', [Client\Servers\BackupController::class, 'view']);
        Route::get('/{backup}/download', [Client\Servers\BackupController::class, 'download']);
        Route::post('/{backup}/lock', [Client\Servers\BackupController::class, 'toggleLock']);
        Route::post('/{backup}/restore', [Client\Servers\BackupController::class, 'restore']);
        Route::delete('/{backup}', [Client\Servers\BackupController::class, 'delete']);
    });

    Route::group(['prefix' => '/startup'], function () {
        Route::get('/', [Client\Servers\StartupController::class, 'index']);
        Route::put('/variable', [Client\Servers\StartupController::class, 'update']);
    });

    Route::group(['prefix' => '/settings'], function () {
        Route::post('/rename', [Client\Servers\SettingsController::class, 'rename']);
        Route::post('/reinstall', [Client\Servers\SettingsController::class, 'reinstall']);
        Route::put('/docker-image', [Client\Servers\SettingsController::class, 'dockerImage']);
    });
});
