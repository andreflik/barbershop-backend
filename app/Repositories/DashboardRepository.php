<?php

namespace App\Repositories;

use App\Models\AgendarCorte;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class DashboardRepository
{
    /**
     * ✅ Novo: contagem de agendamentos por mês PARA UM USUÁRIO e ANO.
     * Retorna array 1..12 com inteiros (meses sem dados = 0).
     */
    public function countAgendamentosPorMes(int $userId, int $ano): array
    {
        $rows = AgendarCorte::query()
            ->selectRaw('MONTH(data_agendamento) AS mes, COUNT(*) AS total')
            ->where('usuario_id', $userId)
            ->whereYear('data_agendamento', $ano)
            ->groupByRaw('MONTH(data_agendamento)')
            ->orderByRaw('MONTH(data_agendamento)')
            ->get();

        // normaliza 1..12
        $out = array_fill(1, 12, 0);
        foreach ($rows as $r) {
            $m = (int) ($r->mes ?? 0);
            if ($m >= 1 && $m <= 12) {
                $out[$m] = (int) $r->total;
            }
        }
        return $out;
    }

    /**
     * ✅ Novo: detalhes paginados PARA UM USUÁRIO e ANO.
     * Seleciona só campos necessários e carrega servico (id, nome/servico).
     */
    public function getAgendamentosDetalhadosPorAno(
        int $userId,
        int $ano,
        int $perPage = 10,
        int $page = 1
    ): LengthAwarePaginator {
        return AgendarCorte::query()
            ->select(['id', 'usuario_id', 'data_agendamento', 'hora_agendamento'])
            ->with(['servicos:id,servico']) // 👈 Troca para servicos
            ->where('usuario_id', $userId)
            ->whereYear('data_agendamento', $ano)
            ->orderByDesc('data_agendamento')
            ->orderBy('hora_agendamento')
            ->paginate($perPage, ['*'], 'page', $page);
    }

    // ------------------------------------------------------------------------------------
    // 🔧 Back-compat helpers (se algo antigo no código ainda chamar os métodos abaixo)
    // ------------------------------------------------------------------------------------

    /**
     * (Compat) Versão antiga S/ userId — NÃO usar em código novo.
     * Mantida para evitar break; agora filtra por ANO apenas (global).
     */
    public function getAgendamentosPorMes(int $ano): Collection
    {
        return AgendarCorte::query()
            ->selectRaw('MONTH(data_agendamento) as mes, COUNT(*) as total')
            ->whereYear('data_agendamento', $ano)
            ->groupByRaw('MONTH(data_agendamento)')
            ->orderByRaw('MONTH(data_agendamento)')
            ->get();
    }

    /**
     * (Compat) Versão antiga que já mapeava saída — evite em código novo.
     * O Service atual mapeia/sanitiza os itens.
     */
    public function getAgendamentosDetalhados(int $ano): Collection
    {
        return AgendarCorte::query()
            ->select(['id', 'usuario_id', 'data_agendamento', 'hora_agendamento'])
            ->with(['servicos:id,servico']) // 👈 AQUI também
            ->whereYear('data_agendamento', $ano)
            ->where('usuario_id', auth()->id())
            ->orderByDesc('data_agendamento')
            ->orderBy('hora_agendamento')
            ->get()
            ->map(function ($agendamento) {
                // Correção: agora retorna uma lista de serviços
                $servicos = $agendamento->servicos?->map(fn($s) => [
                    'id'   => $s->id,
                    'nome' => $s->servico ?? $s->nome,
                ])->toArray() ?? [];

                return [
                    'id'               => $agendamento->id,
                    'data_agendamento' => $agendamento->data_agendamento,
                    'hora_agendamento' => $agendamento->hora_agendamento,
                    'servicos'         => $servicos,
                ];
            });
    }
}
