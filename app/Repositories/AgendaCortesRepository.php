<?php

namespace App\Repositories;

use App\Models\AgendarCorte;
use App\Models\Servico;
use Illuminate\Support\Collection;

class AgendaCortesRepository
{
    /**
     * Horários agendados (HH:mm) para a data (YYYY-mm-dd).
     */
    public function getBookedTimes(string $data): Collection
    {
        return AgendarCorte::query()
            ->whereDate('data_agendamento', $data)
            ->orderBy('hora_agendamento')
            ->pluck('hora_agendamento')
            ->map(fn ($hora) => substr((string) $hora, 0, 5));
    }

    /**
     * Cria um agendamento (campos já validados no service/controller).
     */
    public function saveAgendamento(array $dados): AgendarCorte
    {
        // Model já tem $fillable seguro
        return AgendarCorte::create($dados);
    }

    /**
     * Verifica se já existe agendamento no mesmo slot.
     */
    public function existeAgendamento(string $data, string $hora): bool
    {
        return AgendarCorte::query()
            ->whereDate('data_agendamento', $data)
            ->where('hora_agendamento', $hora)
            ->exists();
    }

    /**
     * Lista serviços para seleção (campos essenciais).
     */
    public function listarServicos(): Collection
    {
        return Servico::query()
            ->orderBy('servico')
            ->get(['id', 'codigo', 'servico', 'preco']);
    }

    /**
     * Busca por ID (casos de edição/exclusão).
     */
    public function buscarPorId(int $id): ?AgendarCorte
    {
        return AgendarCorte::find($id);
    }

    /**
     * Remove um agendamento já carregado.
     */
    public function excluir(AgendarCorte $agendamento): void
    {
        $agendamento->delete();
    }
}
