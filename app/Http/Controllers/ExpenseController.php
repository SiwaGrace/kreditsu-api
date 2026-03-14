<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

/**
 * Controller for managing business expenses.
 *
 * This controller handles CRUD operations for expenses, ensuring that users can only
 * access and modify expenses belonging to their own business. It provides endpoints
 * for listing, creating, viewing, updating, and deleting expenses.
 */
class ExpenseController extends Controller
{
    /**
     * Get all expenses for the authenticated user's business.
     */
    public function index()
    {
        $business = Auth::user()?->business;

        if (! $business) {
            return response()->json([
                'message' => 'Business not found',
            ], Response::HTTP_NOT_FOUND);
        }

        $expenses = $business->expenses()->latest('date')->get();

        return response()->json([
            'message'  => 'Expenses fetched successfully',
            'expenses' => $expenses,
        ]);
    }

    /**
     * Create a new expense for the authenticated user's business.
     */
    public function store(Request $request)
    {
        $business = Auth::user()?->business;

        if (! $business) {
            return response()->json([
                'message' => 'Business not found',
            ], Response::HTTP_NOT_FOUND);
        }

        $validated = $request->validate([
            'amount' => ['required', 'numeric'],
            'description' => ['nullable', 'string'],
            'category' => ['required', 'in:rent,supplies,salaries,utilities,other'],
            'date' => ['required', 'date'],
        ]);

        $expense = $business->expenses()->create($validated);

        return response()->json([
            'message' => 'Expense created successfully',
            'expense' => $expense,
        ], Response::HTTP_CREATED);
    }

    /**
     * Get a specific expense by ID.
     */
    public function show(Expense $expense)
    {
        $this->authorizeExpense($expense);

        return response()->json([
            'message' => 'Expense fetched successfully',
            'expense' => $expense,
        ]);
    }

    /**
     * Update an existing expense.
     */
    public function update(Request $request, Expense $expense)
    {
        $this->authorizeExpense($expense);

        $validated = $request->validate([
            'amount' => ['sometimes', 'numeric'],
            'description' => ['nullable', 'string'],
            'category' => ['sometimes', 'in:rent,supplies,salaries,utilities,other'],
            'date' => ['sometimes', 'date'],
        ]);

        $expense->update($validated);

        return response()->json([
            'message' => 'Expense updated successfully',
            'expense' => $expense,
        ]);
    }

    /**
     * Delete an expense.
     */
    public function destroy(Expense $expense)
    {
        $this->authorizeExpense($expense);

        $expense->delete();

        return response()->json([
            'message' => 'Expense deleted successfully',
        ]);
    }

    /**
     * Authorize that the expense belongs to the authenticated user's business.
     */
    protected function authorizeExpense(Expense $expense): void
    {
        $business = Auth::user()?->business;

        abort_unless(
            $business && $expense->business_id === $business->id,
            Response::HTTP_FORBIDDEN
        );
    }
}
