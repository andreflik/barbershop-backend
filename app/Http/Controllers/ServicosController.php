<?php

namespace App\Http\Controllers;


use App\Models\Servico;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;


class ServicosController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $q = $request->query('q');
        $perPage = (int)($request->query('per_page', 10));

        $servicos = Servico::query()
            ->when($q, fn($qb) => $qb->where(function ($q2) use ($q) {
                $q2->where('servico', 'like', "%{$q}%")
                    ->orWhere('codigo', 'like', "%{$q}%");
            }))
            ->orderBy('servico')
            ->paginate($perPage);

        return response()->json($servicos);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'codigo'  => 'required|string|max:30|unique:servicos,codigo',
            'servico' => 'required|string|max:120',
            'preco'   => 'required|numeric|min:0|max:999999.99',
        ]);

        $servico = Servico::create($data);
        return response()->json($servico, 201);
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
        return response()->json($servico);
    }

    public function options()
    {
        return response()->json(
            Servico::orderBy('servico')->get(['id','servico','preco'])
        );
    }


    public function destroy(int $id): JsonResponse
    {
        $servico = Servico::findOrFail($id);
        $servico->delete();
        return response()->json(['message' => 'Serviço excluído']);
    }
}

