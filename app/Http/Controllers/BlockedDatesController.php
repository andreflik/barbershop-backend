<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BlockedDate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\JsonResponse;

class BlockedDatesController extends Controller
{
    public function publicIndex(): JsonResponse
    {
        return response()->json([
            'blocked_dates' => BlockedDate::query()
                ->orderBy('data')
                ->get(['id', 'data', 'motivo'])
        ]);
    }

    public function index(): JsonResponse
    {
        return response()->json(
            BlockedDate::with('creator:id,name')
                ->orderBy('data')
                ->get()
        );
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'data'   => ['required', 'date_format:Y-m-d', 'unique:blocked_dates,data'],
            'motivo' => ['required', 'string', 'max:255'],
        ]);

        $blocked = BlockedDate::create([
            'data'       => $data['data'],
            'motivo'     => $data['motivo'],
            'created_by' => Auth::id(),
        ]);

        return response()->json($blocked, 201);
    }

    public function destroy(int $id)
    {
        BlockedDate::findOrFail($id)->delete();

        return response()->json(['message' => 'Bloqueio removido']);
    }
}
