<?php

namespace App\Http\Controllers;

use App\Models\BusinessSnapshots;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class BusinessSnapshotsController extends Controller
{
    public function index()
    {
        $business = Auth::user()?->business;

        if (! $business) {
            return response()->json([
                'message' => 'Business not found',
            ], Response::HTTP_NOT_FOUND);
        }

        $snapshots = $business->businessSnapshots()
            ->orderByDesc('month')
            ->get();

        return response()->json([
            'message' => 'Business snapshots fetched successfully',
            'business_snapshots' => $snapshots,
        ]);
    }

    public function show(string $month)
    {
        $business = Auth::user()?->business;

        if (! $business) {
            return response()->json([
                'message' => 'Business not found',
            ], Response::HTTP_NOT_FOUND);
        }

        $snapshot = $business->businessSnapshots()
            ->where('month', $month)
            ->first();

        if (! $snapshot) {
            return response()->json([
                'message' => 'Business snapshot not found',
            ], Response::HTTP_NOT_FOUND);
        }

        return response()->json([
            'message' => 'Business snapshot fetched successfully',
            'business_snapshot' => $snapshot,
        ]);
    }

    /**
     * Manually generate (or regenerate) a monthly snapshot.
     */
    public function generate(Request $request)
    {
        $business = Auth::user()?->business;

        if (! $business) {
            return response()->json([
                'message' => 'Business not found',
            ], Response::HTTP_NOT_FOUND);
        }

        $validated = $request->validate([
            'month' => ['nullable', 'date_format:Y-m'],
        ]);

        $month = $validated['month'] ?? now()->format('Y-m');

        $start = Carbon::createFromFormat('Y-m', $month)->startOfMonth()->toDateString();
        $end = Carbon::createFromFormat('Y-m', $month)->endOfMonth()->toDateString();

        $totalSales = (float) $business->sales()
            ->whereBetween('date', [$start, $end])
            ->sum('amount');

        $totalExpenses = (float) $business->expenses()
            ->whereBetween('date', [$start, $end])
            ->sum('amount');

        $transactionCount =
            (int) $business->sales()->whereBetween('date', [$start, $end])->count()
            + (int) $business->expenses()->whereBetween('date', [$start, $end])->count();

        $netProfit = $totalSales - $totalExpenses;

        $snapshot = BusinessSnapshots::updateOrCreate(
            [
                'business_id' => $business->id,
                'month' => $month,
            ],
            [
                'total_sales' => $totalSales,
                'total_expenses' => $totalExpenses,
                'net_profit' => $netProfit,
                'transaction_count' => $transactionCount,
            ]
        );

        return response()->json([
            'message' => 'Business snapshot generated successfully',
            'business_snapshot' => $snapshot,
        ], Response::HTTP_CREATED);
    }
}
