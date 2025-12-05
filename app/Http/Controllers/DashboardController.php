<?php

namespace App\Http\Controllers;

use App\Models\AgendarCorte;
use App\Services\DashboardService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    protected DashboardService $dashboardService;

    public function __construct(DashboardService $dashboardService)
    {
        $this->dashboardService = $dashboardService;
    }

    /**
     * Estatísticas do usuário logado (dashboard do cliente).
     * - Valida e limita paginação
     * - Sanitiza os agendamentos antes de retornar
     */
    public function estatisticas(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ano'      => 'nullable|integer|min:2000|max:' . (date('Y') + 1),
            'page'     => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:5|max:50',
        ]);

        $ano     = (int)($validated['ano'] ?? date('Y'));
        $page    = (int)($validated['page'] ?? 1);
        $perPage = (int)($validated['per_page'] ?? 10);

        $perPage = max(5, min($perPage, 50));
        $page    = max(1, $page);

        $userId = (int)auth()->id();

        // ⚡ Aqui já vem tudo transformado
        $data = $this->dashboardService->getEstatisticas($userId, $ano, $perPage, $page);

        return response()->json($data);
    }
    /**
     * (Admin) Lista todos agendamentos paginados.
     * - Exige admin (além do middleware de rota)
     * - Pagina e limita per_page
     * - Retorna apenas os campos necessários
     */
    public function todosAgendamentos(Request $request): JsonResponse
    {
        // defesa extra (caso a rota não esteja sob can:admin)
        abort_unless($request->user()?->can('admin'), 403, 'Operação não permitida.');

        $validated = $request->validate([
            'page'     => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:5|max:50',
        ]);

        $page    = (int)($validated['page'] ?? 1);
        $perPage = (int)($validated['per_page'] ?? 20);
        $perPage = max(5, min($perPage, 50));
        $page    = max(1, $page);

        // Carrega apenas as colunas necessárias (evita vazamentos)
        $query = AgendarCorte::query()
            ->select(['id', 'usuario_id', 'servico_id', 'data_agendamento', 'hora_agendamento'])
            ->with([
                'usuario:id,name',
                // aceita 'servico' com nome em 'servico' OU 'nome'
                'servico:id,servico,nome',
            ])
            ->orderByDesc('data_agendamento')
            ->orderBy('hora_agendamento');

        /** @var LengthAwarePaginator $paginator */
        $paginator = $query->paginate($perPage, ['*'], 'page', $page);

        // transforma os itens para um formato seguro/minimalista
        $paginator->getCollection()->transform(function ($item) {
            return $this->mapAgendamento($item);
        });

        return response()->json($paginator);
    }

    /**
     * Normaliza/sanitiza o payload vindo do DashboardService.
     * Mantém compat com formatos como:
     * - ['agendamentos' => paginator]
     * - ['agendamentosDetalhados' => []]
     * - ['data' => []]
     * - paginator direto
     */
    private function sanitizeDashboardPayload($raw)
    {
        // paginator direto
        if ($raw instanceof LengthAwarePaginator) {
            $raw->getCollection()->transform(fn($i) => $this->mapAgendamento($i));
            return $raw;
        }

        // array associativo com possíveis chaves
        if (is_array($raw)) {
            // formato: ['agendamentos' => paginator|array]
            if (isset($raw['agendamentos'])) {
                if ($raw['agendamentos'] instanceof LengthAwarePaginator) {
                    $raw['agendamentos']->getCollection()->transform(fn($i) => $this->mapAgendamento($i));
                    return $raw;
                }
                if (isset($raw['agendamentos']['data']) && is_array($raw['agendamentos']['data'])) {
                    $raw['agendamentos']['data'] = array_map(fn($i) => $this->mapAgendamento($i), $raw['agendamentos']['data']);
                    return $raw;
                }
            }

            // formato: ['agendamentosDetalhados' => array]
            if (isset($raw['agendamentosDetalhados']) && is_array($raw['agendamentosDetalhados'])) {
                $raw['agendamentosDetalhados'] = array_map(fn($i) => $this->mapAgendamento($i), $raw['agendamentosDetalhados']);
                return $raw;
            }

            // formato: ['data' => array] (genérico)
            if (isset($raw['data']) && is_array($raw['data'])) {
                $raw['data'] = array_map(fn($i) => $this->mapAgendamento($i), $raw['data']);
                return $raw;
            }
        }

        // coleção simples
        if ($raw instanceof \Illuminate\Support\Collection) {
            return $raw->map(fn($i) => $this->mapAgendamento($i))->values();
        }

        // fallback: retorna como veio (para não quebrar), mas isso deve ser evitado no service
        return $raw;
    }

    /**
     * Mapeia um item de agendamento (Model ou array) para um formato seguro/minimalista.
     */
    private function mapAgendamento($item): array
    {
        $servicos = [];

        // Garantindo que a relação existe e é uma Collection
        if (!empty($item->servicos) && $item->servicos instanceof \Illuminate\Support\Collection) {
            $servicos = $item->servicos->map(fn($s) => [
                'id'   => $s->id,
                'nome' => $s->servico ?? $s->nome ?? 'Serviço',
            ])->toArray();
        } elseif (!empty($item->servico)) {
            // Compatibilidade com dados antigos (serviço único)
            $servicos[] = [
                'id'   => $item->servico->id ?? null,
                'nome' => $item->servico->servico ?? $item->servico->nome ?? 'Serviço',
            ];
        }

        return [
            'id'               => $item->id,
            'data_agendamento' => $item->data_agendamento,
            'hora_agendamento' => $item->hora_agendamento,
            'servicos'         => $servicos,
        ];
    }
}
