# Laravel Controller Review: Data Mutation Response Standards

**Review Date:** March 18, 2026  
**Standards Applied:**

- Every `store()` response must return the newly created resource with all fields
- Every `update()` response must return `$model->fresh()` not the in-memory instance
- Computed values must be recalculated fresh from database before returning
- Never return stale in-memory data after a mutation

---

## Summary

**Status:** 🔴 2 CRITICAL ISSUES FOUND

---

## Detailed Findings

### ✅ AuthController

**Status:** COMPLIANT

- **register()**: Returns freshly created user with limited fields (id, name, email) ✓
    - Data freshness: Guaranteed (just created)
    - Issue: Returns only subset of fields, but this is intentional for security

- **login()**: Returns `Auth::user()` which is freshly authenticated ✓
    - Not a data mutation operation

---

### ✅ BusinessController

**Status:** COMPLIANT

- **store()**: Returns newly created business ✓
    - Fresh from `Business::create()`
    - All fields included

- **update()**: Returns `$business->fresh()` ✓
    - Explicitly calls `fresh()` to reload from database
    - Correct pattern

- **show()**: Recalculates totals fresh from database ✓
    - `sum('amount')` queries DB, not cached values
    - Computed values are fresh each call

---

### ✅ BusinessDocumentController

**Status:** COMPLIANT

- **store()**: Returns newly created document from `$business->businessDocuments()->create()` ✓
    - Fresh from database

- **update()**: Returns `$businessDocument->fresh()` ✓
    - Explicitly calls `fresh()` to reload from database

---

### 🔴 **ExpenseController - CRITICAL ISSUE**

**Status:** NON-COMPLIANT

**Issue Location:** [ExpenseController.php - update() method](app/Http/Controllers/ExpenseController.php#L84)

```php
public function update(Request $request, Expense $expense)
{
    $this->authorizeExpense($expense);

    $validated = $request->validate([...]);

    $expense->update($validated);

    return response()->json([
        'message' => 'Expense updated successfully',
        'expense' => $expense,  // ❌ STALE IN-MEMORY DATA
    ]);
}
```

**Problems:**

1. Returns in-memory `$expense` instead of `$expense->fresh()`
2. The `ExpenseObserver` listens to `updated()` event and recalculates the associated Business's `kreditsu_score`
3. After mutation, the in-memory `$expense` object doesn't reflect any database-side changes
4. Frontend receives potentially stale data

**Impact:** High - Score calculations depend on fresh expense data

**Fix Required:** Change to `$expense->fresh()`

---

### 🔴 **SaleController - CRITICAL ISSUE**

**Status:** NON-COMPLIANT

**Issue Location:** [SaleController.php - update() method](app/Http/Controllers/SaleController.php#L84)

```php
public function update(Request $request, Sale $sale)
{
    $this->authorizeSale($sale);

    $validated = $request->validate([...]);

    $sale->update($validated);

    return response()->json([
        'message' => 'Sale updated successfully',
        'sale' => $sale,  // ❌ STALE IN-MEMORY DATA
    ]);
}
```

**Problems:**

1. Returns in-memory `$sale` instead of `$sale->fresh()`
2. The `SaleObserver` listens to `updated()` event and recalculates the associated Business's `kreditsu_score`
3. After mutation, the in-memory `$sale` object doesn't reflect any database-side changes
4. Frontend receives potentially stale data

**Impact:** High - Score calculations depend on fresh sale data

**Fix Required:** Change to `$sale->fresh()`

---

### ✅ BusinessSnapshotsController

**Status:** COMPLIANT

- **generate()**: Uses `updateOrCreate()` which returns a fresh model instance ✓
    - Calculated values are computed fresh before insert/update
    - Response includes fresh snapshot data

---

### ✅ ScoreController

**Status:** COMPLIANT

- **show()**: Read-only operation, not a mutation ✓
    - Calculations are fresh (`breakdown()` method recalculates on each call)

---

## Root Cause Analysis

Both `ExpenseController` and `SaleController` have Observer classes that:

1. **ExpenseObserver** - Recalculates business `kreditsu_score` on any expense mutation
2. **SaleObserver** - Recalculates business `kreditsu_score` on any sale mutation

This means the `update()` methods are **not simple updates** - they trigger side effects that could affect related models. Returning the in-memory instance is unsafe because:

- The in-memory instance doesn't know about observer-triggered changes
- Database has fresh data, instance has stale data
- Frontend receives incomplete/incorrect information

---

## Recommendations

### Priority 1: Fix Mutation Response

1. Update `ExpenseController::update()` to return `$expense->fresh()`
2. Update `SaleController::update()` to return `$sale->fresh()`

### Priority 2: Consider Response Enrichment

If frontend needs the updated business score after sale/expense mutations, consider:

```php
return response()->json([
    'sale' => $sale->fresh(),
    'business_score' => $sale->business->fresh()->kreditsu_score,
]);
```

---

## Testing Checklist

After fixes:

- [ ] Update expense, verify all fields returned are fresh
- [ ] Update sale, verify all fields returned are fresh
- [ ] Update sale and verify business score is current
- [ ] Update expense and verify business score is current
- [ ] Frontend receives up-to-date data without needing separate refetch
