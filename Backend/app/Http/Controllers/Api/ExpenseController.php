<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ExpenseController extends Controller
{
    private const PAYMENT_METHODS = ['cash', 'bank_transfer', 'card', 'other'];

    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'store_id' => ['nullable', 'integer', 'exists:stores,id'],
            'category' => ['nullable', 'string', 'max:100'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);
        $query = Expense::with([
            'store:id,name',
            'creator:id,username,full_name',
        ]);
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($builder) use ($search) {
                $builder->where('category', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('reference', 'like', "%{$search}%")
                    ->orWhere('payment_method', 'like', "%{$search}%")
                    ->orWhereHas('store', fn ($store) => $store->where('name', 'like', "%{$search}%"));
            });
        }
        if (isset($filters['store_id'])) {
            $query->where('store_id', $filters['store_id']);
        }
        if (!empty($filters['category'])) {
            $query->where('category', $filters['category']);
        }
        if (!empty($filters['from'])) {
            $query->whereDate('expense_date', '>=', $filters['from']);
        }
        if (!empty($filters['to'])) {
            $query->whereDate('expense_date', '<=', $filters['to']);
        }

        $summaryQuery = clone $query;
        $summary = $summaryQuery->toBase()
            ->selectRaw('COUNT(*) as count, COALESCE(SUM(amount), 0) as total')
            ->first();

        return response()->json([
            'summary' => $summary,
            'data' => $query->latest('expense_date')->latest('id')->paginate(25),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $this->validateExpense($request);
        $validated['created_by'] = $request->user()->id;

        $expense = Expense::create($validated);

        return response()->json([
            'message' => 'Expense created successfully.',
            'data' => $expense->load(['store:id,name', 'creator:id,username,full_name']),
        ], 201);
    }

    public function update(Request $request, Expense $expense): JsonResponse
    {
        $validated = $this->validateExpense($request);
        $expense->update($validated);

        return response()->json([
            'message' => 'Expense updated successfully.',
            'data' => $expense->load(['store:id,name', 'creator:id,username,full_name']),
        ]);
    }

    public function destroy(Expense $expense): JsonResponse
    {
        $expense->delete();

        return response()->json([
            'message' => 'Expense deleted successfully.',
        ]);
    }

    private function validateExpense(Request $request): array
    {
        return $request->validate([
            'store_id' => ['required', 'integer', 'exists:stores,id'],
            'category' => ['required', 'string', 'max:100'],
            'amount' => ['required', 'numeric', 'gt:0', 'max:9999999999.99'],
            'expense_date' => ['required', 'date'],
            'payment_method' => ['required', Rule::in(self::PAYMENT_METHODS)],
            'reference' => ['nullable', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:5000'],
        ]);
    }
}
