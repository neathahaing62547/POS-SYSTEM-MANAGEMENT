<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CashTransaction;
use App\Models\Expense;
use App\Models\InventoryStock;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Purchase;
use App\Models\ReportSnapshot;
use App\Models\SalesReturn;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'store_id' => ['nullable', 'integer', 'exists:stores,id'],
        ]);

        $to = CarbonImmutable::parse($filters['to'] ?? now()->toDateString())->toDateString();
        $from = CarbonImmutable::parse($filters['from'] ?? CarbonImmutable::parse($to)->subDays(29)->toDateString())->toDateString();
        $storeId = $filters['store_id'] ?? null;

        $salesOrders = $this->salesOrders($from, $to, $storeId);
        $sales = (clone $salesOrders)
            ->selectRaw('COUNT(*) as order_count, COALESCE(SUM(grand_total), 0) as gross_sales')
            ->first();

        $returnQuery = SalesReturn::query()
            ->whereHas('order', fn (Builder $orders) => $this->applyStoreFilter($orders, $storeId))
            ->whereDate('created_at', '>=', $from)
            ->whereDate('created_at', '<=', $to);
        $returns = (clone $returnQuery)
            ->selectRaw('COUNT(*) as return_count, COALESCE(SUM(total_refunded), 0) as refunded')
            ->first();

        $purchaseQuery = Purchase::query()
            ->whereDate('ordered_at', '>=', $from)
            ->whereDate('ordered_at', '<=', $to);
        if ($storeId) {
            $purchaseQuery->where('store_id', $storeId);
        }
        $purchases = (clone $purchaseQuery)
            ->selectRaw(
                "COUNT(*) as purchase_count,
                COALESCE(SUM(total_cost), 0) as total_cost,
                COALESCE(SUM(CASE WHEN status = 'received' THEN total_cost ELSE 0 END), 0) as received_cost,
                COALESCE(SUM(CASE WHEN status = 'pending' THEN total_cost ELSE 0 END), 0) as pending_cost"
            )
            ->first();

        $expenseQuery = Expense::query()
            ->whereDate('expense_date', '>=', $from)
            ->whereDate('expense_date', '<=', $to);
        if ($storeId) {
            $expenseQuery->where('store_id', $storeId);
        }
        $expenses = (clone $expenseQuery)
            ->selectRaw('COUNT(*) as expense_count, COALESCE(SUM(amount), 0) as total')
            ->first();

        $paymentsQuery = Payment::query()
            ->whereHas('order', function (Builder $orders) use ($from, $to, $storeId) {
                $orders->whereDate('created_at', '>=', $from)
                    ->whereDate('created_at', '<=', $to);
                $this->applyStoreFilter($orders, $storeId);
            });
        $payments = (clone $paymentsQuery)
            ->selectRaw(
                "COUNT(*) as payment_count,
                COALESCE(SUM(amount), 0) as total_amount,
                COALESCE(SUM(CASE WHEN status = 'paid' THEN amount ELSE 0 END), 0) as paid_amount,
                COALESCE(SUM(CASE WHEN status = 'pending' THEN amount ELSE 0 END), 0) as pending_amount"
            )
            ->first();

        $cashQuery = CashTransaction::query()
            ->whereHas('shift', function (Builder $shifts) use ($storeId) {
                if ($storeId) {
                    $shifts->whereHas('cashRegister', fn (Builder $registers) => $registers->where('store_id', $storeId));
                }
            })
            ->whereDate('created_at', '>=', $from)
            ->whereDate('created_at', '<=', $to);
        $cash = (clone $cashQuery)
            ->selectRaw(
                "COUNT(*) as transaction_count,
                COALESCE(SUM(CASE WHEN type = 'cash_in' THEN amount ELSE 0 END), 0) as cash_in,
                COALESCE(SUM(CASE WHEN type = 'cash_out' THEN amount ELSE 0 END), 0) as cash_out"
            )
            ->first();

        $inventoryQuery = InventoryStock::query()
            ->join('products', 'products.id', '=', 'inventory_stocks.product_id');
        if ($storeId) {
            $inventoryQuery->where('inventory_stocks.store_id', $storeId);
        }
        $inventory = (clone $inventoryQuery)
            ->selectRaw(
                'COALESCE(SUM(inventory_stocks.quantity), 0) as units,
                COALESCE(SUM(inventory_stocks.quantity * products.cost_price), 0) as stock_value,
                COALESCE(SUM(CASE WHEN inventory_stocks.quantity <= inventory_stocks.alert_quantity THEN 1 ELSE 0 END), 0) as low_stock'
            )
            ->first();

        $dailySales = (clone $salesOrders)
            ->selectRaw('DATE(created_at) as day, COUNT(*) as order_count, COALESCE(SUM(grand_total), 0) as total')
            ->groupBy('day')
            ->orderBy('day')
            ->get()
            ->keyBy('day');

        $salesByDay = [];
        $day = CarbonImmutable::parse($from);
        $lastDay = CarbonImmutable::parse($to);
        while ($day->lessThanOrEqualTo($lastDay)) {
            $key = $day->toDateString();
            $salesByDay[] = [
                'date' => $key,
                'orders' => (int) ($dailySales[$key]->order_count ?? 0),
                'sales' => (float) ($dailySales[$key]->total ?? 0),
            ];
            $day = $day->addDay();
        }

        $salesByPaymentMethod = (clone $paymentsQuery)
            ->selectRaw('payment_method as method, COUNT(*) as payment_count, COALESCE(SUM(amount), 0) as total')
            ->groupBy('payment_method')
            ->orderByDesc('total')
            ->get();

        $expensesByCategory = (clone $expenseQuery)
            ->selectRaw('category, COUNT(*) as expense_count, COALESCE(SUM(amount), 0) as total')
            ->groupBy('category')
            ->orderByDesc('total')
            ->get();

        $topProducts = OrderItem::query()
            ->whereHas('order', function (Builder $orders) use ($from, $to, $storeId) {
                $orders->whereIn('status', ['paid', 'completed'])
                    ->whereDate('created_at', '>=', $from)
                    ->whereDate('created_at', '<=', $to);
                $this->applyStoreFilter($orders, $storeId);
            })
            ->selectRaw('product_id, product_name, SUM(quantity) as quantity, COALESCE(SUM(total_price), 0) as sales')
            ->groupBy('product_id', 'product_name')
            ->orderByDesc('quantity')
            ->limit(10)
            ->get();

        $orderStatusesQuery = Order::query()
            ->whereDate('created_at', '>=', $from)
            ->whereDate('created_at', '<=', $to);
        $this->applyStoreFilter($orderStatusesQuery, $storeId);
        $orderStatuses = (clone $orderStatusesQuery)
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->orderBy('status')
            ->get();

        $summary = [
            'sales_orders' => (int) $sales->order_count,
            'gross_sales' => (float) $sales->gross_sales,
            'returns' => (int) $returns->return_count,
            'refunded' => (float) $returns->refunded,
            'net_sales' => (float) $sales->gross_sales - (float) $returns->refunded,
            'purchase_orders' => (int) $purchases->purchase_count,
            'purchase_cost' => (float) $purchases->total_cost,
            'received_purchase_cost' => (float) $purchases->received_cost,
            'pending_purchase_cost' => (float) $purchases->pending_cost,
            'expenses' => (int) $expenses->expense_count,
            'expense_total' => (float) $expenses->total,
            'payments' => (int) $payments->payment_count,
            'payment_total' => (float) $payments->total_amount,
            'paid_amount' => (float) $payments->paid_amount,
            'pending_amount' => (float) $payments->pending_amount,
            'cash_transactions' => (int) $cash->transaction_count,
            'cash_in' => (float) $cash->cash_in,
            'cash_out' => (float) $cash->cash_out,
            'stock_units' => (int) $inventory->units,
            'stock_value' => (float) $inventory->stock_value,
            'low_stock_items' => (int) $inventory->low_stock,
        ];
        $details = [
            'sales_by_day' => $salesByDay,
            'sales_by_payment_method' => $salesByPaymentMethod,
            'expenses_by_category' => $expensesByCategory,
            'top_products' => $topProducts,
            'order_statuses' => $orderStatuses,
        ];

        $snapshot = ReportSnapshot::create([
            'store_id' => $storeId,
            'generated_by' => $request->user()->id,
            'period_start' => $from,
            'period_end' => $to,
            'summary' => $summary,
            'details' => $details,
            'generated_at' => now(),
        ]);

        return response()->json([
            'message' => 'Reports generated successfully.',
            'report_id' => $snapshot->id,
            'filters' => ['from' => $from, 'to' => $to, 'store_id' => $storeId],
            'summary' => $summary,
            ...$details,
        ]);
    }
    private function salesOrders(string $from, string $to, ?int $storeId): Builder
    {
        $query = Order::query()
            ->whereIn('status', ['paid', 'completed'])
            ->whereDate('created_at', '>=', $from)
            ->whereDate('created_at', '<=', $to);
        return $this->applyStoreFilter($query, $storeId);
    }
    private function applyStoreFilter(Builder $query, ?int $storeId): Builder
    {
        if ($storeId) {
            $query->whereHas('shift.cashRegister', fn (Builder $registers) => $registers->where('store_id', $storeId));
        }
        return $query;
    }
}
