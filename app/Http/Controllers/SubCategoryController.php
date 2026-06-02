<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\SubCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SubCategoryController extends Controller
{
    public function index()
    {
        $this->authorize('view sub categories');
        return view('sub_categories.index');
    }

    public function data()
    {
        $this->authorize('view sub categories');

        $subCategories = SubCategory::with(['category', 'createdBy'])->latest()->get();
        $canEdit       = auth()->user()->can('edit sub categories');
        $canDelete     = auth()->user()->can('delete sub categories');

        $data = $subCategories->map(function ($subCategory, $index) use ($canEdit, $canDelete) {
            $status = $canEdit
                ? '<div class="form-check form-switch mb-0"><input class="form-check-input sub-category-status-toggle" type="checkbox" role="switch" data-url="' . route('admin.sub-categories.toggle-status', $subCategory) . '" ' . ($subCategory->status === 'active' ? 'checked' : '') . ' /></div>'
                : status_badge($subCategory->status);

            $actions = '';
            if ($canEdit) {
                $actions .= '<button class="btn btn-sm btn-icon btn-label-info me-1" data-common-modal="' . route('admin.sub-categories.edit', $subCategory) . '" data-bs-toggle="tooltip" title="Edit"><i class="ti ti-pencil"></i></button>';
            }
            if ($canDelete) {
                $actions .= '<button class="btn btn-sm btn-icon btn-label-danger" data-common-delete="' . route('admin.sub-categories.destroy', $subCategory) . '" data-row-id="subcategory-row-' . $subCategory->id . '" data-bs-toggle="tooltip" title="Delete"><i class="ti ti-trash"></i></button>';
            }

            return [
                'index'      => $index + 1,
                'name'       => $subCategory->name,
                'category'   => $subCategory->category->name ?? '-',
                'slug'       => '<code>' . $subCategory->slug . '</code>',
                'status'     => $status,
                'created_by' => $subCategory->createdBy->name ?? '-',
                'created_at' => format_date($subCategory->created_at),
                'actions'    => $actions,
            ];
        });

        return response()->json(['status' => 'success', 'data' => $data]);
    }

    public function create()
    {
        $this->authorize('create sub categories');
        $categories = Category::where('status', 'active')->orderBy('name')->get();
        return view('sub_categories.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $this->authorize('create sub categories');

        $validator = Validator::make($request->all(), [
            'category_id' => ['required', 'exists:categories,id'],
            'name'        => ['required', 'string', 'max:100', 'unique:sub_categories,name'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'error',
                'message' => $validator->errors(),
            ], 422);
        }

        SubCategory::create([
            'category_id' => $request->category_id,
            'name'        => $request->name,
            'slug'        => generate_slug(SubCategory::class, $request->name),
            'status'      => $request->has('status') ? 'active' : 'inactive',
            'created_by'  => auth()->id(),
        ]);

        return response()->json([
            'status'  => 'success',
            'message' => 'Sub Category created successfully.',
        ]);
    }

    public function edit(SubCategory $subCategory)
    {
        $this->authorize('edit sub categories');
        $categories = Category::where('status', 'active')->orderBy('name')->get();
        return view('sub_categories.edit', compact('subCategory', 'categories'));
    }

    public function update(Request $request, SubCategory $subCategory)
    {
        $this->authorize('edit sub categories');

        $validator = Validator::make($request->all(), [
            'category_id' => ['required', 'exists:categories,id'],
            'name'        => ['required', 'string', 'max:100', 'unique:sub_categories,name,' . $subCategory->id],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'error',
                'message' => $validator->errors(),
            ], 422);
        }

        $subCategory->update([
            'category_id' => $request->category_id,
            'name'        => $request->name,
            'slug'        => generate_slug(SubCategory::class, $request->name, $subCategory->id),
            'status'      => $request->has('status') ? 'active' : 'inactive',
        ]);

        return response()->json([
            'status'  => 'success',
            'message' => 'Sub Category updated successfully.',
        ]);
    }

    public function toggleStatus(SubCategory $subCategory)
    {
        $this->authorize('edit sub categories');

        $subCategory->update([
            'status' => $subCategory->status === 'active' ? 'inactive' : 'active',
        ]);

        return response()->json([
            'status'  => 'success',
            'message' => 'Sub Category status updated successfully.',
        ]);
    }

    public function destroy(SubCategory $subCategory)
    {
        $this->authorize('delete sub categories');

        $subCategory->delete();

        return response()->json([
            'status'  => 'success',
            'message' => 'Sub Category deleted successfully.',
        ]);
    }
}
