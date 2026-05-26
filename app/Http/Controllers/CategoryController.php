<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CategoryController extends Controller
{
    public function index()
    {
        $this->authorize('view categories');
        $categories = Category::with('createdBy')->latest()->get();
        return view('categories.index', compact('categories'));
    }

    public function data()
    {
        $this->authorize('view categories');

        $categories = Category::with('createdBy')->latest()->get();
        $canEdit    = auth()->user()->can('edit categories');
        $canDelete  = auth()->user()->can('delete categories');

        $data = $categories->map(function ($category, $index) use ($canEdit, $canDelete) {
            $status = $canEdit
                ? '<div class="form-check form-switch mb-0"><input class="form-check-input category-status-toggle" type="checkbox" role="switch" data-url="' . route('admin.categories.toggle-status', $category) . '" ' . ($category->status === 'active' ? 'checked' : '') . ' /></div>'
                : status_badge($category->status);

            $actions = '';
            if ($canEdit) {
                $actions .= '<button class="btn btn-sm btn-icon btn-label-info me-1" data-common-modal="' . route('admin.categories.edit', $category) . '"><i class="ti ti-pencil"></i></button>';
            }
            if ($canDelete) {
                $actions .= '<button class="btn btn-sm btn-icon btn-label-danger" data-common-delete="' . route('admin.categories.destroy', $category) . '" data-row-id="category-row-' . $category->id . '"><i class="ti ti-trash"></i></button>';
            }

            return [
                'index'      => $index + 1,
                'name'       => $category->name,
                'slug'       => '<code>' . $category->slug . '</code>',
                'status'     => $status,
                'created_by' => $category->createdBy->name ?? '-',
                'created_at' => format_date($category->created_at),
                'actions'    => $actions,
            ];
        });

        return response()->json(['status' => 'success', 'data' => $data]);
    }

    public function create()
    {
        $this->authorize('create categories');
        return view('categories.create');
    }

    public function store(Request $request)
    {
        $this->authorize('create categories');

        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:100', 'unique:categories,name'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'error',
                'message' => $validator->errors(),
            ], 422);
        }

        Category::create([
            'name'       => $request->name,
            'slug'       => generate_slug(Category::class, $request->name),
            'status'     => $request->has('status') ? 'active' : 'inactive',
            'created_by' => auth()->id(),
        ]);

        return response()->json([
            'status'  => 'success',
            'message' => 'Category created successfully.',
        ]);
    }

    public function edit(Category $category)
    {
        $this->authorize('edit categories');
        return view('categories.edit', compact('category'));
    }

    public function update(Request $request, Category $category)
    {
        $this->authorize('edit categories');

        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:100', 'unique:categories,name,' . $category->id],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'error',
                'message' => $validator->errors(),
            ], 422);
        }

        $category->update([
            'name'   => $request->name,
            'slug'   => generate_slug(Category::class, $request->name, $category->id),
            'status' => $request->has('status') ? 'active' : 'inactive',
        ]);

        return response()->json([
            'status'  => 'success',
            'message' => 'Category updated successfully.',
        ]);
    }

    public function toggleStatus(Category $category)
    {
        $this->authorize('edit categories');

        $category->update([
            'status' => $category->status === 'active' ? 'inactive' : 'active',
        ]);

        return response()->json([
            'status'  => 'success',
            'message' => 'Category status updated successfully.',
        ]);
    }

    public function destroy(Category $category)
    {
        $this->authorize('delete categories');

        $category->delete();

        return response()->json([
            'status'  => 'success',
            'message' => 'Category deleted successfully.',
        ]);
    }
}
