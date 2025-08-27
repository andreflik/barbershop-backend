<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UsuariosAdminController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'per_page' => 'nullable|integer|min:5|max:50',
            'page'     => 'nullable|integer|min:1',
            'q'        => 'nullable|string|max:120',
        ]);

        $perPage = (int)($validated['per_page'] ?? 10);
        $page    = (int)($validated['page'] ?? 1);
        $q       = $validated['q'] ?? null;

        $qb = User::query()
            ->select(['id', 'name', 'email', 'role'])
            ->when($q, function ($qb2) use ($q) {
                $qb2->where(function ($inner) use ($q) {
                    $inner->where('name', 'like', "%{$q}%")
                        ->orWhere('email', 'like', "%{$q}%");
                });
            })
            ->orderBy('name');

        $paginator = $qb->paginate($perPage, ['*'], 'page', $page);

        return response()->json($paginator);
    }

    public function options(): JsonResponse
    {
        return response()->json(
            User::query()->orderBy('name')->get(['id', 'name'])
        );
    }
}
