<?php

namespace App\Http\Controllers;

use App\Models\Banner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class BannerController extends Controller
{
    public function index()
    {
        $this->authorize('view banners');
        return view('banners.index');
    }

    public function data(Request $request)
    {
        $this->authorize('view banners');

        $banners = Banner::with('createdBy')->orderBy('id', 'desc')->get();
        $canEdit = auth()->user()->can('edit banners');
        $canDelete = auth()->user()->can('delete banners');

        $data = $banners->map(function ($banner, $index) use ($canEdit, $canDelete) {
            $image = $banner->image
                ? '<img src="' . $banner->image_url . '" style="max-width:140px; max-height:55px; width:auto; height:auto; cursor:pointer;" class="rounded shadow-sm banner-img-preview" alt="Banner ' . ($index + 1) . '">'
                : '<span class="badge bg-label-secondary">No Image</span>';

            $status = $canEdit
                ? '<div class="form-check form-switch mb-0"><input class="form-check-input banner-status-toggle" type="checkbox" role="switch" data-url="' . route('admin.banners.toggle-status', $banner) . '" ' . ($banner->status == 1 ? 'checked' : '') . ' /></div>'
                : status_badge($banner->status);

            $actions = '';
            if ($canEdit || $canDelete) {
                $actions = '<div class="dropdown table-action-dropdown">';
                $actions .= '<button class="btn btn-sm btn-label-primary action-dropdown-btn dropdown-toggle" data-bs-toggle="dropdown" data-bs-boundary="viewport" aria-expanded="false"><span>Actions</span></button>';
                $actions .= '<div class="dropdown-menu dropdown-menu-end action-dropdown-menu m-0">';
                if ($canEdit) {
                    $actions .= '<button class="dropdown-item" data-common-modal="' . route('admin.banners.edit', $banner) . '"><i class="ti ti-pencil me-2"></i>Edit</button>';
                }
                if ($canDelete) {
                    if ($canEdit) {
                        $actions .= '<div class="dropdown-divider"></div>';
                    }
                    $actions .= '<button class="dropdown-item text-danger" data-common-delete="' . route('admin.banners.destroy', $banner) . '" data-row-id="banner-row-' . $banner->id . '"><i class="ti ti-trash me-2"></i>Delete</button>';
                }
                $actions .= '</div></div>';
            }

            return [
                'id'             => $banner->id,
                'index'          => $index + 1,
                'image'          => $image,
                'status'         => $status,
                'raw_status'     => (int) $banner->status,
                'created_by'     => $banner->createdBy ? e($banner->createdBy->name) : 'System',
                'raw_created_by' => $banner->createdBy ? $banner->createdBy->name : 'System',
                'created_at'     => format_date($banner->created_at),
                'raw_created_at' => $banner->created_at ? $banner->created_at->format('YmdHis') : '0',
                'actions'        => $actions,
            ];
        });

        return response()->json(['status' => 'success', 'data' => $data]);
    }

    public function create()
    {
        $this->authorize('create banners');
        return view('banners.create');
    }

    public function store(Request $request)
    {
        $this->authorize('create banners');

        $validator = Validator::make($request->all(), [
            'image_base64' => ['required', 'string'],
        ], [
            'image_base64.required' => 'The banner image field is required.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
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
                        'status' => 'error',
                        'message' => ['image' => ['The image must be a file of type: jpg, jpeg, png, webp.']],
                    ], 422);
                }

                $decodedData = base64_decode($dataString);
                if ($decodedData === false) {
                    return response()->json([
                        'status' => 'error',
                        'message' => ['image' => ['Failed to decode image.']],
                    ], 422);
                }

                $dir = public_path('uploads/banners');
                if (!file_exists($dir)) {
                    mkdir($dir, 0755, true);
                }

                $filename = time() . '_' . uniqid() . '.' . $type;
                file_put_contents($dir . '/' . $filename, $decodedData);
                $imagePath = 'banners/' . $filename;
            }
        }

        Banner::create([
            'image' => $imagePath,
            'status' => $request->has('status') ? Banner::STATUS_ACTIVE : Banner::STATUS_INACTIVE,
            'created_by' => auth()->id(),
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Banner created successfully.',
        ]);
    }

    public function edit(Banner $banner)
    {
        $this->authorize('edit banners');
        return view('banners.edit', compact('banner'));
    }

    public function update(Request $request, Banner $banner)
    {
        $this->authorize('edit banners');

        $validator = Validator::make($request->all(), [
            'image_base64' => ['nullable', 'string'],
        ]);

        $validator->after(function ($validator) use ($request, $banner) {
            $hasNewImage = $request->filled('image_base64');
            $isRemovingImage = $request->boolean('remove_image');
            $hasExistingImage = !empty($banner->image);

            if (!$hasNewImage && ($isRemovingImage || !$hasExistingImage)) {
                $validator->errors()->add('image_base64', 'The banner image field is required.');
            }
        });

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors(),
            ], 422);
        }

        $imagePath = $banner->image;

        if ($request->filled('image_base64')) {
            $base64Data = $request->image_base64;
            if (preg_match('/^data:image\/(\w+);base64,/', $base64Data, $type)) {
                $dataString = substr($base64Data, strpos($base64Data, ',') + 1);
                $type = strtolower($type[1]);

                if (!in_array($type, ['jpg', 'jpeg', 'png', 'webp'])) {
                    return response()->json([
                        'status' => 'error',
                        'message' => ['image' => ['The image must be a file of type: jpg, jpeg, png, webp.']],
                    ], 422);
                }

                $decodedData = base64_decode($dataString);
                if ($decodedData === false) {
                    return response()->json([
                        'status' => 'error',
                        'message' => ['image' => ['Failed to decode image.']],
                    ], 422);
                }

                if ($banner->image) {
                    $oldFile = public_path('uploads/' . $banner->image);
                    if (file_exists($oldFile)) {
                        @unlink($oldFile);
                    }
                }

                $dir = public_path('uploads/banners');
                if (!file_exists($dir)) {
                    mkdir($dir, 0755, true);
                }

                $filename = time() . '_' . uniqid() . '.' . $type;
                file_put_contents($dir . '/' . $filename, $decodedData);
                $imagePath = 'banners/' . $filename;
            }
        }

        $banner->update([
            'image' => $imagePath,
            'status' => $request->has('status') ? Banner::STATUS_ACTIVE : Banner::STATUS_INACTIVE,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Banner updated successfully.',
        ]);
    }

    public function toggleStatus(Banner $banner)
    {
        $this->authorize('edit banners');

        $banner->update([
            'status' => $banner->status == Banner::STATUS_ACTIVE ? Banner::STATUS_INACTIVE : Banner::STATUS_ACTIVE,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Banner status updated successfully.',
        ]);
    }

    public function destroy(Banner $banner)
    {
        $this->authorize('delete banners');

        if ($banner->image) {
            $file = public_path('uploads/' . $banner->image);
            if (file_exists($file)) {
                @unlink($file);
            }
        }

        $banner->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Banner deleted successfully.',
        ]);
    }
}
