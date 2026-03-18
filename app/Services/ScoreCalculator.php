<?php

namespace App\Services;

use App\Models\Business;
use App\Models\Expense;
use App\Models\Sale;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ScoreCalculator
{
    public function calculate(Business $business): int
    {
        $score =
            $this->profileCompletenessScore($business) +
            $this->timeOnPlatformScore($business) +
            $this->transactionConsistencyScore($business) +
            $this->revenueTrendScore($business) +
            $this->expenseRatioScore($business);

        return max(0, min(100, $score));
    }

    public function breakdown(Business $business): array
    {
        $profile = $this->profileCompletenessScore($business);      // /25
        $consistency = $this->transactionConsistencyScore($business); // /25
        $trend = $this->revenueTrendScore($business);              // /20
        $ratio = $this->expenseRatioScore($business);              // /15
        $time = $this->timeOnPlatformScore($business);             // /15

        $total = max(0, min(100, $profile + $consistency + $trend + $ratio + $time));

        return [
            'score' => $total,
            'breakdown' => [
                'profile_completeness' => ['points' => $profile, 'max' => 25],
                'transaction_consistency' => ['points' => $consistency, 'max' => 25],
                'revenue_trend' => ['points' => $trend, 'max' => 20],
                'expense_ratio' => ['points' => $ratio, 'max' => 15],
                'time_on_platform' => ['points' => $time, 'max' => 15],
            ],
        ];
    }

    private function profileCompletenessScore(Business $business): int
    {
        $score = 0;

        if (! empty($business->name)) {
            $score += 4;
        }
        if (! empty($business->industry)) {
            $score += 4;
        }
        if (! empty($business->description)) {
            $score += 4;
        }
        if (! empty($business->location)) {
            $score += 4;
        }
        if (! empty($business->phone)) {
            $score += 4;
        }
        if (! empty($business->email)) {
            $score += 5;
        }

        return $score; // max 25
    }

    private function timeOnPlatformScore(Business $business): int
    {
        $createdAt = $business->created_at ? Carbon::parse($business->created_at) : Carbon::now();
        $months = $createdAt->diffInMonths(Carbon::now());

        if ($months < 1) {
            return 3;
        }
        if ($months < 3) {
            return 6;
        }
        if ($months < 6) {
            return 9;
        }
        if ($months < 12) {
            return 12;
        }

        return 15;
    }

    private function transactionConsistencyScore(Business $business): int
    {
        $end = Carbon::now()->endOfMonth();
        $start = Carbon::now()->startOfMonth()->subMonthsNoOverflow(2);

        $salesCounts = Sale::query()
            ->where('business_id', $business->id)
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->selectRaw("strftime('%Y-%m', `date`) as ym, COUNT(*) as cnt")
            ->groupBy('ym');

        $expenseCounts = Expense::query()
            ->where('business_id', $business->id)
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->selectRaw("strftime('%Y-%m', `date`) as ym, COUNT(*) as cnt")
            ->groupBy('ym');

        $union = $salesCounts->unionAll($expenseCounts);

        $perMonth = DB::query()
            ->fromSub($union, 't')
            ->selectRaw('ym, SUM(cnt) as total')
            ->groupBy('ym')
            ->pluck('total', 'ym');

        $months = [
            Carbon::now()->startOfMonth()->subMonthsNoOverflow(2)->format('Y-m'),
            Carbon::now()->startOfMonth()->subMonthsNoOverflow(1)->format('Y-m'),
            Carbon::now()->startOfMonth()->format('Y-m'),
        ];

        $sum = 0;
        foreach ($months as $m) {
            $sum += (int) ($perMonth[$m] ?? 0);
        }

        $avg = $sum / 3;

        if ($avg <= 0) {
            return 0;
        }
        if ($avg <= 4) {
            return 8;
        }
        if ($avg <= 9) {
            return 16;
        }

        return 25;
    }

    private function revenueTrendScore(Business $business): int
    {
        $snapshots = $business->businessSnapshots()
            ->orderBy('month', 'desc')
            ->limit(3)
            ->get(['month', 'total_sales'])
            ->reverse()
            ->values();

        if ($snapshots->count() < 3) {
            return 0;
        }

        $m1 = (float) $snapshots[0]->total_sales;
        $m2 = (float) $snapshots[1]->total_sales;
        $m3 = (float) $snapshots[2]->total_sales;

        if ($m3 > $m2 && $m2 > $m1) {
            return 20; // growing
        }

        $avg = ($m1 + $m2 + $m3) / 3;
        $max = max($m1, $m2, $m3);
        $min = min($m1, $m2, $m3);

        if ($avg > 0 && ($max - $min) <= (0.10 * $avg)) {
            return 12; // stable within ±10%
        }

        if ($m3 < $m2 && $m2 < $m1) {
            return 5; // declining
        }

        return 5;
    }

    private function expenseRatioScore(Business $business): int
    {
        $totalRevenue = (float) $business->sales()->sum('amount');
        $totalExpenses = (float) $business->expenses()->sum('amount');

        if ($totalExpenses <= 0 || $totalRevenue <= 0) {
            return 0;
        }

        $ratio = $totalExpenses / $totalRevenue;

        if ($ratio < 0.50) {
            return 15;
        }
        if ($ratio < 0.75) {
            return 10;
        }
        if ($ratio < 0.90) {
            return 5;
        }

        return 0;
    }
}