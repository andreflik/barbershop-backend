<?php

namespace App\Services;

use App\Repositories\DashboardRepository;
use Illuminate\Support\Facades\Auth;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class DashboardService
{
    public function __construct(
        protected DashboardRepository $repository
    ) {}

    /**
     * Estatísticas do usuário para um ano específico.
     * - Usa apenas os métodos novos do repositório:
     *   - countAgendamentosPorMes(int $userId, int $ano): array 1..12
     *   - getAgendamentosDetalhadosPorAno(int $userId, int $ano, int $perPage, int $page): paginator
     * - Sanitiza os itens antes de retornar ao controller.
     */
    public function getEstatisticas(int $userId, int $ano, int $perPage, int $page): array
    {
        $agregPorMes = $this->repository->countAgendamentosPorMes($userId, $ano);

        /** @var LengthAwarePaginator $paginator */
        $paginator = $this->repository->getAgendamentosDetalhadosPorAno($userId, $ano, $perPage, $page);

        // Sanitize items (somente campos necessários)
        $paginator->getCollection()->transform(fn ($i) => $this->mapAgendamento($i));

        return [
            'agendamentosPorMes'     => $agregPorMes,
            'agendamentosDetalhados' => $paginator->items(),
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page'    => $paginator->lastPage(),
                'per_page'     => $paginator->perPage(),
                'total'        => $paginator->total(),
            ],
        ];
    }

    /**
     * Mantém compat: retorna paginator já saneado para o ano informado.
     */
    public function getEstatisticasPaginadas(int $ano, int $perPage = 10): LengthAwarePaginator
    {
        $userId = (int) Auth::id();

        /** @var LengthAwarePaginator $paginator */
        $paginator = $this->repository->getAgendamentosDetalhadosPorAno($userId, $ano, $perPage, page: 1);

        $paginator->getCollection()->transform(fn ($i) => $this->mapAgendamento($i));

        return $paginator;
    }

    /**
     * Normaliza/sanitiza um item de agendamento (Model ou array).
     */
    private function mapAgendamento($item): array
    {
        $get = function ($obj, $key, $default = null) {
            if (is_array($obj)) return $obj[$key] ?? $default;
            return $obj->{$key} ?? $default;
        };

        $serv     = $get($item, 'servico');
        $servId   = $get($item, 'servico_id');
        $servNome = null;

        if (is_array($serv)) {
            $servNome = $serv['nome'] ?? ($serv['servico'] ?? null);
            $servId   = $serv['id'] ?? $servId;
        } elseif (is_object($serv)) {
            $servNome = $serv->nome ?? ($serv->servico ?? null);
            $servId   = $serv->id ?? $servId;
        }

        return [
            'id'               => $get($item, 'id'),
            'data_agendamento' => $get($item, 'data_agendamento'),
            'hora_agendamento' => $get($item, 'hora_agendamento'),
            'servico'          => [
                'id'   => $servId,
                'nome' => $servNome,
            ],
        ];
    }
}
