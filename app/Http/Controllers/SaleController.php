<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

class SaleController extends Controller
{
    public function index()
    {
        $business = Auth::user()?->business;

        if (! $business) {
            return response()->json([
                'message' => 'Business not found',
            ], Response::HTTP_NOT_FOUND);
        }

        $sales = $business->sales()->latest('date')->get();

        return response()->json([
            'message' => 'Sales fetched successfully',
            'sales'   => $sales,
        ]);
    }

    public function store(Request $request)
    {
        $business = Auth::user()?->business;

        if (! $business) {
            return response()->json(['message' => 'Business not found'], Response::HTTP_NOT_FOUND);
        }

        $validated = $request->validate([
            'amount' => ['required', 'numeric'],
            'description' => ['nullable', 'string'],
            'date' => ['required', 'date'],
        ]);

        $sale = $business->sales()->create($validated);

        return response()->json([
            'message' => 'Sale recorded successfully',
            'sale' => $sale,
        ], Response::HTTP_CREATED);
    }

    public function show(Sale $sale)
    {
        $this->authorizeSale($sale);

        return response()->json([
            'message' => 'Sale fetched successfully',
            'sale'    => $sale,
        ]);
    }

    public function update(Request $request, Sale $sale)
    {
        $this->authorizeSale($sale);

        $validated = $request->validate([
            'amount' => ['sometimes', 'numeric'],
            'description' => ['nullable', 'string'],
            'date' => ['sometimes', 'date'],
        ]);

        $sale->update($validated);

        return response()->json([
            'message' => 'Sale updated successfully',
            'sale' => $sale,
        ]);
    }

    public function destroy(Sale $sale)
    {
        $this->authorizeSale($sale);

        $sale->delete();

        return response()->json([
            'message' => 'Sale deleted successfully',
        ]);
    }

    protected function authorizeSale(Sale $sale): void
    {
        $business = Auth::user()?->business;

        abort_unless($business && $sale->business_id === $business->id, Response::HTTP_FORBIDDEN);
    }
}
