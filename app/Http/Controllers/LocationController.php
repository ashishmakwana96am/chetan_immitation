<?php

namespace App\Http\Controllers;

use App\Models\Location;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class LocationController extends Controller
{
    public function index()
    {
        $this->authorize('view locations');
        $locations = Location::with('createdBy')->latest()->get();
        return view('locations.index', compact('locations'));
    }

    public function data()
    {
        $this->authorize('view locations');

        $locations = Location::with('createdBy')->latest()->get();

        $canEdit   = auth()->user()->can('edit locations');
        $canDelete = auth()->user()->can('delete locations');

        $data = $locations->map(function ($location, $index) use ($canEdit, $canDelete) {
            $status = $canEdit
                ? '<div class="form-check form-switch mb-0">
                    <input class="form-check-input location-status-toggle" type="checkbox" role="switch"
                        data-url="' . route('admin.locations.toggle-status', $location) . '"
                        ' . ($location->status === 'active' ? 'checked' : '') . ' />
                   </div>'
                : '<span class="badge ' . ($location->status === 'active' ? 'bg-label-success' : 'bg-label-danger') . '">' . ucfirst($location->status) . '</span>';

            $actions = '';
            if ($canEdit) {
                $actions .= '<button class="btn btn-sm btn-icon btn-label-info me-1"
                    data-common-modal="' . route('admin.locations.edit', $location) . '">
                    <i class="ti ti-pencil"></i></button>';
            }
            if ($canDelete) {
                $actions .= '<button class="btn btn-sm btn-icon btn-label-danger"
                    data-common-delete="' . route('admin.locations.destroy', $location) . '"
                    data-row-id="location-row-' . $location->id . '">
                    <i class="ti ti-trash"></i></button>';
            }

            return [
                'index'      => $index + 1,
                'name'       => $location->name,
                'slug'       => '<code>' . $location->slug . '</code>',
                'address'    => $location->address ?? '-',
                'is_default' => $location->is_default
                    ? '<span class="badge bg-label-success">Default</span>'
                    : '<span class="badge bg-label-secondary">No</span>',
                'status'     => $status,
                'created_by' => $location->createdBy->name ?? '-',
                'actions'    => $actions,
            ];
        });

        return response()->json(['status' => 'success', 'data' => $data]);
    }

    public function create()
    {
        $this->authorize('create locations');
        return view('locations.create');
    }

    public function store(Request $request)
    {
        $this->authorize('create locations');

        $validator = Validator::make($request->all(), [
            'name'    => ['required', 'string', 'max:100'],
            'address' => ['nullable', 'string', 'max:255'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'error',
                'message' => $validator->errors(),
            ], 422);
        }

        if ($request->boolean('is_default')) {
            Location::where('is_default', true)->update(['is_default' => false]);
        }

        Location::create([
            'name'       => $request->name,
            'slug'       => generate_slug(Location::class, $request->name),
            'address'    => $request->address,
            'is_default' => $request->boolean('is_default'),
            'status'     => $request->has('status') ? 'active' : 'inactive',
            'created_by' => auth()->id(),
        ]);

        return response()->json([
            'status'  => 'success',
            'message' => 'Location created successfully.',
        ]);
    }

    public function edit(Location $location)
    {
        $this->authorize('edit locations');
        return view('locations.edit', compact('location'));
    }

    public function update(Request $request, Location $location)
    {
        $this->authorize('edit locations');

        $validator = Validator::make($request->all(), [
            'name'    => ['required', 'string', 'max:100'],
            'address' => ['nullable', 'string', 'max:255'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'error',
                'message' => $validator->errors(),
            ], 422);
        }

        if ($request->boolean('is_default')) {
            Location::where('is_default', true)
                ->where('id', '!=', $location->id)
                ->update(['is_default' => false]);
        }

        $location->update([
            'name'       => $request->name,
            'slug'       => generate_slug(Location::class, $request->name, $location->id),
            'address'    => $request->address,
            'is_default' => $request->boolean('is_default'),
            'status'     => $request->has('status') ? 'active' : 'inactive',
        ]);

        return response()->json([
            'status'  => 'success',
            'message' => 'Location updated successfully.',
        ]);
    }

    public function toggleStatus(Location $location)
    {
        $this->authorize('edit locations');

        $location->update([
            'status' => $location->status === 'active' ? 'inactive' : 'active',
        ]);

        return response()->json([
            'status'  => 'success',
            'message' => 'Location status updated successfully.',
        ]);
    }

    public function destroy(Location $location)
    {
        $this->authorize('delete locations');

        if ($location->is_default) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Cannot delete the default location.',
            ], 422);
        }

        $location->delete();

        return response()->json([
            'status'  => 'success',
            'message' => 'Location deleted successfully.',
        ]);
    }
}
