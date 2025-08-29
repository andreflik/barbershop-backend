<?php

namespace App\Http\Controllers;

use App\Models\Servico;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Database\QueryException;

class ServicosController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'q'        => 'nullable|string|max:120',
            'per_page' => 'nullable|integer|min:5|max:50',
            'page'     => 'nullable|integer|min:1',
        ]);

        $q        = $validated['q'] ?? null;
        $perPage  = (int)($validated['per_page'] ?? 10);
        $page     = (int)($validated['page'] ?? 1);

        $qb = Servico::query()
            ->select(['id', 'codigo', 'servico', 'preco'])
            ->when($q, function ($qb2) use ($q) {
                $qb2->where(function ($inner) use ($q) {
                    $inner->where('servico', 'like', "%{$q}%")
                        ->orWhere('codigo', 'like', "%{$q}%");
                });
            })
            ->orderBy('servico');

        $paginator = $qb->paginate($perPage, ['*'], 'page', $page);

        return response()->json($paginator);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'codigo'  => 'required|string|max:30|unique:servicos,codigo',
            'servico' => 'required|string|max:120',
            'preco'   => 'required|numeric|min:0|max:999999.99',
        ]);

        $servico = Servico::create($data);

        return response()->json([
            'id'      => $servico->id,
            'codigo'  => $servico->codigo,
            'servico' => $servico->servico,
            'preco'   => $servico->preco,
        ], 201);
    }

    public function update(int $id, Request $request): JsonResponse
    {
        $servico = Servico::findOrFail($id);

        $data = $request->validate([
            'codigo'  => 'required|string|max:30|unique:servicos,codigo,' . $servico->id,
            'servico' => 'required|string|max:120',
            'preco'   => 'required|numeric|min:0|max:999999.99',
        ]);

        $servico->update($data);

        return response()->json([
            'id'      => $servico->id,
            'codigo'  => $servico->codigo,
            'servico' => $servico->servico,
            'preco'   => $servico->preco,
        ]);
    }

    public function options(): JsonResponse
    {
        try {
            return response()->json(
                Servico::query()->orderBy('servico')->get(['id', 'servico'])
            );
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Erro ao carregar opções'], 500);
        }
    }

    public function destroy(int $id): JsonResponse
    {
        $servico = Servico::findOrFail($id);

        try {
            $servico->delete();
            return response()->json(['message' => 'Serviço excluído']);
        } catch (QueryException $e) {
            if ((string)$e->getCode() === '23000') {
                return response()->json([
                    'message' => 'Não é possível excluir: serviço em uso em agendamentos.'
                ], 409);
            }
            return response()->json(['message' => 'Erro ao excluir serviço'], 500);
        }
    }


    public function publicList(): JsonResponse
    {
        $itens = Servico::query()
            ->orderBy('servico')
            ->get(['id', 'servico', 'preco'])
            ->map(function ($s) {
                return [
                    'id'      => $s->id,
                    'servico' => $s->servico,
                    'preco'   => (float) $s->preco, // força número
                ];
            });

        return response()
            ->json($itens)
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
    }

}
