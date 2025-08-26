<?php


namespace App\Http\Controllers;


use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;


class UsuariosAdminController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = (int)($request->query('per_page', 10));
        $q = $request->query('q');
        $users = User::query()
            ->when($q, fn($qb) => $qb->where('name', 'like', "%{$q}%")
                ->orWhere('email', 'like', "%{$q}%"))
            ->orderBy('name')
            ->paginate($perPage, ['id', 'name', 'email', 'role']);
        return response()->json($users);
    }

    public function options(): JsonResponse
    {
        return response()->json(
            User::query()->orderBy('name')->get(['id','name'])
        );
    }

}
