<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class CategoryController extends Controller
{
    public function index()
    {
        $this->authorize('view categories');
        $categories = Category::with('createdBy')->orderBy('id', 'desc')->get();
        return view('categories.index', compact('categories'));
    }

    public function data(Request $request)
    {
        $this->authorize('view categories');

        $query = Category::with('createdBy')->orderBy('id', 'desc');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('is_featured')) {
            $query->where('is_featured', $request->is_featured);
        }

        $categories = $query->get();
        $canEdit    = auth()->user()->can('edit categories');
        $canDelete  = auth()->user()->can('delete categories');

        $data = $categories->map(function ($category, $index) use ($canEdit, $canDelete) {
            $image = $category->image
                ? '<img src="' . $category->image_url . '" width="40" height="40" class="rounded object-fit-cover product-thumbnail" style="cursor:pointer;" alt="' . e($category->name) . '">'
                : '<span class="badge bg-label-secondary">No Image</span>';

            $featured = $canEdit
                ? '<div class="form-check form-switch mb-0"><input class="form-check-input category-featured-toggle" type="checkbox" role="switch" data-url="' . route('admin.categories.toggle-featured', $category) . '" ' . ($category->is_featured ? 'checked' : '') . ' /></div>'
                : ($category->is_featured ? '<span class="badge bg-label-success">Yes</span>' : '<span class="badge bg-label-secondary">No</span>');

            $status = $canEdit
                ? '<div class="form-check form-switch mb-0"><input class="form-check-input category-status-toggle" type="checkbox" role="switch" data-url="' . route('admin.categories.toggle-status', $category) . '" ' . ($category->status == 1 ? 'checked' : '') . ' /></div>'
                : status_badge($category->status);

            $actions = '';
            if ($canEdit || $canDelete) {
                $actions = '<div class="dropdown table-action-dropdown">';
                $actions .= '<button class="btn btn-sm btn-label-primary action-dropdown-btn dropdown-toggle" data-bs-toggle="dropdown" data-bs-boundary="viewport" aria-expanded="false"><span>Actions</span></button>';
                $actions .= '<div class="dropdown-menu dropdown-menu-end action-dropdown-menu m-0">';
                if ($canEdit) {
                    $actions .= '<button class="dropdown-item" data-common-modal="' . route('admin.categories.edit', $category) . '"><i class="ti ti-pencil me-2"></i>Edit</button>';
                }
                if ($canDelete) {
                    if ($canEdit) {
                        $actions .= '<div class="dropdown-divider"></div>';
                    }
                    $actions .= '<button class="dropdown-item text-danger" data-common-delete="' . route('admin.categories.destroy', $category) . '" data-row-id="category-row-' . $category->id . '"><i class="ti ti-trash me-2"></i>Delete</button>';
                }
                $actions .= '</div></div>';
            }

            return [
                'id'                   => $category->id,
                'index'                => $index + 1,
                'image'                => $image,
                'name'                 => $category->name,
                'slug'                 => '<code>' . $category->slug . '</code>',
                'is_featured'          => $featured,
                'status'               => $status,
                'low_stock_threshold'  => (string) ($category->low_stock_threshold ?? Category::DEFAULT_LOW_STOCK_THRESHOLD),
                'created_at'           => format_date($category->created_at),
                'actions'              => $actions,
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
            'name'                 => ['required', 'string', 'max:100', Rule::unique('categories', 'name')->whereNull('deleted_at')],
            'image_base64'         => ['required', 'string'],
            'low_stock_threshold'  => ['required', 'integer', 'min:0'],
        ], [
            'image_base64.required' => 'The category image field is required.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'error',
                'message' => $validator->errors(),
            ], 422);
        }

        $imagePath = null;
        if ($request->filled('image_base64')) {
            $base64Data = $request->image_base64;
            if (preg_match('/^data:image\/(\w+);base64,/', $base64Data, $type)) {
                $dataString = substr($base64Data, strpos($base64Data, ',') + 1);
                $type = strtolower($type[1]);
                
                if (!in_array($type, ['jpg', 'jpeg', 'png', 'webp'])) {
                    return response()->json([
                        'status'  => 'error',
                        'message' => ['image' => ['The image must be a file of type: jpg, jpeg, png, webp.']],
                    ], 422);
                }
                
                $decodedData = base64_decode($dataString);
                if ($decodedData === false) {
                    return response()->json([
                        'status'  => 'error',
                        'message' => ['image' => ['Failed to decode image.']],
                    ], 422);
                }
                
                if (strlen($decodedData) > 52428800) {
                    return response()->json([
                        'status'  => 'error',
                        'message' => ['image' => ['The image size must be less than 50 MB.']],
                    ], 422);
                }
                
                $dir = public_path('uploads/categories');
                if (!file_exists($dir)) {
                    mkdir($dir, 0755, true);
                }
                
                $filename = time() . '_' . uniqid() . '.' . $type;
                file_put_contents($dir . '/' . $filename, $decodedData);
                $imagePath = 'categories/' . $filename;
            }
        }

        Category::create([
            'name'                => $request->name,
            'slug'                => generate_slug(Category::class, $request->name),
            'image'               => $imagePath,
            'status'              => $request->has('status') ? 1 : 2,
            'is_featured'         => $request->has('is_featured') ? true : false,
            'created_by'          => auth()->id(),
            'sort_order'          => ((int) Category::max('sort_order')) + 1,
            'low_stock_threshold' => (int) $request->low_stock_threshold,
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
            'name'                => ['required', 'string', 'max:100', Rule::unique('categories', 'name')->ignore($category->id)->whereNull('deleted_at')],
            'image_base64'        => ['nullable', 'string'],
            'low_stock_threshold' => ['required', 'integer', 'min:0'],
        ]);

        $validator->after(function ($validator) use ($request, $category) {
            $hasNewImage = $request->filled('image_base64');
            $isRemovingImage = $request->boolean('remove_image');
            $hasExistingImage = !empty($category->image);

            if (!$hasNewImage && ($isRemovingImage || !$hasExistingImage)) {
                $validator->errors()->add('image_base64', 'The category image field is required.');
            }
        });

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'error',
                'message' => $validator->errors(),
            ], 422);
        }

        $imagePath = $category->image;
        $imageUpdated = false;

        if ($request->filled('image_base64')) {
            $base64Data = $request->image_base64;
            if (preg_match('/^data:image\/(\w+);base64,/', $base64Data, $type)) {
                $dataString = substr($base64Data, strpos($base64Data, ',') + 1);
                $type = strtolower($type[1]);
                
                if (!in_array($type, ['jpg', 'jpeg', 'png', 'webp'])) {
                    return response()->json([
                        'status'  => 'error',
                        'message' => ['image' => ['The image must be a file of type: jpg, jpeg, png, webp.']],
                    ], 422);
                }
                
                $decodedData = base64_decode($dataString);
                if ($decodedData === false) {
                    return response()->json([
                        'status'  => 'error',
                        'message' => ['image' => ['Failed to decode image.']],
                    ], 422);
                }
                
                if (strlen($decodedData) > 52428800) {
                    return response()->json([
                        'status'  => 'error',
                        'message' => ['image' => ['The image size must be less than 50 MB.']],
                    ], 422);
                }
                
                if ($category->image) {
                    $oldFile = public_path('uploads/' . $category->image);
                    if (file_exists($oldFile)) {
                        @unlink($oldFile);
                    }
                }
                
                $dir = public_path('uploads/categories');
                if (!file_exists($dir)) {
                    mkdir($dir, 0755, true);
                }
                
                $filename = time() . '_' . uniqid() . '.' . $type;
                file_put_contents($dir . '/' . $filename, $decodedData);
                $imagePath = 'categories/' . $filename;
                $imageUpdated = true;
            }
        }

        if (!$imageUpdated && $request->boolean('remove_image')) {
            if ($category->image) {
                $oldFile = public_path('uploads/' . $category->image);
                if (file_exists($oldFile)) {
                    @unlink($oldFile);
                }
            }
            $imagePath = null;
        }

        $category->update([
            'name'                => $request->name,
            'slug'                => generate_slug(Category::class, $request->name, $category->id),
            'image'               => $imagePath,
            'status'              => $request->has('status') ? 1 : 2,
            'is_featured'         => $request->has('is_featured') ? true : false,
            'low_stock_threshold' => (int) $request->low_stock_threshold,
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
            'status' => $category->status == 1 ? 2 : 1,
        ]);

        return response()->json([
            'status'  => 'success',
            'message' => 'Category status updated successfully.',
        ]);
    }

    public function toggleFeatured(Category $category)
    {
        $this->authorize('edit categories');

        $category->update([
            'is_featured' => !$category->is_featured,
        ]);

        return response()->json([
            'status'  => 'success',
            'message' => 'Category featured status updated successfully.',
        ]);
    }

    public function destroy(Category $category)
    {
        $this->authorize('delete categories');

        $products = $category->products()->whereNull('products.deleted_at')->get()->unique('id');
        if ($products->count() > 0) {
            $totalProducts = $products->count();
            $visibleProducts = $products->take(10);

            $productListHtml = '<div class="text-start mt-3" style="font-size: 15px; color: #5d596c; padding-left: 15px;">';
            $productListHtml .= '<p class="mb-2 fw-bold text-dark" style="font-size: 15px;">Used in products:</p>';
            $index = 1;
            foreach ($visibleProducts as $prod) {
                $url = route('admin.products.show', $prod);
                $productListHtml .= '<div style="margin-bottom: 6px; display: flex; align-items: start; gap: 8px; font-size: 15px; line-height: 1.4;">';
                $productListHtml .= '<span style="font-weight: bold; color: #5d596c; min-width: 20px;">' . $index . '.</span>';
                $productListHtml .= '<a href="' . $url . '" target="_blank" style="color: #2f2b3d; text-decoration: none; font-weight: 500;">' . e($prod->name) . '</a>';
                $productListHtml .= '</div>';
                $index++;
            }
            if ($totalProducts > 10) {
                $productListHtml .= '<div class="text-muted mt-2" style="font-size: 13.5px; padding-left: 28px;"><em>+ ' . ($totalProducts - 10) . ' more products</em></div>';
            }
            $productListHtml .= '</div>';

            return response()->json([
                'status'  => 'error',
                'message' => "This category cannot be deleted because it is in use. First remove it from the products below: " . $productListHtml,
            ], 422);
        }

        if ($category->image) {
            $file = public_path('uploads/' . $category->image);
            if (file_exists($file)) {
                @unlink($file);
            }
        }

        $category->delete();

        return response()->json([
            'status'  => 'success',
            'message' => 'Category deleted successfully.',
        ]);
    }

}
