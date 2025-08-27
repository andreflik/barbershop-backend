<?php

namespace App\Http\Controllers;

use App\Models\AgendarCorte;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\AgendamentosExport;
use Barryvdh\DomPDF\Facade\Pdf;

class AgendamentosAdminController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'per_page'   => 'nullable|integer|min:5|max:50',
            'page'       => 'nullable|integer|min:1',
            'servico_id' => 'nullable|integer|min:1',
            'usuario_id' => 'nullable|integer|min:1',
            'mes'        => 'nullable|integer|min:1|max:12',
            'ano'        => 'nullable|integer|min:2000|max:' . (date('Y') + 1),
            'q'          => 'nullable|string|max:120',
        ]);

        $perPage = (int)($validated['per_page'] ?? 10);
        $page    = (int)($validated['page'] ?? 1);

        $qb = AgendarCorte::query()
            ->select(['id','usuario_id','servico_id','data_agendamento','hora_agendamento'])
            ->with([
                'usuario:id,name',
                // 🔧 SOMENTE colunas existentes em `servicos`
                'servico:id,servico',
            ])
            ->orderByDesc('data_agendamento')
            ->orderBy('hora_agendamento');

        $this->applyFilters($qb, $validated);

        try {
            $paginator = $qb->paginate($perPage, ['*'], 'page', $page);

            // Normaliza itens (entrega "nome" derivado de servico)
            $paginator->getCollection()->transform(fn ($i) => $this->mapAgendamentoAdmin($i));

            return response()->json($paginator);
        } catch (\Throwable $e) {
            Log::error('admin.agendamentos.index', ['err' => $e->getMessage()]);
            return response()->json(['message' => 'Falha ao listar agendamentos.'], 500);
        }
    }

    public function exportXlsx(Request $request)
    {
        $validated = $request->validate([
            'servico_id' => 'nullable|integer|min:1',
            'usuario_id' => 'nullable|integer|min:1',
            'mes'        => 'nullable|integer|min:1|max:12',
            'ano'        => 'nullable|integer|min:2000|max:' . (date('Y') + 1),
            'q'          => 'nullable|string|max:120',
        ]);

        $limit = (int) env('ADMIN_EXPORT_LIMIT', 5000);
        return Excel::download(new AgendamentosExport($validated, $limit), 'agendamentos.xlsx');
    }

    public function exportPdf(Request $request)
    {
        $validated = $request->validate([
            'servico_id' => 'nullable|integer|min:1',
            'usuario_id' => 'nullable|integer|min:1',
            'mes'        => 'nullable|integer|min:1|max:12',
            'ano'        => 'nullable|integer|min:2000|max:' . (date('Y') + 1),
            'q'          => 'nullable|string|max:120',
        ]);

        $limit = (int) env('ADMIN_EXPORT_LIMIT', 5000);

        $qb = AgendarCorte::query()
            ->select(['id','usuario_id','servico_id','data_agendamento','hora_agendamento'])
            ->with(['usuario:id,name', 'servico:id,servico'])
            ->orderByDesc('data_agendamento')
            ->orderBy('hora_agendamento');

        $this->applyFilters($qb, $validated);

        $rows = $qb->limit($limit)->get()
            ->map(fn ($i) => $this->mapAgendamentoAdmin($i));

        $pdf = Pdf::loadView('exports.agendamentos', [
            'rows'    => $rows,
            'filtros' => $validated,
        ])->setPaper('a4', 'portrait');

        return $pdf->download('agendamentos.pdf');
    }

    private function applyFilters($qb, array $params): void
    {
        if (!empty($params['servico_id'])) {
            $qb->where('servico_id', (int) $params['servico_id']);
        }
        if (!empty($params['usuario_id'])) {
            $qb->where('usuario_id', (int) $params['usuario_id']);
        }
        if (!empty($params['mes'])) {
            $qb->whereMonth('data_agendamento', (int) $params['mes']);
        }
        if (!empty($params['ano'])) {
            $qb->whereYear('data_agendamento', (int) $params['ano']);
        }
        if (!empty($params['q'])) {
            $q = $params['q'];
            $qb->where(function ($inner) use ($q) {
                $inner->whereHas('usuario', fn($u) => $u->where('name', 'like', "%{$q}%"))
                    // 🔧 procura só em `servico` (coluna existente)
                    ->orWhereHas('servico', fn($s) => $s->where('servico', 'like', "%{$q}%"));
            });
        }
    }

    private function mapAgendamentoAdmin($item): array
    {
        $get = function ($obj, $key, $default = null) {
            if (is_array($obj)) return $obj[$key] ?? $default;
            return $obj->{$key} ?? $default;
        };

        $usuario = $get($item, 'usuario');
        $servico = $get($item, 'servico');

        return [
            'id'               => $get($item, 'id'),
            'data_agendamento' => (string) $get($item, 'data_agendamento'),
            'hora_agendamento' => (string) $get($item, 'hora_agendamento'),
            'usuario'          => [
                'id'   => is_array($usuario) ? ($usuario['id'] ?? null) : ($usuario->id ?? null),
                'name' => is_array($usuario) ? ($usuario['name'] ?? null) : ($usuario->name ?? null),
            ],
            'servico'          => [
                'id'   => is_array($servico) ? ($servico['id'] ?? null) : ($servico->id ?? null),
                // entregamos "nome" derivado do campo `servico`
                'nome' => is_array($servico)
                    ? ($servico['servico'] ?? null)
                    : ($servico->servico ?? null),
            ],
        ];
    }
}
