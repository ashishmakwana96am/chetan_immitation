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

        $query = Attribute::with('createdBy', 'values')->orderBy('id', 'desc');

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

            return [
                'index'      => $index + 1,
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

        $attribute = Attribute::create([
            'name'       => $request->name,
            'slug'       => Str::slug($request->name),
            'status'     => $request->has('status') ? 1 : 2,
            'created_by' => auth()->id(),
        ]);

        $values = json_decode($request->values_json, true);
        foreach ($values as $item) {
            if (!empty($item['value'])) {
                AttributeValue::create([
                    'attribute_id' => $attribute->id,
                    'value'        => trim($item['value']),
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
                ->map(fn ($v) => trim($v))
                ->all();

            $removedValues = $attribute->values()->get()
                ->filter(fn ($v) => !in_array($v->value, $submittedValues, true));

            foreach ($removedValues as $value) {
                $productNames = ProductVariant::where('attribute_value_id', $value->id)
                    ->with('product')
                    ->get()
                    ->pluck('product.name')
                    ->filter()
                    ->unique();

                if ($productNames->isNotEmpty()) {
                    $validator->errors()->add(
                        'values_json',
                        'Value "' . $value->value . '" is used in product(s): ' . $productNames->implode(', ') . '. Please remove it from there first before deleting this value.'
                    );
                }
            }
        });

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'error',
                'message' => $validator->errors(),
            ], 422);
        }

        $attribute->update([
            'name'   => $request->name,
            'slug'   => Str::slug($request->name),
            'status' => $request->has('status') ? 1 : 2,
        ]);

        $attribute->values()->delete();

        $values = json_decode($request->values_json, true);
        foreach ($values as $item) {
            if (!empty($item['value'])) {
                AttributeValue::create([
                    'attribute_id' => $attribute->id,
                    'value'        => trim($item['value']),
                ]);
            }
        }

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
                'message' => "This attribute cannot be deleted because it is in use. First remove it from the products below: " . $productListHtml,
            ], 422);
        }

        $attribute->delete();

        return response()->json([
            'status'  => 'success',
            'message' => 'Attribute deleted successfully.',
        ]);
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

        $attrValue = AttributeValue::create([
            'attribute_id' => $request->attribute_id,
            'value'        => trim($request->value),
        ]);

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

        $attribute = Attribute::create([
            'name'       => $request->name,
            'slug'       => Str::slug($request->name),
            'status'     => 1,
            'created_by' => auth()->id(),
        ]);

        $valueLines = array_filter(array_map('trim', explode("\n", $request->values)));
        foreach ($valueLines as $val) {
            AttributeValue::create([
                'attribute_id' => $attribute->id,
                'value'        => $val,
            ]);
        }

        $attribute->load('values');

        return response()->json([
            'status' => 'success',
            'data'   => $attribute,
            'message' => 'Attribute created successfully.',
        ]);
    }
}
