<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

use App\Http\Controllers\AuthController;
use App\Http\Controllers\GoogleController;
use App\Http\Controllers\CalendarController;
use App\Http\Controllers\AgendaCortesController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\AgendamentosAdminController;
use App\Http\Controllers\ServicosController;
use App\Http\Controllers\UsuariosAdminController;

/*
|--------------------------------------------------------------------------
| Público / Auth
|--------------------------------------------------------------------------
| - Login e OAuth com rate limit específico (anti força-bruta)
| - Logout protegido por auth
*/

Route::post('/login', [AuthController::class, 'login'])
    ->middleware('throttle:login')
    ->name('login');

Route::post('/register', [AuthController::class, 'register'])
    ->middleware('throttle:5,1'); // evita spam de criação de contas

Route::post('/logout', [AuthController::class, 'logout'])
    ->middleware(['auth:sanctum', 'throttle:api']);

Route::get('google/redirect', [GoogleController::class, 'redirectToGoogle'])
    ->middleware('throttle:login')
    ->name('google.redirect');

Route::get('google/callback', [GoogleController::class, 'handleGoogleCallback'])
    ->middleware('throttle:login')
    ->name('google.callback');

/*
|--------------------------------------------------------------------------
| Público (sem auth)
|--------------------------------------------------------------------------
*/

Route::get('/servicos-publicos', [ServicosController::class, 'options'])
    ->middleware('throttle:api');

/*
|--------------------------------------------------------------------------
| Usuário logado
|--------------------------------------------------------------------------
*/

Route::middleware(['auth:sanctum', 'throttle:api'])->group(function () {
    // Perfil simples
    Route::get('user/name', [GoogleController::class, 'getUsername']);

    // Agendar corte (usuário)
    Route::get('agendar-corte/servicos', [AgendaCortesController::class, 'listarServicos']);

    // Booked times para data YYYY-MM-DD (evita colisão com /{id})
    Route::get('agendar-corte/{data}', [AgendaCortesController::class, 'getBookedTimes'])
        ->where('data', '^\d{4}-\d{2}-\d{2}$');

    Route::post('agendar-corte', [AgendaCortesController::class, 'salvarAgendamento'])
        ->middleware('throttle:30,1');

    Route::delete('agendar-corte/{id}', [AgendaCortesController::class, 'excluirAgendamento'])
        ->whereNumber('id')
        ->middleware('throttle:30,1');

    // Dashboard geral (paginado)
    Route::get('dashboard/estatisticas/{ano?}', [DashboardController::class, 'estatisticas'])
        ->where('ano', '^\d{4}$');

    // Calendar (se estiver usando esses endpoints)
    Route::post('calendar/create', [CalendarController::class, 'createEvent'])
        ->name('calendar.create')
        ->middleware('throttle:30,1');

    Route::get('calendar/list', [CalendarController::class, 'listEvents'])
        ->name('calendar.list');
});

/*
|--------------------------------------------------------------------------
| Admin (auth + middleware admin)
|--------------------------------------------------------------------------
| - Substitui can:admin por admin (EnsureUserIsAdmin)
| - Rate limit geral e limites mais rígidos para exportações/escrita
*/

Route::middleware(['auth:sanctum', 'admin', 'throttle:api'])
    ->prefix('admin')
    ->group(function () {

        // (Opcional) ping para diagnóstico rápido
        // Route::get('ping', fn() => response()->json(['ok' => true]));

        // Dashboard/admin
        Route::get('dashboard', [DashboardController::class, 'estatisticas']);

        // Agendamentos (admin)
        Route::get('agendamentos', [AgendamentosAdminController::class, 'index']);
        Route::post('agendamentos', [AgendamentosAdminController::class, 'store'])
            ->middleware('throttle:30,1');
        Route::delete('agendamentos/{id}', [AgendamentosAdminController::class, 'cancelar'])
            ->whereNumber('id')
            ->middleware('throttle:30,1');

        // Exports (custosos)
        Route::get('agendamentos/export/xlsx', [AgendamentosAdminController::class, 'exportXlsx'])
            ->middleware('throttle:5,1');
        Route::get('agendamentos/export/pdf', [AgendamentosAdminController::class, 'exportPdf'])
            ->middleware('throttle:5,1');

        // Serviços (admin)
        Route::get('servicos', [ServicosController::class, 'index']);
        Route::post('servicos', [ServicosController::class, 'store'])
            ->middleware('throttle:30,1');
        Route::put('servicos/{id}', [ServicosController::class, 'update'])
            ->whereNumber('id')
            ->middleware('throttle:30,1');
        Route::delete('servicos/{id}', [ServicosController::class, 'destroy'])
            ->whereNumber('id')
            ->middleware('throttle:30,1');
        Route::get('servicos/options', [ServicosController::class, 'options']);

        // Usuários (admin)
        Route::get('usuarios', [UsuariosAdminController::class, 'index']);
        Route::get('usuarios/options', [UsuariosAdminController::class, 'options']);
    });

/*
|--------------------------------------------------------------------------
| Diagnóstico simples do usuário logado
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->get('/me', function (Request $r) {
    $u = $r->user();
    return response()->json([
        'id'    => $u?->id,
        'name'  => $u?->name,
        'email' => $u?->email,
        'role'  => $u?->role,
    ]);
});
