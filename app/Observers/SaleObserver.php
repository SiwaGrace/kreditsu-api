<?php

namespace App\Observers;

use App\Models\Sale;
use App\Services\ScoreCalculator;

class SaleObserver
{
    public function __construct(protected ScoreCalculator $calculator) {}

    public function created(Sale $sale): void
    {
        $this->recalculate($sale);
    }

    public function updated(Sale $sale): void
    {
        $this->recalculate($sale);
    }

    public function deleted(Sale $sale): void
    {
        $this->recalculate($sale);
    }

    private function recalculate(Sale $sale): void
    {
        $business = $sale->business;
        $business->kreditsu_score = $this->calculator->calculate($business);
        $business->save();
    }
}