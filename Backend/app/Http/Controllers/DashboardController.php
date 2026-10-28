<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Expense;
use App\Models\InventoryStock;
use App\Models\Order;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\SalesReturn;
use  App\Models\Supplier;
use  App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $validated = $request->validate([
            'period' => [
                'sometimes',
                'required',
                'in:all,today,week,month,year,custom',
            ],
            'start_date' => [
                'required_if:period,custom',
                'nullable',
                'date',
            ],
            'end_date' => [
                'required_if:period,custom',
                'nullable',
                'date',
                'after_or_equal:start_date',
            ],
        ]);

        $period = $validated['period'] ?? 'all';

        $startDate = null;
        $endDate = null;
        $previousStartDate = null;
        $previousEndDate = null;

        if ($period !== 'all') {
            switch ($period) {
                case 'today':
                    $startDate = Carbon::today();
                    $endDate = Carbon::today()->endOfDay();
                    break;

                case 'week':
                    $startDate = Carbon::now()->startOfWeek();
                    $endDate = Carbon::now()->endOfWeek();
                    break;

                case 'month':
                    $startDate = Carbon::now()->startOfMonth();
                    $endDate = Carbon::now()->endOfMonth();
                    break;

                case 'year':
                    $startDate = Carbon::now()->startOfYear();
                    $endDate = Carbon::now()->endOfYear();
                    break;

                case 'custom':
                    $startDate = Carbon::parse(
                        $validated['start_date']
                    )->startOfDay();

                    $endDate = Carbon::parse(
                        $validated['end_date']
                    )->endOfDay();
                    break;
            }

            switch ($period) {
                case 'today':
                    $previousStartDate = $startDate->copy()->subDay();
                    $previousEndDate = $endDate->copy()->subDay();
                    break;

                case 'week':
                    $previousStartDate = $startDate->copy()->subWeek();
                    $previousEndDate = $endDate->copy()->subWeek();
                    break;

                case 'month':
                    $previousStartDate = $startDate->copy()
                        ->subMonthNoOverflow()
                        ->startOfMonth();

                    $previousEndDate = $previousStartDate->copy()
                        ->endOfMonth();
                    break;

                case 'year':
                    $previousStartDate = $startDate->copy()
                        ->subYearNoOverflow()
                        ->startOfYear();

                    $previousEndDate = $previousStartDate->copy()
                        ->endOfYear();
                    break;

                case 'custom':
                    $days = (int) $startDate->diffInDays($endDate) + 1;

                    $previousStartDate = $startDate->copy()
                        ->subDays($days)
                        ->startOfDay();

                    $previousEndDate = $startDate->copy()
                        ->subSecond();
                    break;
            }
        }

        $applyDateRange = function (
            $query,
            string $column,
            $from,
            $to
        ) use ($period) {
            if ($period !== 'all') {
                $query->whereBetween($column, [$from, $to]);
            }

            return $query;
        };

        $successfulOrderStatuses = ['paid', 'completed'];

        $applySuccessfulSales = function ($query) use (
            $successfulOrderStatuses
        ) {
            return $query->whereIn(
                'status',
                $successfulOrderStatuses
            );
        };
        $allProducts = Product::count();

        $activeProducts = Product::where('status', 'active')->count();

        $inactiveProducts = Product::where('status', 'inactive')->count();

        $productAddedQuery = Product::query();

        $previousProductsAddedQuery = Product::query();

        if ($period !== 'all') {
            $productAddedQuery->whereBetween('created_at', [
                $startDate,
                $endDate,
            ]);

            $previousProductsAddedQuery->whereBetween('created_at', [
                $previousStartDate,
                $previousEndDate,
            ]);
        }

        $productAdded = $productAddedQuery->count();

        $previousProductsAdded = $period !== 'all'
            ? $previousProductsAddedQuery->count()
            : null;

        $growProductAmount = $previousProductsAdded !== null
            ? $productAdded - $previousProductsAdded
            : null;

        $growPercentageProducts = $previousProductsAdded !== null
            && $previousProductsAdded > 0
            ? round(
                ($growProductAmount / $previousProductsAdded) * 100,
                2
            )
            : null;
        $currentStock = (int) InventoryStock::sum('quantity');
        $lowStockProducts = InventoryStock::query()
            ->whereColumn('quantity', '<=', 'alert_quantity')
            ->where('quantity', '>', 0)
            ->distinct()
            ->count('product_id');

        $outStockProducts = InventoryStock::query()
            ->where('quantity', '<=', 0)
            ->distinct()
            ->count('product_id');

        $currentStockMovementQuery = DB::table('stock_histories');

        $previousStockMovementQuery = DB::table('stock_histories');

        if ($period !== 'all') {
            $currentStockMovementQuery->whereBetween('created_at', [
                $startDate,
                $endDate,
            ]);

            $previousStockMovementQuery->whereBetween('created_at', [
                $previousStartDate,
                $previousEndDate,
            ]);
        }

        $currentStockMovement = (int) $currentStockMovementQuery
            ->sum('quantity_changed');

        $previousStockMovement = $period !== 'all'
            ? (int) $previousStockMovementQuery->sum('quantity_changed')
            : null;

        $stockGrowAmount = $previousStockMovement !== null
            ? $currentStockMovement - $previousStockMovement
            : null;

        $stockGrowthPercentage = $previousStockMovement !== null
            && $previousStockMovement > 0
            ? round(
                ($stockGrowAmount / $previousStockMovement) * 100,
                2
            )
            : null;


        $allBrands = Brand::count();

        $activeBrands = Brand::where('status', 'active')->count();
        $inactiveBrands = Brand::where('status', 'inactive')->count();

        $allCategories = Category::count();

        $activeCategories = Category::where('status', 'active')->count();

        $inactiveCategories = Category::where('status', 'inactive')->count();


        $allOrdersQuery = Order::query();

        $pendingOrdersQuery = Order::where('status', 'pending');

        $processingOrdersQuery = Order::where('status', 'processing');

        $completedOrdersQuery = Order::where('status', 'completed');

        $cancelledOrdersQuery = Order::where('status', 'cancelled');

        $applyDateRange(
            $allOrdersQuery,
            'created_at',
            $startDate,
            $endDate
        );

        $applyDateRange(
            $pendingOrdersQuery,
            'created_at',
            $startDate,
            $endDate
        );

        $applyDateRange(
            $processingOrdersQuery,
            'created_at',
            $startDate,
            $endDate
        );

        $applyDateRange(
            $completedOrdersQuery,
            'created_at',
            $startDate,
            $endDate
        );

        $applyDateRange(
            $cancelledOrdersQuery,
            'created_at',
            $startDate,
            $endDate
        );

        $allOrders = $allOrdersQuery->count();

        $pendingOrders = $pendingOrdersQuery->count();

        $processingOrders = $processingOrdersQuery->count();

        $completedOrders = $completedOrdersQuery->count();

        $cancelledOrders = $cancelledOrdersQuery->count();

        $allExpenses = Expense::count();

        $filteredExpenses = Expense::query();

        if ($period !== 'all') {
            $filteredExpenses->whereBetween('expense_date', [
                $startDate->toDateString(),
                $endDate->toDateString(),
            ]);
        }

        $filteredExpenseCount = (clone $filteredExpenses)->count();

        $totalExpenseAmount = round(
            (float) (clone $filteredExpenses)->sum('amount'),
            2
        );
        $currentSaleQuery = Order::query();

        $applySuccessfulSales($currentSaleQuery);

        $applyDateRange(
            $currentSaleQuery,
            'created_at',
            $startDate,
            $endDate
        );

        $currentSale = round(
            (float) $currentSaleQuery->sum('grand_total'),
            2
        );

        $previousSale = null;
        $saleGrowAmount = null;
        $saleGrowthPercentage = null;

        if ($period !== 'all') {
            $previousSaleQuery = Order::query();

            $applySuccessfulSales($previousSaleQuery);

            $previousSaleQuery->whereBetween('created_at', [
                $previousStartDate,
                $previousEndDate,
            ]);

            $previousSale = round(
                (float) $previousSaleQuery->sum('grand_total'),
                2
            );

            $saleGrowAmount = round(
                $currentSale - $previousSale,
                2
            );
            $saleGrowthPercentage = $previousSale > 0
                ? round(
                    ($saleGrowAmount / $previousSale) * 100,
                    2
                )
                : null;
        }
        $soldQuantityQuery = DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->whereIn('orders.status', $successfulOrderStatuses);

        if ($period !== 'all') {
            $soldQuantityQuery->whereBetween('orders.created_at', [
                $startDate,
                $endDate,
            ]);
        }

        $soldQuantity = (int) $soldQuantityQuery
            ->sum('order_items.quantity');


        return response()->json([
            'success' => true,
            'message' => 'Main Dashboard Quick View For Admin',

            'Data' => [
                'Filter' => [
                    'Period' => $period,
                    'StartDate' => $startDate?->toDateString(),
                    'EndDate' => $endDate?->toDateString(),
                ],

                'Summary' => [
                    'SummaryProducts' => [
                        'AllProducts' => $allProducts,
                        'ActiveProducts' => $activeProducts,
                        'InactiveProducts' => $inactiveProducts,
                        'LowstockProducts' => $lowStockProducts,
                        'OutStockProduct' => $outStockProducts,
                        'SoldQuantity' => $soldQuantity,
                        'CurrentProductsAdded' => $productAdded,
                        'PreviousProductsAdded' => $previousProductsAdded,
                        'GrowAmountProduct' => $growProductAmount,
                        'growPercentageProducts' => $growPercentageProducts,
                    ],

                    'SummaryBrand' => [
                        'AllBrands' => $allBrands,
                        'ActiveBrands' => $activeBrands,
                        'InactiveBrands' => $inactiveBrands,
                    ],

                    'SummaryCategories' => [
                        'AllCategories' => $allCategories,
                        'ActiveCategories' => $activeCategories,
                        'InactiveCategories' => $inactiveCategories,
                    ],

                    'SummaryOrder' => [
                        'AllOrders' => $allOrders,
                        'PendingOrders' => $pendingOrders,
                        'ProcessingOrders' => $processingOrders,
                        'CompletedOrders' => $completedOrders,
                        'CancelledOrders' => $cancelledOrders,
                    ],

                    'SummaryExpenses' => [
                        'AllExpenses' => $allExpenses,
                        'ExpenseCount' => $filteredExpenseCount,
                        'TotalExpenseAmount' => $totalExpenseAmount,
                    ],

                    'InventoryStockSummary' => [
                        'current_stock' => $currentStock,
                        'StockgrowAmount' => $stockGrowAmount,
                        'stock_growth_percentage' => $stockGrowthPercentage,
                    ],

                    'SaleSummary' => [
                        'current_sale' => $currentSale,
                        'previous_sale' => $previousSale,
                        'sale_growth_amount' => $saleGrowAmount,
                        'sale_growth_percentage' => $saleGrowthPercentage,
                    ],
                ],
            ],
        ]);
    }

    public function saleChart(Request $request)
    {
        $validated = $request->validate([
            'period' => [
                'sometimes',
                'required',
                'in:all,today,week,month,year,custom',
            ],
            'start_date' => [
                'required_if:period,custom',
                'nullable',
                'date',
            ],
            'end_date' => [
                'required_if:period,custom',
                'nullable',
                'date',
                'after_or_equal:start_date',
            ],
        ]);

        $period = $validated['period'] ?? 'all';

        $start = null;
        $end = null;

        switch ($period) {
            case 'today':
                $start = now()->startOfDay();
                $end = now()->endOfDay();
                break;

            case 'week':
                $start = now()->startOfWeek();
                $end = now()->endOfWeek();
                break;

            case 'month':
                $start = now()->startOfMonth();
                $end = now()->endOfMonth();
                break;

            case 'year':
                $start = now()->startOfYear();
                $end = now()->endOfYear();
                break;

            case 'custom':
                $start = Carbon::parse($validated['start_date'])->startOfDay();
                $end = Carbon::parse($validated['end_date'])->endOfDay();
                break;
        }

        $query = DB::table('orders')
            ->selectRaw("TO_CHAR(created_at, 'YYYY-MM-DD') as date_key")
            ->selectRaw('SUM(grand_total) as total_sales')
            ->selectRaw('COUNT(*) as total_orders')
            ->whereIn('status', ['paid', 'completed']);

        if ($period !== 'all') {
            $query->whereBetween('created_at', [$start, $end]);
        }

        $rows = $query
            ->groupBy('date_key')
            ->orderBy('date_key')
            ->get()
            ->keyBy('date_key');

        $labels = [];
        $sales = [];
        $orders = [];

        if ($period === 'all') {
            // Include every date with successful sales, from oldest to newest.
            foreach ($rows as $dateKey => $row) {
                $labels[] = Carbon::parse($dateKey)->format('M d, Y');
                $sales[] = round((float) $row->total_sales, 2);
                $orders[] = (int) $row->total_orders;
            }
        } else {
            // Fill missing dates with zero so the chart remains continuous.
            $cursor = $start->copy()->startOfDay();
            $lastDate = $end->copy()->startOfDay();

            while ($cursor->lte($lastDate)) {
                $dateKey = $cursor->format('Y-m-d');
                $row = $rows->get($dateKey);

                $labels[] = match ($period) {
                    'year' => $cursor->format('M d, Y'),
                    default => $cursor->format('M d'),
                };

                $sales[] = round((float) ($row->total_sales ?? 0), 2);
                $orders[] = (int) ($row->total_orders ?? 0);

                $cursor->addDay();
            }
        }

        return response()->json([
            'success' => true,
            'Data' => [
                'SaleChart' => [
                    'labels' => $labels,
                    'sales' => $sales,
                    'orders' => $orders,
                ],
            ],
        ]);
    }
    public function dashboardQuickView()
    {
        $recentOrders = \App\Models\Order::query()
            ->latest()
            ->take(5)
            ->get()
            ->map(function ($order) {
                return [
                    'id' => $order->id,
                    'order_number' => $order->order_number
                        ?? $order->reference_no
                        ?? ('ORD-' . $order->id),
                    'customer_name' => $order->customer?->name ?? 'Walk-in Customer',
                    'status' => $order->status,
                    'total' => $order->total_amount
                        ?? $order->total
                        ?? $order->grand_total
                        ?? 0,
                    'created_at' => $order->created_at,
                ];
            });


        $topProducts = \App\Models\OrderItem::query()
            ->selectRaw('product_id, SUM(quantity) as total_sold')
            ->with('product:id,name')
            ->groupBy('product_id')
            ->orderByDesc('total_sold')
            ->take(5)
            ->get()
            ->map(function ($item) {
                return [
                    'product_id' => $item->product_id,
                    'name' => $item->product?->name ?? 'Unknown Product',
                    'total_sold' => (float) $item->total_sold,
                ];
            });
        $lowStockProducts = \App\Models\InventoryStock::query()
            ->with('product:id,name')
            ->whereColumn('quantity', '<=', 'alert_quantity')
            ->where('quantity', '>', 0)
            ->take(5)
            ->get()
            ->map(function ($stock) {
                return [
                    'product_id' => $stock->product_id,
                    'name' => $stock->product?->name ?? 'Unknown Product',
                    'quantity' => (float) $stock->quantity,
                    'alert_quantity' => (float) $stock->alert_quantity,
                ];
            });
        $outofstocks = InventoryStock::query()
            ->with('product:id,name')
            ->where('quantity', '=', '0')
            ->take(5)
            ->get()
            ->map(function ($outstock) {
                return [
                    'product_id' => $outstock->product_id,
                    'name' => $outstock->product?->name ?? 'Unknown Product',
                    'quantity' => (int) $outstock->quantity ?? 0,
                ];
            });
        $ReactionPurchase = Purchase::query()
            ->with(['items', 'supplier', 'store'])
            ->where('status', 'received')
            ->latest()
            ->take(5)
            ->get()
            ->map(function ($purchase) {
                return [
                    'purchase_id' => $purchase->id,
                    'status' => $purchase->status,
                    'total_cost' => $purchase->total_cost,
                    'received_at' => $purchase->received_at,
                    'store_id' =>  [
                        'name' => $purchase->store?->name,
                    ],
                    'supplier' => [
                        'company_name' => $purchase->supplier?->company_name,
                        'contact_person' => $purchase->supplier?->contact_person,
                    ],
                    'items' => $purchase->items
                        ->map(function ($item) {
                            return [
                                'product_id' => $item->product_id,
                                'quantity' => $item->quantity ?? null,
                                'unit_cost' => $item->unit_cost ?? null,
                            ];
                        }),
                ];
            });
        $ReactionSaleRuturn = SalesReturn::query()
            ->with('items')
            ->where('status', 'completed')
            ->take(5)
            ->get()
            ->map(function ($SaleReturn) {
                return [
                   'order' => $SaleReturn->order?->invoice_no,
                    'total_refunded' => $SaleReturn->total_refunded,
                    'status' => $SaleReturn->status,
                    'user' =>  [
                                'name' => $SaleReturn->user?->full_name,
                                 'roles' =>$SaleReturn->user?->roles->map(function ($roles){
                                       return [
                                         'name' =>$roles->name,
                                       ];
                                 }),
                            ],
                    'items' => $SaleReturn->items
                        ->map(function ($returnitem) {
                            return [
                                'quantity' => $returnitem->quantity,
                            ];
                        })
                ];
            });
        return response()->json([
            'recent_orders' => $recentOrders,
            'top_products' => $topProducts,
            'low_stock_products' => $lowStockProducts,
            'Outofstocks' => $outofstocks,
            'ReactionpercahseRecived' => $ReactionPurchase,
            'ReactionSaleRuturn' => $ReactionSaleRuturn,
        ]);
    }
}
