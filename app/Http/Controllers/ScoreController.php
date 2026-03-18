<?php

namespace App\Http\Controllers;

use App\Services\ScoreCalculator;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

class ScoreController extends Controller
{
    public function __construct(protected ScoreCalculator $calculator)
    {
    }

    public function show()
    {
        $business = Auth::user()?->business;

        if (! $business) {
            return response()->json([
                'message' => 'Business not found',
            ], Response::HTTP_NOT_FOUND);
        }

        $data = $this->calculator->breakdown($business);

        return response()->json([
            'message' => 'Kreditsu score fetched successfully',
            'score' => $data['score'],
            'breakdown' => $data['breakdown'],
        ]);
    }
}

