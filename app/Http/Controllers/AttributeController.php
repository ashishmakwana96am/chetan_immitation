<?php

namespace App\Http\Controllers;

use App\Models\Attribute;
use App\Models\AttributeValue;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class AttributeController extends Controller
{
    public function index()
    {
        $this->authorize('view attributes');
        return view('attributes.index');
    }

    public function data()
    {
        $this->authorize('view attributes');

        $attributes = Attribute::with('createdBy', 'values')->orderBy('id', 'desc')->get();
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
                $actions .= '<button class="btn btn-sm btn-label-primary action-dropdown-btn dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false"><span>Actions</span></button>';
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
            'name'       => ['required', 'string', 'max:255', 'unique:attributes,name'],
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
            'name'       => ['required', 'string', 'max:255', 'unique:attributes,name,' . $attribute->id],
            'values_json' => ['required', 'json'],
            'status'     => ['nullable', 'string'],
        ]);

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
            'name'   => ['required', 'string', 'max:255', 'unique:attributes,name'],
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
