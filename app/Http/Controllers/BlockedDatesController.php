<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BlockedDate;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class BlockedDatesController extends Controller
{

    public function index(): JsonResponse
    {
        $items = BlockedDate::query()
            ->orderBy('data')
            ->get()
            ->map(fn($b) => [
                'id'     => $b->id,
                'data'   => $b->data->format('Y-m-d'),
                'motivo' => $b->motivo,
            ]);

        return response()->json([
            'data' => $items,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'data'   => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'motivo' => ['required', 'string', 'max:255'],
        ]);

        try {
            $exists = BlockedDate::whereDate('data', $validated['data'])->exists();
            if ($exists) {
                return response()->json([
                    'message' => 'Essa data já está bloqueada.',
                ], 409);
            }

            $blocked = BlockedDate::create([
                'data'       => $validated['data'],
                'motivo'     => $validated['motivo'],
                'created_by' => Auth::id(),
            ]);

            return response()->json([
                'message' => 'Data bloqueada com sucesso.',
                'data'    => [
                    'id'     => $blocked->id,
                    'data'   => $blocked->data->format('Y-m-d'),
                    'motivo' => $blocked->motivo,
                ],
            ], 201);
        } catch (\Throwable $e) {
            Log::error('admin.blocked_dates.store', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Erro ao bloquear a data.',
            ], 500);
        }
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            $blocked = BlockedDate::findOrFail($id);
            $blocked->delete();

            return response()->json([
                'message' => 'Bloqueio removido com sucesso.',
            ]);
        } catch (\Throwable $e) {
            Log::error('admin.blocked_dates.destroy', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Erro ao remover bloqueio.',
            ], 500);
        }
    }
}
