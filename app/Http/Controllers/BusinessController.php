<?php

namespace App\Http\Controllers;

use App\Models\Business;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BusinessController extends Controller
{
    // ─── Get authenticated user's business ────────────────────────
    public function show()
    {
        $business = Auth::user()->business;

        if (!$business) {
            return response()->json(['message' => 'No business found'], 404);
        }

        $totalSales    = $business->sales()->sum('amount');
    $totalExpenses = $business->expenses()->sum('amount');

        return response()->json([
            'business' => $business,'total_sales'    => $totalSales,
        'total_expenses' => $totalExpenses,
        'net'            => $totalSales - $totalExpenses,]);
    }

    // ─── Create business ──────────────────────────────────────────
    public function store(Request $request)
    {
        // prevent creating a second business
        if (Auth::user()->business) {
            return response()->json(['message' => 'You already have a business'], 422);
        }

        $validated = $request->validate([
            'name'           => ['required', 'string', 'max:255'],
            'type'           => ['nullable', 'in:sole_proprietor,partnership,limited'],
            'industry'       => ['nullable', 'string', 'max:255'],
            'description'    => ['nullable', 'string'],
            'location'       => ['nullable', 'string', 'max:255'],
            'phone'          => ['nullable', 'string', 'max:20'],
            'email'          => ['nullable', 'email', 'max:255'],
            'website'        => ['nullable', 'url', 'max:255'],
            'established_at' => ['nullable', 'date'],
        ]);

        $business = Business::create([
            ...$validated,
            'user_id' => Auth::id(),
            'slug'    => Business::generateSlug($validated['name']),
        ]);

        return response()->json([
            'message'  => 'Business created successfully',
            'business' => $business,
        ], 201);
    }

    // ─── Update business ──────────────────────────────────────────
    public function update(Request $request)
    {
        $business = Auth::user()->business;

        if (!$business) {
            return response()->json(['message' => 'No business found'], 404);
        }

        $validated = $request->validate([
            'name'           => ['sometimes', 'string', 'max:255'],
            'type'           => ['sometimes', 'nullable', 'in:sole_proprietor,partnership,limited'],
            'industry'       => ['sometimes', 'nullable', 'string', 'max:255'],
            'description'    => ['sometimes', 'nullable', 'string'],
            'location'       => ['sometimes', 'nullable', 'string', 'max:255'],
            'phone'          => ['sometimes', 'nullable', 'string', 'max:20'],
            'email'          => ['sometimes', 'nullable', 'email', 'max:255'],
            'website'        => ['sometimes', 'nullable', 'url', 'max:255'],
            'established_at' => ['sometimes', 'nullable', 'date'],
            'is_published'   => ['sometimes', 'boolean'],
        ]);

        // regenerate slug if name changed
        if (isset($validated['name']) && $validated['name'] !== $business->name) {
            $validated['slug'] = Business::generateSlug($validated['name']);
        }

        $business->update($validated);

        return response()->json([
            'message'  => 'Business updated successfully',
            'business' => $business->fresh(),
        ]);
    }

    // ─── Public profile (no auth required) ────────────────────────
    public function profile(string $slug)
    {
        $business = Business::where('slug', $slug)
            ->where('is_published', true)
            ->first();

        if (!$business) {
            return response()->json(['message' => 'Business not found'], 404);
        }

        return response()->json([
            'business' => $business,
        'is_active_trader'  => $business->isActiveTrader()
        ]);
    }

    // ─── Directory of all businesses (no auth required) ─────────────────────────────
    public function index()
    {
        $businesses = Business::where('is_published', true)
            ->latest()
            ->paginate(12);

        return response()->json($businesses);
    }
}