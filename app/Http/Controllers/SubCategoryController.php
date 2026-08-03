<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\SubCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class SubCategoryController extends Controller
{
    public function index()
    {
        $this->authorize('view sub categories');
        $categories = Category::where('status', 1)->orderBy('name')->get();
        return view('sub_categories.index', compact('categories'));
    }

    public function data(Request $request)
    {
        $this->authorize('view sub categories');

        $query = SubCategory::with(['category', 'createdBy'])->orderBy('id', 'desc');

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $subCategories = $query->get();
        $canEdit       = auth()->user()->can('edit sub categories');
        $canDelete     = auth()->user()->can('delete sub categories');

        $data = $subCategories->map(function ($subCategory, $index) use ($canEdit, $canDelete) {
            $status = $canEdit
                ? '<div class="form-check form-switch mb-0"><input class="form-check-input sub-category-status-toggle" type="checkbox" role="switch" data-url="' . route('admin.sub-categories.toggle-status', $subCategory) . '" ' . ($subCategory->status == 1 ? 'checked' : '') . ' /></div>'
                : status_badge($subCategory->status);

            $actions = '';
            if ($canEdit || $canDelete) {
                $actions = '<div class="dropdown table-action-dropdown">';
                $actions .= '<button class="btn btn-sm btn-label-primary action-dropdown-btn dropdown-toggle" data-bs-toggle="dropdown" data-bs-boundary="viewport" aria-expanded="false"><span>Actions</span></button>';
                $actions .= '<div class="dropdown-menu dropdown-menu-end action-dropdown-menu m-0">';
                if ($canEdit) {
                    $actions .= '<button class="dropdown-item" data-common-modal="' . route('admin.sub-categories.edit', $subCategory) . '"><i class="ti ti-pencil me-2"></i>Edit</button>';
                }
                if ($canDelete) {
                    if ($canEdit) {
                        $actions .= '<div class="dropdown-divider"></div>';
                    }
                    $actions .= '<button class="dropdown-item text-danger" data-common-delete="' . route('admin.sub-categories.destroy', $subCategory) . '" data-row-id="subcategory-row-' . $subCategory->id . '"><i class="ti ti-trash me-2"></i>Delete</button>';
                }
                $actions .= '</div></div>';
            }

            return [
                'id'         => $subCategory->id,
                'index'      => $index + 1,
                'name'       => $subCategory->name,
                'category'   => $subCategory->category->name ?? '-',
                'slug'       => '<code>' . $subCategory->slug . '</code>',
                'status'     => $status,
                'created_at' => format_date($subCategory->created_at),
                'actions'    => $actions,
            ];
        });

        return response()->json(['status' => 'success', 'data' => $data]);
    }

    public function create()
    {
        $this->authorize('create sub categories');
        $categories = Category::where('status', 1)->orderBy('name')->get();
        return view('sub_categories.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $this->authorize('create sub categories');

        $validator = Validator::make($request->all(), [
            'category_id' => ['required', 'exists:categories,id'],
            'name'        => ['required', 'string', 'max:100', Rule::unique('sub_categories', 'name')->whereNull('deleted_at')],
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
            'status'      => $request->has('status') ? 1 : 2,
            'created_by'  => auth()->id(),
            'sort_order'  => ((int) SubCategory::max('sort_order')) + 1,
        ]);

        return response()->json([
            'status'  => 'success',
            'message' => 'Sub Category created successfully.',
        ]);
    }

    public function edit(SubCategory $subCategory)
    {
        $this->authorize('edit sub categories');
        $categories = Category::where('status', 1)->orderBy('name')->get();
        return view('sub_categories.edit', compact('subCategory', 'categories'));
    }

    public function update(Request $request, SubCategory $subCategory)
    {
        $this->authorize('edit sub categories');

        $validator = Validator::make($request->all(), [
            'category_id' => ['required', 'exists:categories,id'],
            'name'        => ['required', 'string', 'max:100', Rule::unique('sub_categories', 'name')->ignore($subCategory->id)->whereNull('deleted_at')],
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
            'status'      => $request->has('status') ? 1 : 2,
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
            'status' => $subCategory->status == 1 ? 2 : 1,
        ]);

        return response()->json([
            'status'  => 'success',
            'message' => 'Sub Category status updated successfully.',
        ]);
    }

    public function destroy(SubCategory $subCategory)
    {
        $this->authorize('delete sub categories');

        $products = \App\Models\Product::where('sub_category_id', $subCategory->id)->get()->unique('id');
        if ($products->count() > 0) {
            $productData = $products->map(function ($prod) {
                return [
                    'id'      => $prod->id,
                    'name'    => $prod->name,
                    'barcode' => $prod->barcode ?? null,
                    'url'     => route('admin.products.show', $prod),
                ];
            })->values()->all();

            return response()->json([
                'status'   => 'error',
                'in_use'   => true,
                'title'    => 'Cannot Delete SubCategory',
                'message'  => 'This subcategory cannot be deleted because it is in use by ' . count($productData) . ' product(s). Please remove it from these products first:',
                'products' => $productData,
            ], 422);
        }

        $subCategory->delete();

        return response()->json([
            'status'  => 'success',
            'message' => 'Sub Category deleted successfully.',
        ]);
    }

}
