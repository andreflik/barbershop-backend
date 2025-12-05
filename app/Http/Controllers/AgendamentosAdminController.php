<?php

namespace App\Http\Controllers;

use App\Models\AgendarCorte;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\AgendamentosExport;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;

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

        $hoje = Carbon::now('America/Sao_Paulo')->toDateString();

        $qb = AgendarCorte::query()
            ->select(['id', 'usuario_id', 'data_agendamento', 'hora_agendamento'])
            ->with([
                'usuario:id,name',
                'servicos:id,servico',
            ])
            ->orderByRaw("CASE WHEN data_agendamento = ? THEN 0 ELSE 1 END", [$hoje])
            ->orderBy('data_agendamento', 'asc')
            ->orderBy('hora_agendamento', 'asc');

        $this->applyFilters($qb, $validated);

        try {
            $paginator = $qb->paginate($perPage, ['*'], 'page', $page);

            $paginator->getCollection()->transform(fn($i) => $this->mapAgendamentoAdmin($i));

            return response()->json($paginator);
        } catch (\Throwable $e) {
            Log::error('admin.agendamentos.index', [
                'err' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
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
            ->select(['id', 'usuario_id', 'data_agendamento', 'hora_agendamento'])
            ->with(['usuario:id,name', 'servicos:id,servico'])
            ->orderByDesc('data_agendamento')
            ->orderBy('hora_agendamento', 'asc');

        $this->applyFilters($qb, $validated);

        $rows = $qb->limit($limit)->get()
            ->map(fn($i) => $this->mapAgendamentoAdmin($i));

        $pdf = Pdf::loadView('exports.agendamentos', [
            'rows'    => $rows,
            'filtros' => $validated,
        ])->setPaper('a4', 'portrait');

        return $pdf->download('agendamentos.pdf');
    }

    private function applyFilters($qb, array $params): void
    {
        if (!empty($params['servico_id'])) {
            $qb->whereHas('servicos', fn($s) => $s->where('servico_id', (int) $params['servico_id']));
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
            $qb->where(function ($inner) use ($params) {
                $inner
                    ->whereHas('usuario', fn($u) => $u->where('name', 'like', "%{$params['q']}%"))
                    ->orWhereHas(
                        'servicos',
                        fn($s) =>
                        $s->where('servico', 'like', "%{$params['q']}%")
                    );
            });
        }
    }

    private function mapAgendamentoAdmin($item): array
    {
        return [
            'id'               => $item->id,
            'data_agendamento' => Carbon::parse($item->data_agendamento)
                ->timezone('America/Sao_Paulo')
                ->format('Y-m-d'),
            'hora_agendamento' => (string)$item->hora_agendamento,
            'usuario'          => [
                'id'   => $item->usuario->id,
                'name' => $item->usuario->name,
            ],
            'servicos'         => $item->servicos
                ->sortBy('servico')
                ->map(fn($s) => [
                    'id'   => $s->id,
                    'nome' => $s->servico
                ])
                ->values()
                ->toArray(),
        ];
    }
}
