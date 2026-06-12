<?php

namespace App\Http\Controllers;

use App\Models\Attribute;
use App\Models\Category;
use App\Models\SubCategory;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ProductController extends Controller
{
    public function index()
    {
        $this->authorize('view products');
        return view('products.index');
    }

    public function data()
    {
        $this->authorize('view products');

        $user = auth()->user();
        $products = Product::with([
            'category', 
            'primaryImage', 
            'inventories' => function($q) use ($user) {
                $q->when($user->location_id && $user->type !== 'super-admin', fn($sub) => $sub->where('location_id', $user->location_id));
            }
        ])->orderBy('sort_order')->get();

        $canEdit   = auth()->user()->can('edit products');
        $canDelete = auth()->user()->can('delete products');
        $canClone  = auth()->user()->can('clone products');

        $data = $products->map(function ($product, $index) use ($canEdit, $canDelete, $canClone) {
            $nameHtml = $product->is_variable
                ? $product->name . ' <span class="badge bg-label-info ms-1" style="font-size:10px">Variable</span>'
                : $product->name;

            $image = $product->primaryImage
                ? '<img src="' . $product->primaryImage->image_url . '" width="45" height="45" class="rounded object-fit-cover">'
                : '<span class="badge bg-label-secondary">No Image</span>';

            $status = $product->status == 1
                ? '<span class="badge bg-label-success">Active</span>'
                : '<span class="badge bg-label-danger">Inactive</span>';

            $stockSum = $product->inventories->sum('quantity');
            $stock = $stockSum > 0
                ? '<span class="badge bg-label-success fw-bold">' . number_format($stockSum) . '</span>'
                : '<span class="badge bg-label-danger fw-bold">Out of stock</span>';

            $actions = '<div class="dropdown table-action-dropdown">';
            $actions .= '<button class="btn btn-sm btn-label-primary action-dropdown-btn dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false"><span>Actions</span></button>';
            $actions .= '<div class="dropdown-menu dropdown-menu-end action-dropdown-menu m-0">';
            $actions .= '<a href="' . route('admin.products.show', $product) . '" class="dropdown-item"><i class="ti ti-eye me-2"></i>View</a>';
            if ($canEdit) {
                $actions .= '<a href="' . route('admin.products.edit', $product) . '" class="dropdown-item"><i class="ti ti-pencil me-2"></i>Edit</a>';
            }
            if ($canClone) {
                $actions .= '<a href="' . route('admin.products.create', ['clone_id' => $product->id]) . '" class="dropdown-item"><i class="ti ti-copy me-2"></i>Clone</a>';
            }
            if ($canDelete) {
                if ($canEdit || $canClone) {
                    $actions .= '<div class="dropdown-divider"></div>';
                }
                $actions .= '<button class="dropdown-item text-danger" data-common-delete="' . route('admin.products.destroy', $product) . '" data-row-id="product-row-' . $product->id . '"><i class="ti ti-trash me-2"></i>Delete</button>';
            }
            $actions .= '</div></div>';

            return [
                'id'             => $product->id,
                'index'          => $index + 1,
                'image'          => $image,
                'name'           => $nameHtml,
                'sku'            => '<code>' . $product->sku . '</code>',
                'category'       => $product->category->name ?? '-',
                'stock'          => $stock,
                'purchase_price' => format_price($product->purchase_price),
                'sale_price'     => format_price($product->sale_price),
                'status'         => $status,
                'actions'        => $actions,
            ];
        });

        return response()->json(['status' => 'success', 'data' => $data]);
    }

    public function show(Product $product)
    {
        $this->authorize('view products');

        $user = auth()->user();
        $product->load([
            'category', 
            'images', 
            'createdBy', 
            'variants.attributeValue',
            'inventories' => function($q) use ($user) {
                $q->when($user->location_id && $user->type !== 'super-admin', fn($sub) => $sub->where('location_id', $user->location_id));
            },
            'inventories.location'
        ]);
        return view('products.show', compact('product'));
    }

    public function create(Request $request)
    {
        $this->authorize('create products');
        $categories = Category::where('status', 1)->orderBy('name')->get();
        $attributes = Attribute::with('values')->where('status', 1)->orderBy('name')->get();

        $clonedProduct = null;
        $subCategories = collect();
        if ($request->filled('clone_id')) {
            $this->authorize('clone products');
            $clonedProduct = Product::with('images')->findOrFail($request->clone_id);
            $subCategories = SubCategory::where('category_id', $clonedProduct->category_id)
                ->where('status', 1)
                ->orderBy('name')
                ->get();
        }

        return view('products.create', compact('categories', 'clonedProduct', 'subCategories', 'attributes'));
    }

    public function store(Request $request)
    {
        $this->authorize('create products');

        $isCloning = $request->filled('cloned_from_id');

        $rules = [
            'name'                     => ['required', 'string', 'max:200'],
            'category_id'              => ['required', 'exists:categories,id'],
            'sub_category_id'          => ['nullable', 'exists:sub_categories,id'],
            'sku'                      => ['required', 'string', 'max:100', 'unique:products,sku'],
            'description'              => ['required', 'string'],
            'additional_information'   => ['required', 'string'],
            'type'                     => ['required', 'in:normal,variable'],
            'primary_image_base64'     => [$isCloning ? 'nullable' : 'required', 'string'],
            'additional_images_base64' => [$isCloning ? 'nullable' : 'required', 'array', $isCloning ? 'nullable' : 'min:1'],
            'additional_images_base64.*' => ['required_with:additional_images_base64', 'string'],
        ];

        $rules['purchase_price'] = ['required', 'numeric', 'min:0'];
        $rules['sale_price'] = ['required', 'numeric', 'min:0'];

        if ($request->type === 'variable') {
            $rules['variants_json'] = ['required', 'json'];
        }

        $validator = Validator::make($request->all(), $rules, [
            'primary_image_base64.required'     => 'The primary image field is required.',
            'additional_images_base64.required'  => 'At least one additional image is required.',
        ]);

        $validator->after(function ($validator) use ($request) {
            if ($request->filled('cloned_from_id')) {
                $hasNewPrimary = $request->filled('primary_image_base64');
                $isRemovingPrimary = $request->boolean('remove_cloned_primary');
                
                if ($isRemovingPrimary && !$hasNewPrimary) {
                    $validator->errors()->add('primary_image_base64', 'The primary image field is required.');
                }

                $hasNewAdditional = $request->filled('additional_images_base64') && count($request->additional_images_base64) > 0;
                $hasKeptClonedAdditional = $request->filled('existing_cloned_images') && count($request->existing_cloned_images) > 0;
                
                if (!$hasNewAdditional && !$hasKeptClonedAdditional) {
                    $validator->errors()->add('additional_images_base64', 'At least one additional image is required.');
                }
            }
        });

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'error',
                'message' => $validator->errors(),
            ], 422);
        }

        DB::transaction(function () use ($request) {
            $productData = [
                'name'            => $request->name,
                'slug'            => generate_slug(Product::class, $request->name),
                'category_id'     => $request->category_id,
                'sub_category_id' => $request->sub_category_id,
                'sku'             => $request->sku,
                'description'     => $request->description,
                'additional_information' => $request->additional_information,
                'type'            => $request->type,
                'status'          => $request->has('status') ? 1 : 2,
                'created_by'      => auth()->id(),
                'sort_order'      => ((int) Product::max('sort_order')) + 1,
            ];

            $productData['purchase_price'] = $request->purchase_price;
            $productData['sale_price'] = $request->sale_price;

            $product = Product::create($productData);

            // Primary image
            if ($request->filled('primary_image_base64')) {
                $imagePath = $this->saveBase64Image($request->primary_image_base64);
                if ($imagePath) {
                    ProductImage::create([
                        'product_id' => $product->id,
                        'image_path' => $imagePath,
                        'is_primary' => true,
                        'created_by' => auth()->id(),
                    ]);
                }
            } elseif ($request->filled('cloned_from_id') && !$request->boolean('remove_cloned_primary')) {
                $originalPrimary = ProductImage::where('product_id', $request->cloned_from_id)
                    ->where('is_primary', true)
                    ->first();
                if ($originalPrimary) {
                    $newImagePath = $this->copyProductImageFile($originalPrimary->image_path);
                    ProductImage::create([
                        'product_id' => $product->id,
                        'image_path' => $newImagePath ?: $originalPrimary->image_path,
                        'is_primary' => true,
                        'created_by' => auth()->id(),
                    ]);
                }
            }

            // Additional images
            // 1. Copy kept cloned ones
            if ($request->filled('cloned_from_id') && $request->filled('existing_cloned_images')) {
                $originalAdditionals = ProductImage::where('product_id', $request->cloned_from_id)
                    ->where('is_primary', false)
                    ->whereIn('id', $request->existing_cloned_images)
                    ->get();
                foreach ($originalAdditionals as $originalImg) {
                    $newImagePath = $this->copyProductImageFile($originalImg->image_path);
                    ProductImage::create([
                        'product_id' => $product->id,
                        'image_path' => $newImagePath ?: $originalImg->image_path,
                        'is_primary' => false,
                        'created_by' => auth()->id(),
                    ]);
                }
            }

            // 2. Save newly uploaded ones
            if ($request->filled('additional_images_base64')) {
                foreach ($request->additional_images_base64 as $base64) {
                    $imagePath = $this->saveBase64Image($base64);
                    if ($imagePath) {
                        ProductImage::create([
                            'product_id' => $product->id,
                            'image_path' => $imagePath,
                            'is_primary' => false,
                            'created_by' => auth()->id(),
                        ]);
                    }
                }
            }

            // Create variants for variable products
            if ($request->type === 'variable' && $request->filled('variants_json')) {
                $variants = json_decode($request->variants_json, true);
                foreach ($variants as $item) {
                    ProductVariant::create([
                        'product_id'         => $product->id,
                        'attribute_value_id' => $item['attribute_value_id'],
                        'purchase_price'     => $item['purchase_price'] ?? 0,
                        'sale_price'         => $item['sale_price'] ?? 0,
                        'status'             => ($item['status'] ?? 1) == 1 ? 1 : 2,
                    ]);
                }
            }
        });

        return response()->json([
            'status'  => 'success',
            'message' => 'Product created successfully.',
        ]);
    }

    public function edit(Product $product)
    {
        $this->authorize('edit products');
        $categories = Category::where('status', 1)->orderBy('name')->get();
        $subCategories = SubCategory::where('category_id', $product->category_id)
            ->where('status', 1)
            ->orderBy('name')
            ->get();
        $attributes = Attribute::with('values')->where('status', 1)->orderBy('name')->get();
        $product->load('images', 'variants.attributeValue');
        return view('products.edit', compact('product', 'categories', 'subCategories', 'attributes'));
    }

    public function update(Request $request, Product $product)
    {
        $this->authorize('edit products');

        $rules = [
            'name'                     => ['required', 'string', 'max:200'],
            'category_id'              => ['required', 'exists:categories,id'],
            'sub_category_id'          => ['nullable', 'exists:sub_categories,id'],
            'sku'                      => ['required', 'string', 'max:100', 'unique:products,sku,' . $product->id],
            'description'              => ['required', 'string'],
            'additional_information'   => ['required', 'string'],
            'type'                     => ['required', 'in:normal,variable'],
            'primary_image_base64'     => ['nullable', 'string'],
            'additional_images_base64' => ['nullable', 'array'],
            'additional_images_base64.*' => ['nullable', 'string'],
        ];

        $rules['purchase_price'] = ['required', 'numeric', 'min:0'];
        $rules['sale_price'] = ['required', 'numeric', 'min:0'];

        if ($request->type === 'variable') {
            $rules['variants_json'] = ['required', 'json'];
        }

        $validator = Validator::make($request->all(), $rules);

        $validator->after(function ($validator) use ($request, $product) {
            // Primary Image validation
            $hasNewPrimary = $request->filled('primary_image_base64');
            $isRemovingPrimary = $request->boolean('remove_primary_image');
            $hasExistingPrimary = $product->images()->where('is_primary', true)->exists();

            if (!$hasNewPrimary && ($isRemovingPrimary || !$hasExistingPrimary)) {
                $validator->errors()->add('primary_image_base64', 'The primary image field is required.');
            }

            // Additional Images validation
            $hasNewAdditional = $request->filled('additional_images_base64') && count($request->additional_images_base64) > 0;
            $deletedIds = $request->input('deleted_additional_images', []);
            $hasExistingAdditional = $product->images()
                ->where('is_primary', false)
                ->whereNotIn('id', $deletedIds)
                ->exists();

            if (!$hasNewAdditional && !$hasExistingAdditional) {
                $validator->errors()->add('additional_images_base64', 'At least one additional image is required.');
            }
        });

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'error',
                'message' => $validator->errors(),
            ], 422);
        }

        DB::transaction(function () use ($request, $product) {
            $productData = [
                'name'            => $request->name,
                'slug'            => generate_slug(Product::class, $request->name, $product->id),
                'category_id'     => $request->category_id,
                'sub_category_id' => $request->sub_category_id,
                'sku'             => $request->sku,
                'description'     => $request->description,
                'additional_information' => $request->additional_information,
                'type'            => $request->type,
                'status'          => $request->has('status') ? 1 : 2,
            ];

            $productData['purchase_price'] = $request->purchase_price;
            $productData['sale_price'] = $request->sale_price;

            $product->update($productData);

            // Replace primary image
            if ($request->filled('primary_image_base64')) {
                $existing = $product->images()->where('is_primary', true)->first();
                if ($existing) {
                    $existingFile = public_path('uploads/' . $existing->image_path);
                    if (file_exists($existingFile)) {
                        @unlink($existingFile);
                    }
                    $existing->delete();
                }
                $imagePath = $this->saveBase64Image($request->primary_image_base64);
                if ($imagePath) {
                    ProductImage::create([
                        'product_id' => $product->id,
                        'image_path' => $imagePath,
                        'is_primary' => true,
                        'created_by' => auth()->id(),
                    ]);
                }
            }

            // Additional images
            if ($request->filled('additional_images_base64')) {
                foreach ($request->additional_images_base64 as $base64) {
                    $imagePath = $this->saveBase64Image($base64);
                    if ($imagePath) {
                        ProductImage::create([
                            'product_id' => $product->id,
                            'image_path' => $imagePath,
                            'is_primary' => false,
                            'created_by' => auth()->id(),
                        ]);
                    }
                }
            }

            // Delete additional images marked for removal
            if ($request->filled('deleted_additional_images')) {
                foreach ($request->deleted_additional_images as $imageId) {
                    $image = ProductImage::find($imageId);
                    if ($image && $image->product_id === $product->id && !$image->is_primary) {
                        $existingFile = public_path('uploads/' . $image->image_path);
                        if (file_exists($existingFile)) {
                            @unlink($existingFile);
                        }
                        $image->delete();
                    }
                }
            }

            // Update variants for variable products
            if ($request->type === 'variable' && $request->filled('variants_json')) {
                $product->variants()->delete();
                $variants = json_decode($request->variants_json, true);
                foreach ($variants as $item) {
                    ProductVariant::create([
                        'product_id'         => $product->id,
                        'attribute_value_id' => $item['attribute_value_id'],
                        'purchase_price'     => $item['purchase_price'] ?? 0,
                        'sale_price'         => $item['sale_price'] ?? 0,
                        'status'             => ($item['status'] ?? 1) == 1 ? 1 : 2,
                    ]);
                }
            } elseif ($request->type === 'normal') {
                $product->variants()->delete();
            }
        });

        return response()->json([
            'status'  => 'success',
            'message' => 'Product updated successfully.',
        ]);
    }

    public function destroyImage(ProductImage $image)
    {
        $this->authorize('edit products');

        $wasPrimary = $image->is_primary;
        $productId  = $image->product_id;

        $existingFile = public_path('uploads/' . $image->image_path);
        if (file_exists($existingFile)) {
            @unlink($existingFile);
        }
        $image->delete();

        if ($wasPrimary) {
            ProductImage::where('product_id', $productId)->first()?->update(['is_primary' => true]);
        }

        return response()->json([
            'status'  => 'success',
            'message' => 'Image deleted successfully.',
        ]);
    }

    public function setPrimaryImage(ProductImage $image)
    {
        $this->authorize('edit products');

        ProductImage::where('product_id', $image->product_id)->update(['is_primary' => false]);
        $image->update(['is_primary' => true]);

        return response()->json([
            'status'  => 'success',
            'message' => 'Primary image updated.',
        ]);
    }

    public function toggleStatus(Product $product)
    {
        $this->authorize('edit products');

        $product->update([
            'status' => $product->status == 1 ? 2 : 1,
        ]);

        return response()->json([
            'status'  => 'success',
            'message' => 'Product status updated successfully.',
        ]);
    }

    public function destroy(Product $product)
    {
        $this->authorize('delete products');

        foreach ($product->images as $image) {
            $existingFile = public_path('uploads/' . $image->image_path);
            if (file_exists($existingFile)) {
                @unlink($existingFile);
            }
        }

        $product->delete();

        return response()->json([
            'status'  => 'success',
            'message' => 'Product deleted successfully.',
        ]);
    }

    public function getSubCategories(Request $request)
    {
        $this->authorize('view products');
        $categoryId = $request->query('category_id');
        $subCategories = SubCategory::where('category_id', $categoryId)
            ->where('status', 1)
            ->orderBy('name')
            ->get(['id', 'name']);

        return response()->json($subCategories);
    }

    private function saveBase64Image($base64Data, $subDir = 'products')
    {
        if (preg_match('/^data:image\/(\w+);base64,/', $base64Data, $type)) {
            $dataString = substr($base64Data, strpos($base64Data, ',') + 1);
            $type = strtolower($type[1]);
            
            if (!in_array($type, ['jpg', 'jpeg', 'png', 'webp'])) {
                return null;
            }
            
            $decodedData = base64_decode($dataString);
            if ($decodedData === false) {
                return null;
            }
            
            if (strlen($decodedData) > 52428800) {
                return null;
            }
            
            $dir = public_path('uploads/' . $subDir);
            if (!file_exists($dir)) {
                mkdir($dir, 0755, true);
            }
            
            $filename = time() . '_' . uniqid() . '.' . $type;
            file_put_contents($dir . '/' . $filename, $decodedData);
            return $subDir . '/' . $filename;
        }
        return null;
    }

    private function copyProductImageFile($originalPath)
    {
        if (empty($originalPath)) {
            return null;
        }

        $sourceFile = public_path('uploads/' . $originalPath);
        if (file_exists($sourceFile)) {
            $pathInfo = pathinfo($originalPath);
            $newFilename = time() . '_' . uniqid() . '.' . ($pathInfo['extension'] ?? 'jpg');
            $subDir = $pathInfo['dirname'] ?? 'products';
            $destDir = public_path('uploads/' . $subDir);
            
            if (!file_exists($destDir)) {
                mkdir($destDir, 0755, true);
            }

            if (copy($sourceFile, $destDir . '/' . $newFilename)) {
                return $subDir . '/' . $newFilename;
            }
        }
        return null;
    }

    public function reorder(Request $request)
    {
        $this->authorize('reorder products');

        $validator = Validator::make($request->all(), [
            'order'              => ['required', 'array'],
            'order.*.id'         => ['required', 'exists:products,id'],
            'order.*.sort_order' => ['required', 'integer', 'min:0'],
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()], 422);
        }

        foreach ($request->order as $item) {
            Product::where('id', $item['id'])->update(['sort_order' => $item['sort_order']]);
        }

        return response()->json(['status' => 'success', 'message' => 'Order updated.']);
    }
}
