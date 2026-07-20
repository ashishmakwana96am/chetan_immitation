<?php

namespace App\Http\Controllers;

use App\Models\Attribute;
use App\Models\Category;
use App\Models\SubCategory;
use App\Models\Product;
use App\Models\Location;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Picqer\Barcode\BarcodeGeneratorSVG;

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
            'subCategory',
            'primaryImage',
            'variants.attributeValue',
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

        $products = $query->orderBy('id', 'desc')->get();

        $computeStock = function ($product) use ($locationId) {
            if ($product->type === 'variable') {
                $stockData = $product->getVariantStock($locationId);
                if ($locationId) {
                    return $stockData ? array_sum($stockData['variants']) : 0;
                }
                $total = 0;
                foreach ($stockData as $locData) {
                    $total += array_sum($locData['variants']);
                }
                return $total;
            }
            return $product->inventories->sum('quantity');
        };

        if ($request->stock_status === 'in_stock') {
            $products = $products->filter(fn($product) => $computeStock($product) > 0)->values();
        } elseif ($request->stock_status === 'out_of_stock') {
            $products = $products->filter(fn($product) => $computeStock($product) <= 0)->values();
        }

        $canEdit   = auth()->user()->can('edit products');
        $canDelete = auth()->user()->can('delete products');
        $canClone  = auth()->user()->can('clone products');

        $data = $products->map(function ($product, $index) use ($canEdit, $canDelete, $canClone, $computeStock) {
            $nameHtml = $product->name;
            $variationsStr = $product->variants->map(function ($variant) {
                return $variant->attributeValue->value ?? '';
            })->filter()->unique()->implode(', ');


            if ($product->is_variable) {
                $nameHtml .= ' <span class="badge bg-label-info ms-1" style="font-size:10px">Variable</span>';
            }

            if ($product->pair_product) {
                $nameHtml .= ' <span class="badge bg-label-warning ms-1" style="font-size:10px">Pair</span>';
            }

            $image = '<img src="' . $product->primary_image_url . '" width="45" height="45" class="rounded object-fit-cover product-thumbnail" alt="' . e($product->name) . '">';

            $status = $product->status == 1
                ? '<span class="badge bg-label-success">Active</span>'
                : '<span class="badge bg-label-danger">Inactive</span>';

            $stockSum = $computeStock($product);
            $stock = $stockSum > 0
                ? '<span class="badge bg-label-success fw-bold">' . number_format($stockSum) . '</span>'
                : '<span class="badge bg-label-danger fw-bold">SOLD OUT</span>';

            $barcodeVal = $product->barcode;
            $barcode = $barcodeVal
                ? '<div class="d-flex align-items-center gap-2">
                    <code>' . $barcodeVal . '</code>
                    <button onclick="viewBarcode(\'' . $barcodeVal . '\', ' . $product->id . ')" class="btn btn-sm btn-icon btn-label-secondary" title="View Barcode">
                        <i class="ti ti-barcode"></i>
                    </button>
                   </div>'
                : '<span class="text-muted">-</span>';

            $actions = '<div class="dropdown table-action-dropdown">';
            $actions .= '<button class="btn btn-sm btn-label-primary action-dropdown-btn dropdown-toggle" data-bs-toggle="dropdown" data-bs-boundary="viewport" aria-expanded="false"><span>Actions</span></button>';
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
                'barcode'        => $barcode,
                'raw_barcode'    => $product->barcode,
                'product_code'   => $product->product_code,
                'pair_product'   => (bool) $product->pair_product,
                'pair_mode'      => $product->pair_mode,
                'custom_sizes'   => $product->custom_sizes,
                'category'       => !empty($product->subCategory->name) ? $product->subCategory->name : ($product->category->name ?? '-'),
                'variations'     => $variationsStr,
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

    public function printBarcodes(\Illuminate\Http\Request $request)
    {
        $itemsInput = $request->input('items', []);
        
        $printItems = [];
        $totalQty = 0;
        
        $generator = new BarcodeGeneratorSVG();
        
        foreach ($itemsInput as $item) {
            $product = Product::with(['category', 'subCategory', 'variants.attributeValue'])->find($item['id'] ?? null);
            if ($product) {
                $barcodeVal = $product->barcode;
                $categoryDisplay = !empty($product->subCategory?->name)
                    ? $product->subCategory->name
                    : ($product->category?->name ?? '');

                $variations = $product->variants->map(fn($v) => $v->attributeValue->value ?? '')->filter()->unique()->implode(', ');
                $salePrice = number_format($product->sale_price, 0);
                $qty = (int)($item['qty'] ?? 1);
                $totalQty += $qty;
                
                $svgCode = $generator->getBarcode($barcodeVal, $generator::TYPE_CODE_128, 1, 30);
                $barcodeBase64 = 'data:image/svg+xml;base64,' . base64_encode($svgCode);

                $categoryLength = strlen($categoryDisplay);
                $categoryFontSize = match (true) {
                    $categoryLength > 18 => 5.0,
                    $categoryLength > 14 => 5.5,
                    $categoryLength > 10 => 6.5,
                    default => 7.5,
                };

                $isPair = (bool) $product->pair_product && ($product->pair_mode !== 'custom_size');
                $customSizeLabel = null;

                if ($product->pair_product && $product->pair_mode === 'custom_size' && !empty($product->custom_sizes)) {
                    $selectedSizeVal = $item['selected_size'] ?? $item['custom_size'] ?? null;
                    if ($selectedSizeVal) {
                        $customSizeLabel = str_contains((string)$selectedSizeVal, 'pcs') ? $selectedSizeVal : $selectedSizeVal . ' pcs';
                    } else {
                        $sizesList = collect($product->custom_sizes)->sortBy('size')->values();
                        $firstSize = $sizesList->first();
                        if ($firstSize && isset($firstSize['size'])) {
                            $rawSize = $firstSize['size'];
                            $sizeStr = is_numeric($rawSize) ? rtrim(rtrim(number_format((float)$rawSize, 2), '0'), '.') : $rawSize;
                            $customSizeLabel = str_contains((string)$sizeStr, 'pcs') ? $sizeStr : $sizeStr . ' pcs';
                        }
                    }
                }

                $printItems[] = [
                    'barcodeBase64'    => $barcodeBase64,
                    'barcodeText'      => $barcodeVal,
                    'productCode'      => $product->product_code !== null ? number_format($product->product_code, 0) : '',
                    'isPair'           => $isPair,
                    'customSizeLabel'  => $customSizeLabel,
                    'category'         => $categoryDisplay,
                    'categoryFontSize' => $categoryFontSize,
                    'variations'       => $variations,
                    'salePrice'        => $salePrice,
                    'qty'              => $qty
                ];
            }
        }

        if ($totalQty === 0) {
            abort(404, 'No products to print.');
        }

        $pdfHeight = ($totalQty * 34.02) + 15;

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('products.print_barcodes', compact('printItems', 'pdfHeight'))
            ->setPaper([0, 0, 232.44, $pdfHeight], 'portrait');

        return $pdf->stream('barcodes.pdf');
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
            $clonedProduct = Product::with(['images', 'variants.attributeValue.attribute'])->findOrFail($request->clone_id);
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
            'barcode'                  => ['required', 'string', 'max:100', 'unique:products,barcode'],
            'description'              => ['nullable', 'string'],
            'additional_information'   => ['nullable', 'string'],
            'product_highlights'        => ['nullable', 'string'],
            'type'                     => ['required', 'in:normal,variable'],
            'sale'                     => ['nullable', 'boolean'],
            'pair_product'             => ['nullable', 'boolean'],
            'bypass_min_price'         => ['nullable', 'boolean'],
            'primary_image_base64'     => ['nullable', 'string'],
            'additional_images_base64' => ['nullable', 'array'],
            'additional_images_base64.*' => ['nullable', 'string'],
        ];

        $rules['product_code'] = ['required', 'numeric', 'min:0.01'];
        $rules['purchase_multiplier'] = ['required', 'numeric', 'min:0'];
        $rules['sale_multiplier'] = ['required', 'numeric', 'min:0'];
        $rules['mrp_multiplier'] = ['required', 'numeric', 'min:0'];
        $rules['purchase_price'] = ['required', 'numeric', 'min:0'];
        $rules['sale_price'] = ['required', 'numeric', 'min:0'];
        $rules['mrp'] = ['required', 'numeric', 'min:0'];
        $rules['pair_sale_price'] = ['nullable', 'numeric', 'min:0'];
        $rules['pair_mrp'] = ['nullable', 'numeric', 'min:0'];
        $rules['pair_mode'] = ['nullable', 'in:pieces_pair,custom_size'];

        $pairMode = $request->input('pair_mode', 'pieces_pair');

        if ($request->has('pair_product')) {
            if ($pairMode === 'custom_size') {
                $rules['custom_sizes_json'] = [
                    'required',
                    'json',
                    function ($attribute, $value, $fail) {
                        $decoded = json_decode($value, true);
                        if (!is_array($decoded) || empty($decoded)) {
                            $fail('Please add at least one custom size with pricing.');
                            return;
                        }
                        foreach ($decoded as $row) {
                            $sizeText = isset($row['size']) ? ($row['size'] . ' pcs') : 'custom size';
                            if (!isset($row['sale_price']) || $row['sale_price'] === null || $row['sale_price'] === '' || !is_numeric($row['sale_price']) || (float)$row['sale_price'] <= 0) {
                                $fail("Sale Price ({$sizeText}) is required.");
                                return;
                            }
                            if (!isset($row['mrp']) || $row['mrp'] === null || $row['mrp'] === '' || !is_numeric($row['mrp']) || (float)$row['mrp'] <= 0) {
                                $fail("MRP ({$sizeText}) is required.");
                                return;
                            }
                        }
                    },
                ];
            } else {
                $rules['pair_sale_price'] = ['required', 'numeric', 'min:0.01'];
                $rules['pair_mrp']        = ['required', 'numeric', 'min:0.01'];
            }
        }

        if ($request->type === 'variable') {
            $rules['variants_json'] = [
                'required',
                'json',
                function ($attribute, $value, $fail) {
                    $decoded = json_decode($value, true);
                    if (!is_array($decoded) || empty($decoded)) {
                        $fail('Please add at least one attribute & variant.');
                    }
                }
            ];
        }

        $messages = [
            'pair_sale_price.required_if' => 'Pair Sale Price is required.',
            'pair_mrp.required_if'        => 'Pair MRP is required.',
        ];

        $validator = Validator::make($request->all(), $rules, $messages);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'error',
                'message' => $validator->errors(),
            ], 422);
        }

        DB::transaction(function () use ($request, $pairMode) {
            $isSuperAdmin = auth()->user()->hasRole('super-admin');

            $productData = [
                'name'            => $request->name,
                'slug'            => generate_slug(Product::class, $request->name),
                'category_id'     => $request->category_id,
                'sub_category_id' => $request->sub_category_id,
                'barcode'         => $request->barcode,
                'product_code'    => $request->product_code,
                'purchase_multiplier' => $request->purchase_multiplier,
                'sale_multiplier' => $request->sale_multiplier,
                'mrp_multiplier'  => $request->mrp_multiplier,
                'description'     => $request->description,
                'additional_information' => $request->additional_information,
                'product_highlights' => $request->product_highlights,
                'type'            => $request->type,
                'status'          => $request->has('status') ? 1 : 2,
                'sale'            => $request->has('sale') ? 1 : 0,
                'pair_product'    => $request->has('pair_product') ? 1 : 0,
                'bypass_min_price' => $isSuperAdmin && $request->has('bypass_min_price') ? 1 : 0,
                'created_by'      => auth()->id(),
                'sort_order'      => ((int) Product::max('sort_order')) + 1,
            ];

            $productData['purchase_price'] = $request->purchase_price;

            if ($request->has('pair_product')) {
                $productData['pair_mode'] = $pairMode;

                if ($pairMode === 'custom_size') {
                    $productData['pair_sale_price'] = null;
                    $productData['pair_mrp']        = null;
                    $productData['custom_sizes']    = json_decode($request->custom_sizes_json, true);
                } else {
                    $productData['pair_sale_price'] = $request->pair_sale_price;
                    $productData['pair_mrp']        = $request->pair_mrp;
                    $productData['custom_sizes']    = null;
                }

                $productData['sale_price']      = $request->sale_price;
                $productData['mrp']             = $request->mrp;
            } else {
                $productData['pair_mode']       = null;
                $productData['pair_sale_price'] = null;
                $productData['pair_mrp']        = null;
                $productData['custom_sizes']    = null;
                $productData['sale_price']      = $request->sale_price;
                $productData['mrp']             = $request->mrp;
            }

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
            'barcode'                  => ['required', 'string', 'max:100', 'unique:products,barcode,' . $product->id],
            'description'              => ['nullable', 'string'],
            'additional_information'   => ['nullable', 'string'],
            'product_highlights'        => ['nullable', 'string'],
            'type'                     => ['required', 'in:normal,variable'],
            'sale'                     => ['nullable', 'boolean'],
            'pair_product'             => ['nullable', 'boolean'],
            'bypass_min_price'         => ['nullable', 'boolean'],
            'primary_image_base64'     => ['nullable', 'string'],
            'additional_images_base64' => ['nullable', 'array'],
            'additional_images_base64.*' => ['nullable', 'string'],
        ];

        $rules['product_code'] = ['required', 'numeric', 'min:0.01'];
        $rules['purchase_multiplier'] = ['required', 'numeric', 'min:0'];
        $rules['sale_multiplier'] = ['required', 'numeric', 'min:0'];
        $rules['mrp_multiplier'] = ['required', 'numeric', 'min:0'];
        $rules['purchase_price'] = ['required', 'numeric', 'min:0'];
        $rules['sale_price'] = ['required', 'numeric', 'min:0'];
        $rules['mrp'] = ['required', 'numeric', 'min:0'];
        $rules['pair_sale_price'] = ['nullable', 'numeric', 'min:0'];
        $rules['pair_mrp'] = ['nullable', 'numeric', 'min:0'];
        $rules['pair_mode'] = ['nullable', 'in:pieces_pair,custom_size'];

        $pairMode = $request->input('pair_mode', 'pieces_pair');

        if ($request->has('pair_product')) {
            if ($pairMode === 'custom_size') {
                $rules['custom_sizes_json'] = [
                    'required',
                    'json',
                    function ($attribute, $value, $fail) {
                        $decoded = json_decode($value, true);
                        if (!is_array($decoded) || empty($decoded)) {
                            $fail('Please add at least one custom size with pricing.');
                            return;
                        }
                        foreach ($decoded as $row) {
                            $sizeText = isset($row['size']) ? ($row['size'] . ' pcs') : 'custom size';
                            if (!isset($row['sale_price']) || $row['sale_price'] === null || $row['sale_price'] === '' || !is_numeric($row['sale_price']) || (float)$row['sale_price'] <= 0) {
                                $fail("Sale Price ({$sizeText}) is required.");
                                return;
                            }
                            if (!isset($row['mrp']) || $row['mrp'] === null || $row['mrp'] === '' || !is_numeric($row['mrp']) || (float)$row['mrp'] <= 0) {
                                $fail("MRP ({$sizeText}) is required.");
                                return;
                            }
                        }
                    },
                ];
            } else {
                $rules['pair_sale_price'] = ['required', 'numeric', 'min:0.01'];
                $rules['pair_mrp']        = ['required', 'numeric', 'min:0.01'];
            }
        }

        if ($request->type === 'variable') {
            $rules['variants_json'] = [
                'required',
                'json',
                function ($attribute, $value, $fail) {
                    $decoded = json_decode($value, true);
                    if (!is_array($decoded) || empty($decoded)) {
                        $fail('Please add at least one attribute & variant.');
                    }
                }
            ];
        }

        $messages = [
            'pair_sale_price.required_if' => 'Pair Sale Price is required.',
            'pair_mrp.required_if'        => 'Pair MRP is required.',
        ];

        $validator = Validator::make($request->all(), $rules, $messages);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'error',
                'message' => $validator->errors(),
            ], 422);
        }

        DB::transaction(function () use ($request, $product, $pairMode) {
            $isSuperAdmin = auth()->user()->hasRole('super-admin');

            $productData = [
                'name'            => $request->name,
                'category_id'     => $request->category_id,
                'sub_category_id' => $request->sub_category_id,
                'barcode'         => $request->barcode,
                'product_code'    => $request->product_code,
                'purchase_multiplier' => $request->purchase_multiplier,
                'sale_multiplier' => $request->sale_multiplier,
                'mrp_multiplier'  => $request->mrp_multiplier,
                'description'     => $request->description,
                'additional_information' => $request->additional_information,
                'product_highlights' => $request->product_highlights,
                'type'            => $request->type,
                'status'          => $request->has('status') ? 1 : 2,
                'sale'            => $request->has('sale') ? 1 : 0,
                'pair_product'    => $request->has('pair_product') ? 1 : 0,
            ];

            // Only a super-admin's submission can change this — for anyone else the field
            // is simply omitted so the product's existing flag is left untouched.
            if ($isSuperAdmin) {
                $productData['bypass_min_price'] = $request->has('bypass_min_price') ? 1 : 0;
            }

            $productData['purchase_price'] = $request->purchase_price;

            if ($request->has('pair_product')) {
                $productData['pair_mode'] = $pairMode;

                if ($pairMode === 'custom_size') {
                    $productData['pair_sale_price'] = null;
                    $productData['pair_mrp']        = null;
                    $productData['custom_sizes']    = json_decode($request->custom_sizes_json, true);
                } else {
                    $productData['pair_sale_price'] = $request->pair_sale_price;
                    $productData['pair_mrp']        = $request->pair_mrp;
                    $productData['custom_sizes']    = null;
                }

                $productData['sale_price']      = $request->sale_price;
                $productData['mrp']             = $request->mrp;
            } else {
                $productData['pair_mode']       = null;
                $productData['pair_sale_price'] = null;
                $productData['pair_mrp']        = null;
                $productData['custom_sizes']    = null;
                $productData['sale_price']      = $request->sale_price;
                $productData['mrp']             = $request->mrp;
            }

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
                
                $duplicates = $product->variants()
                    ->select('attribute_value_id', DB::raw('MIN(id) as keep_id'))
                    ->groupBy('attribute_value_id')
                    ->get();
                $keepIds = $duplicates->pluck('keep_id')->toArray();
                $product->variants()->whereNotIn('id', $keepIds)->delete();

                $variants = json_decode($request->variants_json, true);
                $existingVariants = $product->variants()->get()->keyBy('attribute_value_id');
                $keptAttributeValueIds = [];

                foreach ($variants as $item) {
                    $attributeValueId = $item['attribute_value_id'];
                    $keptAttributeValueIds[] = $attributeValueId;

                    $variantData = [
                        'purchase_price' => $item['purchase_price'] ?? 0,
                        'sale_price'     => $item['sale_price'] ?? 0,
                        'status'         => ($item['status'] ?? 1) == 1 ? 1 : 2,
                    ];

                    if ($existingVariant = $existingVariants->get($attributeValueId)) {
                        // Preserve the existing variant's id so purchase/stock history keeps matching it
                        $existingVariant->update($variantData);
                    } else {
                        ProductVariant::create($variantData + [
                            'product_id'         => $product->id,
                            'attribute_value_id' => $attributeValueId,
                        ]);
                    }
                }

                $product->variants()->whereNotIn('attribute_value_id', $keptAttributeValueIds)->delete();
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
                        ->orWhere('barcode', 'like', '%' . $request->q . '%');
                });
            })
            ->orderBy('name')
            ->limit(50)
            ->get(['id', 'name', 'barcode']);

        return response()->json($query->map(function ($product) {
            return [
                'id' => $product->id,
                'text' => $product->barcode ? $product->name . ' (' . $product->barcode . ')' : $product->name,
                'name' => $product->name,
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

        $barcodeText = $product->barcode;
        if (empty($barcodeText)) {
            return response()->json(['status' => 'error', 'message' => 'No barcode found for this product'], 404);
        }

        $generator = new BarcodeGeneratorSVG();
        $barcode = $generator->getBarcode($barcodeText, $generator::TYPE_CODE_128);

        return response($barcode, 200)->header('Content-Type', 'image/svg+xml');
    }

}
