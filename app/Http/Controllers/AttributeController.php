<?php

namespace App\Http\Controllers;

use App\Models\Attribute;
use App\Models\AttributeValue;
use App\Models\ProductVariant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AttributeController extends Controller
{
    public function index()
    {
        $this->authorize('view attributes');
        return view('attributes.index');
    }

    public function data(Request $request)
    {
        $this->authorize('view attributes');
        try {
            self::reindexAttributes();
        } catch (\Throwable $e) {}

        $query = Attribute::with('createdBy', 'values');
        try {
            $query->orderBy('index', 'asc');
        } catch (\Throwable $e) {
            $query->orderBy('id', 'asc');
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $attributes = $query->get();
        $canEdit   = auth()->user()->can('edit attributes');
        $canDelete = auth()->user()->can('delete attributes');

        $data = $attributes->map(function ($attribute, $index) use ($canEdit, $canDelete) {
            $status = $canEdit
                ? '<div class="form-check form-switch mb-0"><input class="form-check-input attribute-status-toggle" type="checkbox" role="switch" data-url="' . route('admin.attributes.toggle-status', $attribute) . '" ' . ($attribute->status == 1 ? 'checked' : '') . ' /></div>'
                : status_badge($attribute->status);

            $valuesList = $attribute->values->pluck('value')->implode(', ');

            $actions = '';
            if ($canEdit || $canDelete) {
                $actions = '<div class="dropdown table-action-dropdown">';
                $actions .= '<button class="btn btn-sm btn-label-primary action-dropdown-btn dropdown-toggle" data-bs-toggle="dropdown" data-bs-boundary="viewport" aria-expanded="false"><span>Actions</span></button>';
                $actions .= '<div class="dropdown-menu dropdown-menu-end action-dropdown-menu m-0">';
                if ($canEdit) {
                    $actions .= '<button class="dropdown-item" data-common-modal="' . route('admin.attributes.edit', $attribute) . '"><i class="ti ti-pencil me-2"></i>Edit</button>';
                }
                if ($canDelete) {
                    if ($canEdit) {
                        $actions .= '<div class="dropdown-divider"></div>';
                    }
                    $actions .= '<button class="dropdown-item text-danger" data-common-delete="' . route('admin.attributes.destroy', $attribute) . '" data-row-id="attribute-row-' . $attribute->id . '"><i class="ti ti-trash me-2"></i>Delete</button>';
                }
                $actions .= '</div></div>';
            }

            $indexVal = null;
            try {
                $indexVal = $attribute->index;
            } catch (\Throwable $e) {
                $indexVal = null;
            }

            return [
                'index'      => $indexVal ?: '-',
                'name'       => $attribute->name,
                'values'     => $valuesList ?: '-',
                'status'     => $status,
                'created_at' => format_date($attribute->created_at),
                'actions'    => $actions,
            ];
        });

        return response()->json(['status' => 'success', 'data' => $data]);
    }

    public function create()
    {
        $this->authorize('create attributes');
        return view('attributes.create');
    }

    public function store(Request $request)
    {
        $this->authorize('create attributes');

        $validator = Validator::make($request->all(), [
            'name'       => ['required', 'string', 'max:255', Rule::unique('attributes', 'name')->whereNull('deleted_at')],
            'values_json' => ['required', 'json'],
            'status'     => ['nullable', 'string'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'error',
                'message' => $validator->errors(),
            ], 422);
        }

        $name = trim($request->name);
        $slug = Str::slug($name);

        $existing = Attribute::withTrashed()->where('slug', $slug)->first();
        if ($existing) {
            if ($existing->trashed()) {
                $existing->restore();
                $existing->update([
                    'name'       => $name,
                    'status'     => $request->has('status') ? 1 : 2,
                    'created_by' => auth()->id(),
                ]);
                $attribute = $existing;
            } else {
                $attribute = $existing;
            }
        } else {
            $attribute = Attribute::create([
                'name'       => $name,
                'slug'       => $slug,
                'status'     => $request->has('status') ? 1 : 2,
                'created_by' => auth()->id(),
                'index'      => (int) Attribute::max('index') + 1,
            ]);
        }

        $values = json_decode($request->values_json, true) ?: [];
        $seen = [];
        foreach ($values as $item) {
            $val = trim($item['value'] ?? '');
            $lower = mb_strtolower($val);
            if ($val !== '' && !isset($seen[$lower])) {
                $seen[$lower] = true;
                AttributeValue::create([
                    'attribute_id' => $attribute->id,
                    'value'        => $val,
                ]);
            }
        }

        return response()->json([
            'status'  => 'success',
            'message' => 'Attribute created successfully.',
        ]);
    }

    public function edit(Attribute $attribute)
    {
        $this->authorize('edit attributes');
        $attribute->load('values');
        return view('attributes.edit', compact('attribute'));
    }

    public function update(Request $request, Attribute $attribute)
    {
        $this->authorize('edit attributes');

        $validator = Validator::make($request->all(), [
            'name'       => ['required', 'string', 'max:255', Rule::unique('attributes', 'name')->ignore($attribute->id)->whereNull('deleted_at')],
            'values_json' => ['required', 'json'],
            'status'     => ['nullable', 'string'],
        ]);

        $validator->after(function ($validator) use ($request, $attribute) {
            $submittedValues = collect(json_decode($request->values_json, true) ?: [])
                ->pluck('value')
                ->map(fn ($v) => mb_strtolower(trim($v)))
                ->filter()
                ->unique()
                ->all();

            $removedValues = $attribute->values()->get()
                ->filter(fn ($v) => !in_array(mb_strtolower(trim($v->value)), $submittedValues, true));

            $inUseProducts = collect();
            $inUseValueNames = [];

            foreach ($removedValues as $value) {
                $prods = ProductVariant::where('attribute_value_id', $value->id)
                    ->with('product')
                    ->get()
                    ->pluck('product')
                    ->filter()
                    ->unique('id');

                if ($prods->isNotEmpty()) {
                    $inUseValueNames[] = '"' . $value->value . '"';
                    foreach ($prods as $p) {
                        $inUseProducts->push($p);
                    }
                }
            }

            if ($inUseProducts->isNotEmpty()) {
                $uniqueProds = $inUseProducts->unique('id');
                $productData = $uniqueProds->map(function ($prod) {
                    return [
                        'id'      => $prod->id,
                        'name'    => $prod->name,
                        'barcode' => $prod->barcode ?? null,
                        'url'     => route('admin.products.show', $prod),
                    ];
                })->values()->all();

                $validator->errors()->add('_in_use', json_encode([
                    'title'    => 'Cannot Update Attribute',
                    'message'  => 'Attribute value(s) ' . implode(', ', $inUseValueNames) . ' cannot be removed because they are in use by ' . count($productData) . ' product(s). Please remove them from these products first:',
                    'products' => $productData,
                ]));
            }
        });

        if ($validator->fails()) {
            if ($validator->errors()->has('_in_use')) {
                $inUseData = json_decode($validator->errors()->first('_in_use'), true);
                return response()->json([
                    'status'   => 'error',
                    'in_use'   => true,
                    'title'    => $inUseData['title'],
                    'message'  => $inUseData['message'],
                    'products' => $inUseData['products'],
                ], 422);
            }

            return response()->json([
                'status'  => 'error',
                'message' => $validator->errors(),
            ], 422);
        }

        $attribute->update([
            'name'   => trim($request->name),
            'slug'   => Str::slug(trim($request->name)),
            'status' => $request->has('status') ? 1 : 2,
        ]);

        $submittedValues = json_decode($request->values_json, true) ?: [];
        $seen = [];
        $keptIds = [];

        foreach ($submittedValues as $item) {
            $val = trim($item['value'] ?? '');
            $lower = mb_strtolower($val);
            if ($val === '' || isset($seen[$lower])) {
                continue;
            }
            $seen[$lower] = true;

            $attrVal = AttributeValue::withTrashed()
                ->where('attribute_id', $attribute->id)
                ->whereRaw('LOWER(TRIM(value)) = ?', [$lower])
                ->first();

            if ($attrVal) {
                if ($attrVal->trashed()) {
                    $attrVal->restore();
                }
                $attrVal->update(['value' => $val]);
            } else {
                $attrVal = AttributeValue::create([
                    'attribute_id' => $attribute->id,
                    'value'        => $val,
                ]);
            }
            $keptIds[] = $attrVal->id;
        }

        $attribute->values()->whereNotIn('id', $keptIds)->delete();

        return response()->json([
            'status'  => 'success',
            'message' => 'Attribute updated successfully.',
        ]);
    }

    public function toggleStatus(Attribute $attribute)
    {
        $this->authorize('edit attributes');

        $attribute->update([
            'status' => $attribute->status == 1 ? 2 : 1,
        ]);

        return response()->json([
            'status'  => 'success',
            'message' => 'Attribute status updated successfully.',
        ]);
    }

    public function destroy(Attribute $attribute)
    {
        $this->authorize('delete attributes');

        $variants = \App\Models\ProductVariant::whereHas('attributeValue', function ($q) use ($attribute) {
            $q->where('attribute_id', $attribute->id);
        })->whereHas('product')->with('product')->get();

        if ($variants->count() > 0) {
            $products = $variants->pluck('product')->unique('id');
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
                'title'    => 'Cannot Delete Attribute',
                'message'  => 'This attribute cannot be deleted because it is in use by ' . count($productData) . ' product(s). Please remove it from these products first:',
                'products' => $productData,
            ], 422);
        }

        $attribute->delete();

        self::reindexAttributes();

        return response()->json([
            'status'  => 'success',
            'message' => 'Attribute deleted successfully.',
        ]);
    }

    public static function reindexAttributes(): void
    {
        $attributes = Attribute::whereNull('deleted_at')
            ->orderBy('id', 'asc')
            ->get();

        foreach ($attributes as $i => $attr) {
            $attr->update(['index' => $i + 1]);
        }
    }

    public function getAttributesWithValues()
    {
        $attributes = Attribute::with('values')->where('status', 1)->orderBy('name')->get();

        return response()->json($attributes);
    }

    public function storeValue(Request $request)
    {
        $this->authorize('create attributes');

        $validator = Validator::make($request->all(), [
            'attribute_id' => ['required', 'exists:attributes,id'],
            'value'        => ['required', 'string', 'max:255'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'error',
                'message' => $validator->errors(),
            ], 422);
        }

        $val = trim($request->value);
        $attrValue = AttributeValue::withTrashed()
            ->where('attribute_id', $request->attribute_id)
            ->whereRaw('LOWER(TRIM(value)) = ?', [mb_strtolower($val)])
            ->first();

        if ($attrValue) {
            if ($attrValue->trashed()) {
                $attrValue->restore();
            }
            $attrValue->update(['value' => $val]);
        } else {
            $attrValue = AttributeValue::create([
                'attribute_id' => $request->attribute_id,
                'value'        => $val,
            ]);
        }

        return response()->json([
            'status' => 'success',
            'data'   => $attrValue,
            'message' => 'Value added successfully.',
        ]);
    }

    public function quickStore(Request $request)
    {
        $this->authorize('create attributes');

        $validator = Validator::make($request->all(), [
            'name'   => ['required', 'string', 'max:255', Rule::unique('attributes', 'name')->whereNull('deleted_at')],
            'values' => ['required', 'string'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'error',
                'message' => $validator->errors(),
            ], 422);
        }

        $name = trim($request->name);
        $slug = Str::slug($name);

        $existing = Attribute::withTrashed()->where('slug', $slug)->first();
        if ($existing) {
            if ($existing->trashed()) {
                $existing->restore();
                $existing->update([
                    'name'       => $name,
                    'status'     => 1,
                    'created_by' => auth()->id(),
                ]);
                $attribute = $existing;
            } else {
                $attribute = $existing;
            }
        } else {
            $attribute = Attribute::create([
                'name'       => $name,
                'slug'       => $slug,
                'status'     => 1,
                'created_by' => auth()->id(),
                'index'      => (int) Attribute::max('index') + 1,
            ]);
        }

        $valueLines = array_filter(array_map('trim', explode("\n", $request->values)));
        $seen = [];
        foreach ($valueLines as $val) {
            $lower = mb_strtolower($val);
            if ($val !== '' && !isset($seen[$lower])) {
                $seen[$lower] = true;
                AttributeValue::create([
                    'attribute_id' => $attribute->id,
                    'value'        => $val,
                ]);
            }
        }

        $attribute->load('values');

        return response()->json([
            'status' => 'success',
            'data'   => $attribute,
            'message' => 'Attribute created successfully.',
        ]);
    }
}
