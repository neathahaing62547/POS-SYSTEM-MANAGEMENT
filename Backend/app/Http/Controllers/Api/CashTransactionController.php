<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CashTransaction;
use App\Models\UserShift;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CashTransactionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'type' => ['nullable', 'in:cash_in,cash_out'],
            'shift_id' => ['nullable', 'integer', 'exists:user_shifts,id'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);
        $query = CashTransaction::with([
            'creator:id,username,full_name',
            'shift.user:id,username,full_name',
            'shift.cashRegister.store',
        ]);
        if (isset($filters['type'])) {
            $query->where('type', $filters['type']);
        }
        if (isset($filters['shift_id'])) {
            $query->where('shift_id', $filters['shift_id']);
        }
        if (isset($filters['from'])) {
            $query->whereDate('created_at', '>=', $filters['from']);
        }
        if (isset($filters['to'])) {
            $query->whereDate('created_at', '<=', $filters['to']);
        }
        $summary = (clone $query)
            ->toBase()
            ->selectRaw(
                "COUNT(*) as total_count,
                COALESCE(SUM(CASE WHEN type = 'cash_in' THEN amount ELSE 0 END), 0) as cash_in_total,
                COALESCE(SUM(CASE WHEN type = 'cash_out' THEN amount ELSE 0 END), 0) as cash_out_total"
            )
            ->first();
        return response()->json([
            'summary' => $summary,
            'data' => $query->latest()->paginate(25),
        ]);
    }
    public function cashIn(Request $request): JsonResponse
    {
        return $this->record($request, 'cash_in');
    }
    public function cashOut(Request $request): JsonResponse
    {
        return $this->record($request, 'cash_out');
    }
    private function record(Request $request, string $type): JsonResponse
    {
        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'gt:0', 'max:99999999.99'],
            'reason' => ['required', 'string', 'max:2000'],
        ]);

        $transaction = DB::transaction(function () use ($validated, $type) {
            $shift = UserShift::query()
                ->where('user_id', Auth::id())
                ->where('status', 'open')
                ->lockForUpdate()
                ->first();

            if (!$shift) {
                return null;
            }

            return CashTransaction::create([
                'shift_id' => $shift->id,
                'created_by' => Auth::id(),
                'type' => $type,
                'amount' => $validated['amount'],
                'reason' => $validated['reason'],
            ]);
        });

        if (!$transaction) {
            return response()->json([
                'message' => 'Open a shift before recording a cash transaction.',
            ], 422);
        }

        return response()->json([
            'message' => $type === 'cash_in'
                ? 'Cash-in transaction recorded successfully.'
                : 'Cash-out transaction recorded successfully.',
            'data' => $transaction->load([
                'creator:id,username,full_name',
                'shift.user:id,username,full_name',
                'shift.cashRegister.store',
            ]),
        ], 201);
    }
}
