<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\Location;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ExpenseController extends Controller
{
    public function index()
    {
        $this->authorize('view expenses');

        $user = auth()->user();
        $isRestricted = $user->location_id && !$user->hasRole('super-admin');
        if ($isRestricted) {
            $locations = Location::where('id', $user->location_id)->get();
        } else {
            $locations = Location::where('status', 1)->orderBy('name')->get();
        }

        $categories = Expense::CATEGORIES;
        $paymentMethods = Expense::PAYMENT_METHODS;

        return view('expenses.index', compact('locations', 'isRestricted', 'categories', 'paymentMethods'));
    }

    public function data(Request $request)
    {
        $this->authorize('view expenses');

        $user = auth()->user();
        $isRestricted = $user->location_id && !$user->hasRole('super-admin');

        $expenses = Expense::with(['location', 'createdBy'])
            ->when($isRestricted, fn ($q) => $q->where('location_id', $user->location_id))
            ->when(!$isRestricted && $request->location_id, fn ($q) => $q->where('location_id', $request->location_id))
            ->when($request->category, fn ($q) => $q->where('category', $request->category))
            ->when($request->payment_method, fn ($q) => $q->where('payment_method', $request->payment_method))
            ->when($request->start_date, fn ($q) => $q->whereDate('expense_date', '>=', $request->start_date))
            ->when($request->end_date, fn ($q) => $q->whereDate('expense_date', '<=', $request->end_date))
            ->orderByDesc('expense_date')
            ->orderByDesc('id')
            ->get();

        $canEdit = $user->can('edit expenses');
        $canDelete = $user->can('delete expenses');

        $data = $expenses->map(function ($expense, $index) use ($canEdit, $canDelete) {
            $actions = '';
            if ($canEdit || $canDelete) {
                $actions = '<div class="dropdown table-action-dropdown">';
                $actions .= '<button class="btn btn-sm btn-label-primary action-dropdown-btn dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false"><span>Actions</span></button>';
                $actions .= '<div class="dropdown-menu dropdown-menu-end action-dropdown-menu m-0">';
                if ($canEdit) {
                    $actions .= '<button class="dropdown-item" data-common-modal="' . route('admin.expenses.edit', $expense) . '"><i class="ti ti-pencil me-2"></i>Edit</button>';
                }
                if ($canDelete) {
                    if ($canEdit) {
                        $actions .= '<div class="dropdown-divider"></div>';
                    }
                    $actions .= '<button class="dropdown-item text-danger" data-common-delete="' . route('admin.expenses.destroy', $expense) . '" data-row-id="expense-row-' . $expense->id . '"><i class="ti ti-trash me-2"></i>Delete</button>';
                }
                $actions .= '</div></div>';
            }

            return [
                'index' => $index + 1,
                'title' => e($expense->title),
                'category' => '<span class="badge bg-label-secondary">' . e($expense->category) . '</span>',
                'amount' => format_price($expense->amount),
                'payment_method' => e($expense->payment_method),
                'location' => e($expense->location->name ?? '-'),
                'expense_date' => format_date($expense->expense_date),
                'created_by' => e($expense->createdBy->name ?? '-'),
                'actions' => $actions,
            ];
        });

        return response()->json(['status' => 'success', 'data' => $data])
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->header('Pragma', 'no-cache')
            ->header('Expires', 'Sat, 01 Jan 2000 00:00:00 GMT');
    }

    public function create()
    {
        $this->authorize('create expenses');

        $user = auth()->user();
        $isRestricted = $user->location_id && !$user->hasRole('super-admin');
        $locations = $isRestricted
            ? Location::where('id', $user->location_id)->get()
            : Location::where('status', 1)->orderBy('name')->get();

        $categories = Expense::CATEGORIES;
        $paymentMethods = Expense::PAYMENT_METHODS;

        return view('expenses.create', compact('locations', 'isRestricted', 'categories', 'paymentMethods'));
    }

    public function store(Request $request)
    {
        $this->authorize('create expenses');

        $user = auth()->user();
        $isRestricted = $user->location_id && !$user->hasRole('super-admin');

        if ($isRestricted) {
            $request->merge(['location_id' => $user->location_id]);
        }

        $validator = Validator::make($request->all(), [
            'title' => ['required', 'string', 'max:150'],
            'category' => ['required', 'string', 'in:' . implode(',', Expense::CATEGORIES)],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'expense_date' => ['required', 'date'],
            'payment_method' => ['required', 'string', 'in:' . implode(',', Expense::PAYMENT_METHODS)],
            'location_id' => ['required', 'exists:locations,id'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors(),
            ], 422);
        }

        Expense::create([
            'title' => $request->title,
            'category' => $request->category,
            'amount' => $request->amount,
            'expense_date' => $request->expense_date,
            'payment_method' => $request->payment_method,
            'location_id' => $request->location_id,
            'notes' => $request->notes,
            'created_by' => auth()->id(),
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Expense created successfully.',
        ]);
    }

    public function edit(Expense $expense)
    {
        $this->authorize('edit expenses');
        $this->guardLocationAccess($expense);

        $user = auth()->user();
        $isRestricted = $user->location_id && !$user->hasRole('super-admin');
        $locations = $isRestricted
            ? Location::where('id', $user->location_id)->get()
            : Location::where('status', 1)->orderBy('name')->get();

        $categories = Expense::CATEGORIES;
        $paymentMethods = Expense::PAYMENT_METHODS;

        return view('expenses.edit', compact('expense', 'locations', 'isRestricted', 'categories', 'paymentMethods'));
    }

    public function update(Request $request, Expense $expense)
    {
        $this->authorize('edit expenses');
        $this->guardLocationAccess($expense);

        $user = auth()->user();
        $isRestricted = $user->location_id && !$user->hasRole('super-admin');

        if ($isRestricted) {
            $request->merge(['location_id' => $user->location_id]);
        }

        $validator = Validator::make($request->all(), [
            'title' => ['required', 'string', 'max:150'],
            'category' => ['required', 'string', 'in:' . implode(',', Expense::CATEGORIES)],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'expense_date' => ['required', 'date'],
            'payment_method' => ['required', 'string', 'in:' . implode(',', Expense::PAYMENT_METHODS)],
            'location_id' => ['required', 'exists:locations,id'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors(),
            ], 422);
        }

        $expense->update([
            'title' => $request->title,
            'category' => $request->category,
            'amount' => $request->amount,
            'expense_date' => $request->expense_date,
            'payment_method' => $request->payment_method,
            'location_id' => $request->location_id,
            'notes' => $request->notes,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Expense updated successfully.',
        ]);
    }

    public function destroy(Expense $expense)
    {
        $this->authorize('delete expenses');
        $this->guardLocationAccess($expense);

        $expense->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Expense deleted successfully.',
        ]);
    }

    private function guardLocationAccess(Expense $expense): void
    {
        $user = auth()->user();
        if (!$user->location_id || $user->hasRole('super-admin')) {
            return;
        }

        if ((int) $expense->location_id !== (int) $user->location_id) {
            abort(403);
        }
    }
}
