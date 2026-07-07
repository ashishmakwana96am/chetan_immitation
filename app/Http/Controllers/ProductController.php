<?php

namespace App\Http\Controllers;

use App\Models\Attribute;
use App\Models\Category;
use App\Models\SubCategory;
use App\Models\Product;
use App\Models\Location;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Models\AttributeValue;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Picqer\Barcode\BarcodeGeneratorSVG;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

class ProductController extends Controller
{
    public function index()
    {
        $this->authorize('view products');
        $categories = Category::orderBy('name')->get();

        $user = auth()->user();
        $isRestricted = $user->location_id && !$user->hasRole('super-admin');
        if ($isRestricted) {
            $locations = Location::where('id', $user->location_id)->get();
        } else {
            $locations = Location::where('status', 1)->orderBy('name')->get();
        }

        return view('products.index', compact('categories', 'locations', 'isRestricted'));
    }

    public function data(Request $request)
    {
        $this->authorize('view products');

        $user        = auth()->user();
        $isRestricted = $user->location_id && !$user->hasRole('super-admin');

        $locationId = $request->location_id;
        if ($isRestricted) {
            $locationId = $user->location_id;
        }

        $query = Product::with([
            'category',
            'primaryImage',
            'inventories' => function($q) use ($locationId) {
                $q->when($locationId, fn($sub) => $sub->where('location_id', $locationId));
            }
        ])
        ->when($request->category_id, function($q) use ($request) {
            $q->where('category_id', $request->category_id);
        })
        ->when($request->status !== null && $request->status !== '', function($q) use ($request) {
            $q->where('status', $request->status);
        });

        if ($request->stock_status === 'in_stock') {
            $query->whereHas('inventories', function($q) use ($locationId) {
                $q->when($locationId, fn($sub) => $sub->where('location_id', $locationId));
                $q->where('quantity', '>', 0);
            });
        } elseif ($request->stock_status === 'out_of_stock') {
            $query->whereDoesntHave('inventories', function($q) use ($locationId) {
                $q->when($locationId, fn($sub) => $sub->where('location_id', $locationId));
                $q->where('quantity', '>', 0);
            });
        }

        $products = $query->orderBy('id', 'desc')->get();

        $canEdit   = auth()->user()->can('edit products');
        $canDelete = auth()->user()->can('delete products');
        $canClone  = auth()->user()->can('clone products');

        $data = $products->map(function ($product, $index) use ($canEdit, $canDelete, $canClone) {
            $nameHtml = $product->name;

            if ($product->is_variable) {
                $nameHtml .= ' <span class="badge bg-label-info ms-1" style="font-size:10px">Variable</span>';
            }

            $image = $product->primaryImage
                ? '<img src="' . $product->primaryImage->image_url . '" width="45" height="45" class="rounded object-fit-cover product-thumbnail" alt="' . e($product->name) . '">'
                : '<span class="badge bg-label-secondary">No Image</span>';

            $status = $product->status == 1
                ? '<span class="badge bg-label-success">Active</span>'
                : '<span class="badge bg-label-danger">Inactive</span>';

            $stockSum = $product->inventories->sum('quantity');
            $stock = $stockSum > 0
                ? '<span class="badge bg-label-success fw-bold">' . number_format($stockSum) . '</span>'
                : '<span class="badge bg-label-danger fw-bold">SOLD OUT</span>';

            $barcode = $product->barcode
                ? '<div class="d-flex align-items-center gap-2">
                    <code>' . $product->barcode . '</code>
                    <button onclick="viewBarcode(\'' . $product->barcode . '\', ' . $product->id . ')" class="btn btn-sm btn-icon btn-label-secondary" title="View Barcode">
                        <i class="ti ti-barcode"></i>
                    </button>
                   </div>'
                : '<span class="text-muted">-</span>';

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
                'barcode'        => $barcode,
                'raw_barcode'    => $product->barcode,
                'category'       => $product->category->name ?? '-',
                'stock'          => $stock,
                'purchase_price' => format_price($product->purchase_price),
                'sale_price'     => format_price($product->sale_price),
                'mrp'            => format_price($product->mrp),
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
        $isRestricted = $user->location_id && !$user->hasRole('super-admin');
        $locationId   = $isRestricted ? $user->location_id : null;

        $product->load([
            'category', 
            'images', 
            'createdBy', 
            'variants.attributeValue',
            'inventories' => function($q) use ($locationId) {
                $q->when($locationId, fn($sub) => $sub->where('location_id', $locationId));
            },
            'inventories.location'
        ]);
        return view('products.show', compact('product', 'locationId', 'isRestricted'));
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
            'barcode'                  => ['required', 'string', 'max:100', 'unique:products,barcode'],
            'description'              => ['required', 'string'],
            'additional_information'   => ['nullable', 'string'],
            'product_highlights'        => ['nullable', 'string'],
            'type'                     => ['required', 'in:normal,variable'],
            'sale'                     => ['nullable', 'boolean'],
            'primary_image_base64'     => [$isCloning ? 'nullable' : 'required', 'string'],
            'additional_images_base64' => [$isCloning ? 'nullable' : 'required', 'array', $isCloning ? 'nullable' : 'min:1'],
            'additional_images_base64.*' => ['required_with:additional_images_base64', 'string'],
        ];

        $rules['product_code'] = ['required', 'numeric', 'min:0.01'];
        $rules['purchase_price'] = ['required', 'numeric', 'min:0'];
        $rules['sale_price'] = ['required', 'numeric', 'min:0'];
        $rules['mrp'] = ['required', 'numeric', 'min:0'];

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
                'barcode'         => $request->barcode,
                'product_code'    => $request->product_code,
                'description'     => $request->description,
                'additional_information' => $request->additional_information,
                'product_highlights' => $request->product_highlights,
                'type'            => $request->type,
                'status'          => $request->has('status') ? 1 : 2,
                'sale'            => $request->has('sale') ? 1 : 0,
                'created_by'      => auth()->id(),
                'sort_order'      => ((int) Product::max('sort_order')) + 1,
            ];

            $productData['purchase_price'] = $request->purchase_price;
            $productData['sale_price']     = $request->sale_price;
            $productData['mrp']            = $request->mrp;

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
            'barcode'                  => ['required', 'string', 'max:100', 'unique:products,barcode,' . $product->id],
            'description'              => ['required', 'string'],
            'additional_information'   => ['nullable', 'string'],
            'product_highlights'        => ['nullable', 'string'],
            'type'                     => ['required', 'in:normal,variable'],
            'sale'                     => ['nullable', 'boolean'],
            'primary_image_base64'     => ['nullable', 'string'],
            'additional_images_base64' => ['nullable', 'array'],
            'additional_images_base64.*' => ['nullable', 'string'],
        ];

        $rules['product_code'] = ['required', 'numeric', 'min:0.01'];
        $rules['purchase_price'] = ['required', 'numeric', 'min:0'];
        $rules['sale_price'] = ['required', 'numeric', 'min:0'];
        $rules['mrp'] = ['required', 'numeric', 'min:0'];

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
                'category_id'     => $request->category_id,
                'sub_category_id' => $request->sub_category_id,
                'sku'             => $request->sku,
                'barcode'         => $request->barcode,
                'product_code'    => $request->product_code,
                'description'     => $request->description,
                'additional_information' => $request->additional_information,
                'product_highlights' => $request->product_highlights,
                'type'            => $request->type,
                'status'          => $request->has('status') ? 1 : 2,
                'sale'            => $request->has('sale') ? 1 : 0,
            ];

            $productData['purchase_price'] = $request->purchase_price;
            $productData['sale_price']     = $request->sale_price;
            $productData['mrp']            = $request->mrp;

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

    public function search(Request $request)
    {
        $this->authorize('view products');

        $query = Product::where('status', 1)
            ->when($request->q, function ($q) use ($request) {
                $q->where(function ($sub) use ($request) {
                    $sub->where('name', 'like', '%' . $request->q . '%')
                        ->orWhere('sku', 'like', '%' . $request->q . '%');
                });
            })
            ->orderBy('name')
            ->limit(50)
            ->get(['id', 'name', 'sku']);

        return response()->json($query->map(function ($product) {
            return [
                'id' => $product->id,
                'text' => $product->name . ' (' . $product->sku . ')',
                'name' => $product->name,
                'sku' => $product->sku,
            ];
        }));
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

    public function generateBarcodeImage(Product $product)
    {
        $this->authorize('view products');

        if (empty($product->barcode)) {
            return response()->json(['status' => 'error', 'message' => 'No barcode found for this product'], 404);
        }

        $generator = new BarcodeGeneratorSVG();
        $barcode = $generator->getBarcode($product->barcode, $generator::TYPE_CODE_128);

        return response($barcode, 200)->header('Content-Type', 'image/svg+xml');
    }


    public function importForm()
    {
        $this->authorize('create products');

        return view('products.import');
    }

    public function downloadSampleCsv()
    {
        $this->authorize('create products');

        $columns = [
            'Category', 'Sub Category', 'Name', 'No.', 'SKU', 'Barcode',
            'Product Code', 'Discreptions', 'Product Type', 'Size', 'Colour',
        ];

        // 2 categories x 5 products, using SKU/Barcode left blank so they auto-generate from "No."
        $rows = [
            ['Necklace', 'Short Necklace (R)', 'Short Necklace Regular', 'SNR', '', '', '100.00', 'Traditional short necklace - regular finish', 'normal', '', ''],
            ['Necklace', 'Short Necklace (A)', 'Short Necklace Antique', 'SNA', '', '', '110.00', 'Traditional short necklace - antique finish', 'normal', '', ''],
            ['Necklace', 'Long Necklace (R)', 'Long Necklace Regular', 'LNR', '', '', '150.00', 'Elegant long necklace - regular finish', 'variable', '', 'Gold, Rose Gold'],
            ['Necklace', 'Long Necklace (A)', 'Long Necklace Antique', 'LNA', '', '', '160.00', 'Elegant long necklace - antique finish', 'normal', '', ''],
            ['Necklace', 'Leriyat Necklace (R)', 'Leriyat Necklace Regular', 'YNR', '', '', '200.00', 'Bridal leriyat necklace - regular finish', 'variable', '2.2, 2.4', 'Gold, Silver'],
            ['Bangles & Kada', 'Bangal (R)', 'Bangal Regular', 'BGR', '', '', '90.00', 'Classic bangal - regular finish', 'variable', '2.2, 2.4', ''],
            ['Bangles & Kada', 'Bangal (A)', 'Bangal Antique', 'BGA', '', '', '95.00', 'Classic bangal - antique finish', 'normal', '', ''],
            ['Bangles & Kada', 'Kadali (Regular)', 'Kadali Regular', 'KDR', '', '', '130.00', 'Regular kadali design', 'variable', '2.2, 2.4', ''],
            ['Bangles & Kada', 'Kadali (CNC)', 'Kadali CNC', 'KDC', '', '', '140.00', 'CNC cut kadali design', 'normal', '', ''],
            ['Bangles & Kada', 'Kadali (A.D.)', 'Kadali AD', 'KDA', '', '', '160.00', 'American Diamond studded kadali', 'variable', '', 'Gold, Silver, Rose Gold'],
        ];

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Products');

        $sheet->fromArray($columns, null, 'A1');
        $sheet->fromArray($rows, null, 'A2');

        $lastColumn = chr(ord('A') + count($columns) - 1);
        $dataStartRow = 2;
        $dataEndRow = $dataStartRow + count($rows) - 1;

        $sheet->getStyle('A1:' . $lastColumn . '1')->getFont()->setBold(true);
        $sheet->getStyle('A1:' . $lastColumn . '1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        foreach (range('A', $lastColumn) as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $mergeStart = $dataStartRow;
        foreach ($rows as $i => $row) {
            $rowNum = $dataStartRow + $i;
            $isLastRow = $i === count($rows) - 1;
            $sameAsNext = !$isLastRow && $row[0] === $rows[$i + 1][0];

            if (!$sameAsNext) {
                if ($rowNum > $mergeStart) {
                    $sheet->mergeCells('A' . $mergeStart . ':A' . $rowNum);
                }
                $sheet->getStyle('A' . $mergeStart . ':A' . $rowNum)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
                $mergeStart = $rowNum + 1;
            }
        }

        $sheet->getStyle('A1:' . $lastColumn . $dataEndRow)
            ->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

        $headers = [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="sample_products_import.xlsx"',
        ];

        $callback = function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        };

        return response()->stream($callback, 200, $headers);
    }

    public function import(Request $request)
    {
        $this->authorize('create products');

        $request->validate([
            'csv_file' => ['required', 'file', 'mimes:csv,txt,xlsx,xls', 'max:5120'], // Max 5MB
        ]);

        $file = $request->file('csv_file');
        $path = $file->getRealPath();
        $extension = strtolower($file->getClientOriginalExtension());

        $normalizeHeader = function ($rawHeader) {
            $normalizedHeader = [];
            foreach ($rawHeader as $col) {
                $norm = preg_replace('/[^a-z0-9]/', '', strtolower(trim((string) $col)));
                if ($norm === 'subcategory') {
                    $normalizedHeader[] = 'sub_category';
                } elseif ($norm === 'productcode') {
                    $normalizedHeader[] = 'product_code';
                } elseif ($norm === 'no') {
                    $normalizedHeader[] = 'no';
                } elseif (in_array($norm, ['discreptions', 'discreption', 'descriptions', 'description'])) {
                    $normalizedHeader[] = 'description';
                } elseif ($norm === 'producttype') {
                    $normalizedHeader[] = 'product_type';
                } elseif (in_array($norm, ['colour', 'color'])) {
                    $normalizedHeader[] = 'colour';
                } else {
                    $normalizedHeader[] = $norm;
                }
            }
            return $normalizedHeader;
        };

        // Parse CSV / Excel
        $rows = [];
        if (in_array($extension, ['xlsx', 'xls'])) {
            $spreadsheet = IOFactory::load($path);
            $sheetData = $spreadsheet->getActiveSheet()->toArray(null, true, true, false);
            $header = null;
            $lastCategory = null;
            foreach ($sheetData as $row) {
                if (!$header) {
                    $header = $normalizeHeader($row);
                } else {
                    if (array_filter($row, fn ($v) => trim((string) $v) !== '') && count($header) == count($row)) {
                        if (trim((string) $row[0]) !== '') {
                            $lastCategory = trim((string) $row[0]);
                        } else {
                            $row[0] = $lastCategory;
                        }
                        $rows[] = array_combine($header, array_map(fn ($v) => trim((string) $v), $row));
                    }
                }
            }
        } elseif (($handle = fopen($path, 'r')) !== false) {
            $header = null;
            while (($row = fgetcsv($handle, 1000, ',')) !== false) {
                if (!$header) {
                    $header = $normalizeHeader(array_map('trim', $row));
                } else {
                    if (count($header) == count($row)) {
                        $rows[] = array_combine($header, array_map('trim', $row));
                    }
                }
            }
            fclose($handle);
        }

        if (empty($rows)) {
            return response()->json([
                'status' => 'error',
                'message' => ['The uploaded CSV file is empty or invalid.']
            ], 422);
        }

        // Helper rounding function
        $roundToNearest5 = function ($val) {
            return ceil(floatval($val) / 5) * 5;
        };

        $importedCount = 0;
        $errors = [];

        DB::beginTransaction();
        try {
            foreach ($rows as $index => $row) {
                $rowNum = $index + 2; // 1-based, +1 for header

                // Validate required fields
                $name = $row['name'] ?? null;
                $no = $row['no'] ?? null;
                $productCode = $row['product_code'] ?? null;
                $categoryName = $row['category'] ?? null;
                $type = strtolower($row['product_type'] ?? 'normal');

                if (empty($name) || empty($no) || empty($productCode) || empty($categoryName)) {
                    $errors[] = "Row {$rowNum}: Missing required product details (Name, No., Product Code, and Category are required).";
                    continue;
                }

                if (!is_numeric($productCode) || $productCode <= 0) {
                    $errors[] = "Row {$rowNum}: Product code must be a valid positive number.";
                    continue;
                }

                if (!in_array($type, ['normal', 'variable'], true)) {
                    $errors[] = "Row {$rowNum}: Product Type must be either 'normal' or 'variable'.";
                    continue;
                }

                // Use the SKU given in the sheet, or build a unique one from the "No." prefix (e.g. SNR -> SNR-0001)
                $skuInput = trim($row['sku'] ?? '');
                if (!empty($skuInput)) {
                    if (Product::where('sku', $skuInput)->exists()) {
                        $errors[] = "Row {$rowNum}: Product SKU '{$skuInput}' already exists in the system.";
                        continue;
                    }
                    $sku = $skuInput;
                } else {
                    $prefix = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $no));
                    if (empty($prefix)) {
                        $errors[] = "Row {$rowNum}: No. must contain at least one letter or number.";
                        continue;
                    }

                    $seq = Product::where('sku', 'like', $prefix . '-%')->count() + 1;
                    do {
                        $sku = $prefix . '-' . str_pad($seq, 4, '0', STR_PAD_LEFT);
                        $seq++;
                    } while (Product::where('sku', $sku)->exists() || Product::where('barcode', $sku)->exists());
                }

                // Use the Barcode given in the sheet, or default to the SKU
                $barcodeInput = trim($row['barcode'] ?? '');
                if (!empty($barcodeInput)) {
                    if (Product::where('barcode', $barcodeInput)->exists()) {
                        $errors[] = "Row {$rowNum}: Product Barcode '{$barcodeInput}' already exists in the system.";
                        continue;
                    }
                    $barcode = $barcodeInput;
                } else {
                    if (Product::where('barcode', $sku)->exists()) {
                        $errors[] = "Row {$rowNum}: Generated Barcode '{$sku}' already exists in the system.";
                        continue;
                    }
                    $barcode = $sku;
                }

                // Find or create category
                $category = Category::firstOrCreate(
                    [
                        'name' => $categoryName
                    ],
                    [
                        'slug' => generate_slug(Category::class, $categoryName),
                        'status' => 1,
                        'created_by' => auth()->id()
                    ]
                );

                // Find or create subcategory if provided
                $subCategoryId = null;
                $subCategoryName = $row['sub_category'] ?? null;
                if (!empty($subCategoryName)) {
                    $subCategory = SubCategory::firstOrCreate(
                        [
                            'category_id' => $category->id,
                            'name' => $subCategoryName
                        ],
                        [
                            'slug' => generate_slug(SubCategory::class, $subCategoryName),
                            'status' => 1,
                            'created_by' => auth()->id()
                        ]
                    );
                    $subCategoryId = $subCategory->id;
                }

                // Calculate prices
                $purchasePrice = floatval($productCode) * 2.5;
                $salePrice = $roundToNearest5(floatval($productCode) * 4.125);
                $mrp = $roundToNearest5($salePrice * 4.575);

                // Create Product
                $product = Product::create([
                    'name' => $name,
                    'slug' => generate_slug(Product::class, $name),
                    'category_id' => $category->id,
                    'sub_category_id' => $subCategoryId,
                    'sku' => $sku,
                    'barcode' => $barcode,
                    'product_code' => $productCode,
                    'description' => $row['description'] ?? $name,
                    'purchase_price' => $purchasePrice,
                    'sale_price' => $salePrice,
                    'mrp' => $mrp,
                    'type' => $type,
                    'status' => Product::STATUS_ACTIVE, // 1 = Active
                    'created_by' => auth()->id(),
                    'sort_order' => ((int) Product::max('sort_order')) + 1,
                ]);

                // Create default image
                $destDefaultPath = public_path('uploads/products/default.png');
                if (!file_exists($destDefaultPath)) {
                    $srcPath = public_path('website/assets/images/Royal_Bridal.png');
                    if (file_exists($srcPath)) {
                        if (!file_exists(dirname($destDefaultPath))) {
                            mkdir(dirname($destDefaultPath), 0755, true);
                        }
                        copy($srcPath, $destDefaultPath);
                    }
                }

                ProductImage::create([
                    'product_id' => $product->id,
                    'image_path' => 'products/default.png',
                    'is_primary' => true,
                    'created_by' => auth()->id(),
                ]);

                // Create variants for variable product (from Size / Colour columns)
                if ($type === 'variable') {
                    $sizeValues = array_values(array_filter(array_map('trim', explode(',', (string) ($row['size'] ?? '')))));
                    $colourValues = array_values(array_filter(array_map('trim', explode(',', (string) ($row['colour'] ?? '')))));

                    if (empty($sizeValues) && empty($colourValues)) {
                        $errors[] = "Row {$rowNum}: Variable product '{$name}' must have Size and/or Colour values.";
                        DB::rollBack();
                        return response()->json([
                            'status' => 'error',
                            'message' => ["Row {$rowNum}: Variable product '{$name}' must have Size and/or Colour values."]
                        ], 422);
                    }

                    if (!empty($sizeValues) && !empty($colourValues)) {
                        $attributeName = 'Size / Colour';
                        $attrValues = [];
                        foreach ($sizeValues as $s) {
                            foreach ($colourValues as $c) {
                                $attrValues[] = $s . ' - ' . $c;
                            }
                        }
                    } elseif (!empty($sizeValues)) {
                        $attributeName = 'Size';
                        $attrValues = $sizeValues;
                    } else {
                        $attributeName = 'Colour';
                        $attrValues = $colourValues;
                    }

                    // Find or create Attribute
                    $attribute = Attribute::firstOrCreate(
                        [
                            'name' => $attributeName
                        ],
                        [
                            'slug' => generate_slug(Attribute::class, $attributeName),
                            'status' => Attribute::STATUS_ACTIVE,
                            'created_by' => auth()->id(),
                            'sort_order' => ((int) Attribute::max('sort_order')) + 1
                        ]
                    );

                    foreach ($attrValues as $val) {
                        // Find or create AttributeValue
                        $attributeValue = AttributeValue::firstOrCreate([
                            'attribute_id' => $attribute->id,
                            'value' => $val
                        ]);

                        // Create variant
                        ProductVariant::create([
                            'product_id' => $product->id,
                            'attribute_value_id' => $attributeValue->id,
                            'purchase_price' => $purchasePrice,
                            'sale_price' => $salePrice,
                            'status' => ProductVariant::STATUS_ACTIVE
                        ]);
                    }
                }

                $importedCount++;
            }

            if (!empty($errors)) {
                DB::rollBack();
                return response()->json([
                    'status' => 'error',
                    'message' => $errors
                ], 422);
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => ['An unexpected error occurred: ' . $e->getMessage()]
            ], 500);
        }

        ActivityLogger::log(
            'Product',
            'import',
            null,
            null,
            ['imported_count' => $importedCount, 'file_name' => $file->getClientOriginalName()],
            "Imported {$importedCount} products from CSV"
        );

        return response()->json([
            'status' => 'success',
            'message' => "Successfully imported {$importedCount} products."
        ]);
    }

}
