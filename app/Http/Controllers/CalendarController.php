<?php

namespace App\Http\Controllers;

use App\Models\AgendarCorte;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class CalendarController extends Controller
{
    public function salvarAgendamento(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user) {
            \Log::warning('salvarAgendamento: usuário não autenticado');
            return response()->json(['error' => 'Usuário não autenticado'], 401);
        }

        $validated = $request->validate([
            'data_agendamento' => 'required|date|after_or_equal:today',
            'hora_agendamento' => 'required|date_format:H:i',
            'observacao'       => 'nullable|string|max:255',
        ]);

        try {
            $agendamento = new AgendarCorte();
            $agendamento->usuario_id       = $user->id;
            $agendamento->data_agendamento = $validated['data_agendamento'];
            $agendamento->hora_agendamento = $validated['hora_agendamento'];
            $agendamento->save();

            if (app()->hasDebugModeEnabled()) {
                \Log::info('Agendamento criado', ['id' => $agendamento->id, 'user' => $user->id]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Agendamento salvo com sucesso!',
            ], 201);
        } catch (\Throwable $e) {
            \Log::error('Erro ao salvar agendamento', ['type' => get_class($e)]);
            return response()->json(['error' => 'Erro ao salvar agendamento.'], 500);
        }
    }

    public function getBookedTimes(string $data): JsonResponse
    {
        // valida formato YYYY-mm-dd rapidamente
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $data)) {
            return response()->json(['message' => 'Data inválida.'], 422);
        }

        $bookedTimes = AgendarCorte::where('data_agendamento', $data)
            ->pluck('hora_agendamento')
            ->values();

        return response()->json(['bookedTimes' => $bookedTimes]);
    }
}
