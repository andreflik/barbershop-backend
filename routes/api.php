<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\GoogleController;
use App\Http\Controllers\CalendarController;
use App\Http\Controllers\AgendaCortesController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\AgendamentosAdminController;
use App\Http\Controllers\ServicosController;
use App\Http\Controllers\UsuariosAdminController;
use Illuminate\Support\Facades\Mail;

// --------- Público / Auth ----------
Route::post('/login',    [AuthController::class, 'login'])->name('login');
Route::post('/logout',   [AuthController::class, 'logout'])->middleware('auth:sanctum');
Route::post('/register', [AuthController::class, 'register']);

Route::get('google/redirect', [GoogleController::class, 'redirectToGoogle'])->name('google.redirect');
Route::get('google/callback', [GoogleController::class, 'handleGoogleCallback'])->name('google.callback');

// --------- Usuário logado ----------
Route::middleware('auth:sanctum')->group(function () {
Route::get('user/name', [GoogleController::class, 'getUsername']);

// Agendar corte (usuário)
Route::get('agendar-corte/servicos', [AgendaCortesController::class, 'listarServicos']);
Route::get('agendar-corte/{data}',   [AgendaCortesController::class, 'getBookedTimes']);
Route::post('agendar-corte',         [AgendaCortesController::class, 'salvarAgendamento']);
Route::delete('agendar-corte/{id}',  [AgendaCortesController::class, 'excluirAgendamento']);

// Dashboard geral
Route::get('dashboard/estatisticas/{ano?}', [DashboardController::class, 'estatisticas']);

// Calendar
Route::post('calendar/create', [CalendarController::class, 'createEvent'])->name('calendar.create');
Route::get('calendar/list',    [CalendarController::class, 'listEvents'])->name('calendar.list');
});

// --------- Admin (auth + admin) ----------
Route::middleware(['auth:sanctum','admin'])->prefix('admin')->group(function () {

// Dashboard/admin
Route::get('dashboard', [DashboardController::class, 'estatisticas']);

// Agendamentos (admin)
Route::get('agendamentos',          [AgendamentosAdminController::class, 'index']);
Route::post('agendamentos',         [AgendamentosAdminController::class, 'store']);
Route::delete('agendamentos/{id}',  [AgendamentosAdminController::class, 'cancelar']);

Route::get('agendamentos/export/xlsx', [AgendamentosAdminController::class, 'exportXlsx']);
Route::get('agendamentos/export/pdf',  [AgendamentosAdminController::class, 'exportPdf']);

// Serviços (admin)
Route::get('servicos',          [ServicosController::class, 'index']);
Route::post('servicos',         [ServicosController::class, 'store']);
Route::put('servicos/{id}',     [ServicosController::class, 'update']);
Route::delete('servicos/{id}',  [ServicosController::class, 'destroy']);
Route::get('servicos/options',  [ServicosController::class, 'options']); // <- usado pelos filtros

// Usuários (admin)
Route::get('usuarios',         [UsuariosAdminController::class, 'index']);
Route::get('usuarios/options', [UsuariosAdminController::class, 'options']);
});

// Público (sem auth): opções de serviços para página de contato, se quiser
Route::get('/servicos-publicos', [ServicosController::class, 'options']);



Route::get('/_mail/test', function () {
    try {
        Mail::raw('Teste OK do SMTP (Koyeb).', function ($m) {
            $m->to('seu-email-teste@outlook.com');
            $m->from(config('mail.from.address'), config('mail.from.name'));
            $m->subject('Teste SMTP • BarberShop');
        });
        return response()->json(['ok' => true]);
    } catch (\Throwable $e) {
        return response()->json(['ok' => false, 'err' => $e->getMessage()], 500);
    }
});

