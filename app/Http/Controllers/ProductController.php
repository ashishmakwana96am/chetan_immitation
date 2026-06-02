<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\SubCategory;
use App\Models\Product;
use App\Models\ProductImage;
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
        ])->latest()->get();

        $canEdit   = auth()->user()->can('edit products');
        $canDelete = auth()->user()->can('delete products');

        $data = $products->map(function ($product, $index) use ($canEdit, $canDelete) {
            $image = $product->primaryImage
                ? '<img src="' . $product->primaryImage->image_url . '" width="45" height="45" class="rounded object-fit-cover">'
                : '<span class="badge bg-label-secondary">No Image</span>';

            $status = $product->status === 'active'
                ? '<span class="badge bg-label-success">Active</span>'
                : '<span class="badge bg-label-danger">Inactive</span>';

            $stockSum = $product->inventories->sum('quantity');
            $stock = $stockSum > 0
                ? '<span class="badge bg-label-success fw-bold">' . number_format($stockSum) . '</span>'
                : '<span class="badge bg-label-danger fw-bold">Out of stock</span>';

            $actions = '<a href="' . route('admin.products.show', $product) . '" class="btn btn-sm btn-icon btn-label-secondary me-1" data-bs-toggle="tooltip" title="View"><i class="ti ti-eye"></i></a>';
            if ($canEdit) {
                $actions .= '<a href="' . route('admin.products.edit', $product) . '" class="btn btn-sm btn-icon btn-label-info me-1" data-bs-toggle="tooltip" title="Edit"><i class="ti ti-pencil"></i></a>';
            }
            if ($canDelete) {
                $actions .= '<button class="btn btn-sm btn-icon btn-label-danger" data-common-delete="' . route('admin.products.destroy', $product) . '" data-row-id="product-row-' . $product->id . '" data-bs-toggle="tooltip" title="Delete"><i class="ti ti-trash"></i></button>';
            }

            return [
                'index'          => $index + 1,
                'image'          => $image,
                'name'           => $product->name,
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
            'inventories' => function($q) use ($user) {
                $q->when($user->location_id && $user->type !== 'super-admin', fn($sub) => $sub->where('location_id', $user->location_id));
            },
            'inventories.location'
        ]);
        return view('products.show', compact('product'));
    }

    public function create()
    {
        $this->authorize('create products');
        $categories = Category::where('status', 'active')->orderBy('name')->get();
        return view('products.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $this->authorize('create products');

        $validator = Validator::make($request->all(), [
            'name'            => ['required', 'string', 'max:200'],
            'category_id'     => ['required', 'exists:categories,id'],
            'sub_category_id' => ['nullable', 'exists:sub_categories,id'],
            'sku'             => ['required', 'string', 'max:100', 'unique:products,sku'],
            'description'     => ['nullable', 'string'],
            'purchase_price'  => ['required', 'numeric', 'min:0'],
            'sale_price'      => ['required', 'numeric', 'min:0'],
            'primary_image'   => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'images'          => ['required', 'array', 'min:1'],
            'images.*'        => ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'error',
                'message' => $validator->errors(),
            ], 422);
        }

        DB::transaction(function () use ($request) {
            $product = Product::create([
                'name'            => $request->name,
                'slug'            => generate_slug(Product::class, $request->name),
                'category_id'     => $request->category_id,
                'sub_category_id' => $request->sub_category_id,
                'sku'             => $request->sku,
                'description'     => $request->description,
                'purchase_price'  => $request->purchase_price,
                'sale_price'      => $request->sale_price,
                'status'          => $request->has('status') ? 'active' : 'inactive',
                'created_by'      => auth()->id(),
            ]);

            // Primary image
            if ($request->hasFile('primary_image')) {
                $file = $request->file('primary_image');
                $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
                $file->move(public_path('uploads/products'), $filename);
                ProductImage::create([
                    'product_id' => $product->id,
                    'image_path' => 'products/' . $filename,
                    'is_primary' => true,
                    'created_by' => auth()->id(),
                ]);
            }

            // Additional images
            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $file) {
                    $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
                    $file->move(public_path('uploads/products'), $filename);
                    ProductImage::create([
                        'product_id' => $product->id,
                        'image_path' => 'products/' . $filename,
                        'is_primary' => false,
                        'created_by' => auth()->id(),
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
        $categories = Category::where('status', 'active')->orderBy('name')->get();
        $subCategories = SubCategory::where('category_id', $product->category_id)
            ->where('status', 'active')
            ->orderBy('name')
            ->get();
        $product->load('images');
        return view('products.edit', compact('product', 'categories', 'subCategories'));
    }

    public function update(Request $request, Product $product)
    {
        $this->authorize('edit products');

        $validator = Validator::make($request->all(), [
            'name'            => ['required', 'string', 'max:200'],
            'category_id'     => ['required', 'exists:categories,id'],
            'sub_category_id' => ['nullable', 'exists:sub_categories,id'],
            'sku'             => ['required', 'string', 'max:100', 'unique:products,sku,' . $product->id],
            'description'     => ['nullable', 'string'],
            'purchase_price'  => ['required', 'numeric', 'min:0'],
            'sale_price'      => ['required', 'numeric', 'min:0'],
            'primary_image'   => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'images'          => ['nullable', 'array'],
            'images.*'        => ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'error',
                'message' => $validator->errors(),
            ], 422);
        }

        DB::transaction(function () use ($request, $product) {
            $product->update([
                'name'            => $request->name,
                'slug'            => generate_slug(Product::class, $request->name, $product->id),
                'category_id'     => $request->category_id,
                'sub_category_id' => $request->sub_category_id,
                'sku'             => $request->sku,
                'description'     => $request->description,
                'purchase_price'  => $request->purchase_price,
                'sale_price'      => $request->sale_price,
                'status'          => $request->has('status') ? 'active' : 'inactive',
            ]);

            // Replace primary image
            if ($request->hasFile('primary_image')) {
                $existing = $product->images()->where('is_primary', true)->first();
                if ($existing) {
                    $existingFile = public_path('uploads/' . $existing->image_path);
                    if (file_exists($existingFile)) {
                        @unlink($existingFile);
                    }
                    $existing->delete();
                }
                $file = $request->file('primary_image');
                $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
                $file->move(public_path('uploads/products'), $filename);
                ProductImage::create([
                    'product_id' => $product->id,
                    'image_path' => 'products/' . $filename,
                    'is_primary' => true,
                    'created_by' => auth()->id(),
                ]);
            }

            // Additional images
            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $file) {
                    $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
                    $file->move(public_path('uploads/products'), $filename);
                    ProductImage::create([
                        'product_id' => $product->id,
                        'image_path' => 'products/' . $filename,
                        'is_primary' => false,
                        'created_by' => auth()->id(),
                    ]);
                }
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
            'status' => $product->status === 'active' ? 'inactive' : 'active',
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
            ->where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name']);

        return response()->json($subCategories);
    }
}
