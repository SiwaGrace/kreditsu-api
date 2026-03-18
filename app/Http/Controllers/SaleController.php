<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

/**
 * Controller for managing business sales.
 *
 * This controller handles CRUD operations for sales records, ensuring that users can only
 * access and modify sales belonging to their own business. It provides endpoints
 * for listing, creating, viewing, updating, and deleting sales.
 */
class SaleController extends Controller
{
    /**
     * Get all sales for the authenticated user's business.
     */
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

    /**
     * Create a new sale for the authenticated user's business.
     */
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

    /**
     * Get a specific sale by ID.
     */
    public function show(Sale $sale)
    {
        $this->authorizeSale($sale);

        return response()->json([
            'message' => 'Sale fetched successfully',
            'sale'    => $sale,
        ]);
    }

    /**
     * Update an existing sale.
     */
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
            'sale' => $sale->fresh(),
        ]);
    }

    /**
     * Delete a sale.
     */
    public function destroy(Sale $sale)
    {
        $this->authorizeSale($sale);

        $sale->delete();

        return response()->json([
            'message' => 'Sale deleted successfully',
        ]);
    }

    /**
     * Authorize that the sale belongs to the authenticated user's business.
     */
    protected function authorizeSale(Sale $sale): void
    {
        $business = Auth::user()?->business;

        abort_unless($business && $sale->business_id === $business->id, Response::HTTP_FORBIDDEN);
    }
}
