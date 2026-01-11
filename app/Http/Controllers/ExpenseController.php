<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\Trip;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class ExpenseController extends Controller
{
    public function __construct(
        protected AuditService $auditService
    ) {}

    /**
     * Display expenses for a trip.
     */
    public function index(Trip $trip): Response
    {
        Gate::authorize('view', $trip);

        $expenses = $trip->expenses()
            ->with('recorder:id,name')
            ->latest('expense_date')
            ->get()
            ->map(fn($expense) => [
                'id' => $expense->id,
                'category' => $expense->category,
                'category_label' => $expense->category_label,
                'description' => $expense->description,
                'amount' => (float) $expense->amount,
                'expense_date' => $expense->expense_date->format('Y-m-d'),
                'receipt_image' => $expense->receipt_image,
                'recorder' => $expense->recorder,
                'created_at' => $expense->created_at->format('Y-m-d H:i'),
            ]);

        // Group by category
        $byCategory = $trip->expenses()
            ->selectRaw('category, SUM(amount) as total')
            ->groupBy('category')
            ->get()
            ->mapWithKeys(fn($item) => [$item->category => (float) $item->total]);

        return Inertia::render('Trips/Expenses/Index', [
            'trip' => [
                'id' => $trip->id,
                'name' => $trip->name,
                'total_savings' => $trip->total_savings,
                'total_expenses' => $trip->total_expenses,
                'remaining_balance' => $trip->remaining_balance,
            ],
            'expenses' => $expenses,
            'byCategory' => $byCategory,
            'categories' => Expense::categories(),
            'isAdmin' => $trip->isAdmin(Auth::user()),
        ]);
    }

    /**
     * Store a new expense.
     */
    public function store(Request $request, Trip $trip): RedirectResponse
    {
        Gate::authorize('manageFinances', $trip);

        $validated = $request->validate([
            'category' => 'required|string|in:transport,food,accommodation,ticket,shopping,other',
            'description' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0',
            'expense_date' => 'required|date',
            'receipt_image' => 'nullable|image|max:2048',
        ]);

        if ($request->hasFile('receipt_image')) {
            $validated['receipt_image'] = $request->file('receipt_image')
                ->store('expenses/receipts', 'public');
        }

        $validated['trip_id'] = $trip->id;
        $validated['recorded_by'] = Auth::id();

        $expense = Expense::create($validated);

        $this->auditService->logExpenseRecorded($trip, $expense);

        return back()->with('success', 'Pengeluaran berhasil dicatat!');
    }

    /**
     * Update an expense.
     */
    public function update(Request $request, Trip $trip, Expense $expense): RedirectResponse
    {
        Gate::authorize('manageFinances', $trip);

        if ($expense->trip_id !== $trip->id) {
            abort(404);
        }

        $validated = $request->validate([
            'category' => 'required|string|in:transport,food,accommodation,ticket,shopping,other',
            'description' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0',
            'expense_date' => 'required|date',
            'receipt_image' => 'nullable|image|max:2048',
        ]);

        if ($request->hasFile('receipt_image')) {
            // Delete old image
            if ($expense->receipt_image) {
                Storage::disk('public')->delete($expense->receipt_image);
            }
            $validated['receipt_image'] = $request->file('receipt_image')
                ->store('expenses/receipts', 'public');
        }

        $expense->update($validated);

        return back()->with('success', 'Pengeluaran berhasil diperbarui!');
    }

    /**
     * Delete an expense.
     */
    public function destroy(Trip $trip, Expense $expense): RedirectResponse
    {
        Gate::authorize('manageFinances', $trip);

        if ($expense->trip_id !== $trip->id) {
            abort(404);
        }

        // Only allow deleting own expenses or admin
        if ($expense->recorded_by !== Auth::id() && !$trip->isAdmin(Auth::user())) {
            return back()->withErrors(['expense' => 'Tidak dapat menghapus pengeluaran ini.']);
        }

        // Delete receipt image
        if ($expense->receipt_image) {
            Storage::disk('public')->delete($expense->receipt_image);
        }

        $expense->delete();

        return back()->with('success', 'Pengeluaran berhasil dihapus!');
    }
}
