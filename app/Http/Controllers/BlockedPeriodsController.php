<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\BlockedPeriod;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BlockedPeriodsController extends Controller
{
    public function index(): JsonResponse
    {
        $items = BlockedPeriod::orderBy('data')
            ->orderBy('hora_inicio')
            ->get([
                'id',
                'data',
                'hora_inicio',
                'hora_fim',
                'motivo',
                'created_by',
                'created_at'
            ]);

        return response()->json([
            'data' => $items
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'data'         => ['required', 'date_format:Y-m-d'],
            'hora_inicio'  => ['nullable', 'date_format:H:i'],
            'hora_fim'     => ['nullable', 'date_format:H:i', 'after:hora_inicio'],
            'motivo'       => ['required', 'string', 'max:255'],
        ]);

        if (
            empty($validated['hora_inicio']) ||
            empty($validated['hora_fim'])
        ) {
            $validated['hora_inicio'] = null;
            $validated['hora_fim'] = null;
        }

        $blocked = BlockedPeriod::create([
            'data'        => $validated['data'],
            'hora_inicio' => $validated['hora_inicio'],
            'hora_fim'    => $validated['hora_fim'],
            'motivo'      => $validated['motivo'],
            'created_by'  => Auth::id(),
        ]);

        return response()->json([
            'message' => 'Período bloqueado com sucesso.',
            'data'    => $blocked
        ], 201);
    }

    public function destroy(int $id): JsonResponse
    {
        $item = BlockedPeriod::findOrFail($id);
        $item->delete();

        return response()->json([
            'message' => 'Bloqueio removido com sucesso.'
        ]);
    }
}
