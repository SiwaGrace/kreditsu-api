<?php

namespace App\Observers;

use App\Models\Expense;
use App\Services\ScoreCalculator;

class ExpenseObserver
{
    public function __construct(protected ScoreCalculator $calculator) {}

    public function created(Expense $expense): void
    {
        $this->recalculate($expense);
    }

    public function updated(Expense $expense): void
    {
        $this->recalculate($expense);
    }

    public function deleted(Expense $expense): void
    {
        $this->recalculate($expense);
    }

    private function recalculate(Expense $expense): void
    {
        $business = $expense->business;
        $business->kreditsu_score = $this->calculator->calculate($business);
        $business->save();
    }
}