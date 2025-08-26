<?php

use App\Http\Controllers\AgendamentosAdminController;
use App\Http\Controllers\ServicosController;
use App\Http\Controllers\UsuariosAdminController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\GoogleController;
use App\Http\Controllers\CalendarController;
use App\Http\Controllers\AgendaCortesController;
use App\Http\Controllers\DashboardController;


// Autenticação
Route::post('/login', [AuthController::class, 'login'])->name('login');
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
Route::post('/register', [AuthController::class, 'register']);

// Google Auth
Route::get('google/redirect', [GoogleController::class, 'redirectToGoogle'])->name('google.redirect');
Route::get('google/callback', [GoogleController::class, 'handleGoogleCallback'])->name('google.callback');

// Eventos Google Calendar
Route::post('calendar/create', [CalendarController::class, 'createEvent'])->name('calendar.create');
Route::middleware('auth:sanctum')->get('calendar/list', [CalendarController::class, 'listEvents'])->name('calendar.list');

// Verifica usuário autenticado
Route::middleware('auth:sanctum')->get('test-auth', fn () => auth()->user());
Route::middleware('auth:sanctum')->get('/user/name', [GoogleController::class, 'getUsername']);

// Rotas para agendamentos (usuário autenticado)
Route::middleware('auth:sanctum')->group(function () {
    Route::get('agendar-corte/servicos', [AgendaCortesController::class, 'listarServicos']);
    Route::get('agendar-corte/{data}', [AgendaCortesController::class, 'getBookedTimes']);
    Route::post('agendar-corte', [AgendaCortesController::class, 'salvarAgendamento']);
    Route::delete('agendar-corte/{id}', [AgendaCortesController::class, 'excluirAgendamento']);
});

Route::middleware(['auth:sanctum', 'admin'])->group(function () {
    Route::get('/admin/dashboard', [DashboardController::class, 'estatisticas']);
    Route::get('/admin/agendamentos', [DashboardController::class, 'todosAgendamentos']);

    Route::get('/admin/agendamentos/export/xlsx', [AgendamentosAdminController::class, 'exportXlsx']);
    Route::get('/admin/agendamentos/export/pdf',  [AgendamentosAdminController::class, 'exportPdf']);


    Route::get('/admin/servicos/options', [ServicosController::class, 'options']);
    Route::get('/admin/usuarios/options', [UsuariosAdminController::class, 'options']);
});;

// Dashboard geral
Route::middleware('auth:sanctum')->get('/dashboard/estatisticas', [DashboardController::class, 'estatisticas']);
Route::middleware('auth:sanctum')->get('/dashboard/estatisticas/{ano?}', [DashboardController::class, 'estatisticas']);

Route::middleware(['auth:sanctum', 'admin'])->group(callback: function () {
    Route::get('/admin/servicos', [ServicosController::class, 'index']);
    Route::post('/admin/servicos', [ServicosController::class, 'store']);
    Route::put('/admin/servicos/{id}', [ServicosController::class, 'update']);
    Route::delete('/admin/servicos/{id}', [ServicosController::class, 'destroy']);

    Route::get('/admin/agendamentos', [AgendamentosAdminController::class, 'index']);
    Route::post('/admin/agendamentos', [AgendamentosAdminController::class, 'store']);
    Route::delete('/admin/agendamentos/{id}', [AgendamentosAdminController::class, 'cancelar']);

    Route::get('/admin/usuarios', [UsuariosAdminController::class, 'index']);
    Route::get('/servicos-publicos', [ServicosController::class, 'options']);
});
