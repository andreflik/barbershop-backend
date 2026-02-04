<?php

namespace App\Http\Controllers;

use App\Models\BlockedPeriod;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BlockedPeriodsController extends Controller
{
    public function index(): JsonResponse
    {
        $items = BlockedPeriod::query()
            ->orderBy('date')
            ->orderBy('time')
            ->get([
                'id',
                'date',
                'time',
                'is_full_day',
                'reason',
                'created_by',
                'created_at',
            ]);

        return response()->json([
            'data' => $items,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'date'   => ['required', 'date_format:Y-m-d'],
            'times'  => ['nullable', 'array'],
            'times.*' => ['date_format:H:i'],
            'reason' => ['required', 'string', 'max:255'],
        ]);

        if (empty($validated['times'])) {
            BlockedPeriod::create([
                'date'        => $validated['date'],
                'time'        => null,
                'is_full_day' => true,
                'reason'      => $validated['reason'],
                'created_by'  => Auth::id(),
            ]);
        } else {
            foreach ($validated['times'] as $time) {
                BlockedPeriod::create([
                    'date'        => $validated['date'],
                    'time'        => $time,
                    'is_full_day' => false,
                    'reason'      => $validated['reason'],
                    'created_by'  => Auth::id(),
                ]);
            }
        }

        return response()->json([
            'message' => 'Bloqueio cadastrado com sucesso.',
        ], 201);
    }


    public function destroy(int $id): JsonResponse
    {
        BlockedPeriod::findOrFail($id)->delete();

        return response()->json([
            'message' => 'Bloqueio removido com sucesso.',
        ]);
    }

    public function publicByMonth(Request $request): JsonResponse
    {
        $month = $request->query('month'); // ex: 2026-02

        if (!$month || !preg_match('/^\d{4}-\d{2}$/', $month)) {
            return response()->json([
                'blockedDays' => []
            ]);
        }

        [$year, $monthNum] = explode('-', $month);

        $items = BlockedPeriod::query()
            ->whereYear('date', $year)
            ->whereMonth('date', $monthNum)
            ->where('is_full_day', true)
            ->orderBy('date')
            ->get(['date', 'reason']);

        return response()->json([
            'blockedDays' => $items->map(fn($b) => [
                'date'   => $b->date,
                'reason' => $b->reason,
            ]),
        ]);
    }
}
