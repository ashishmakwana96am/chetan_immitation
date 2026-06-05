<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Module;

class ModuleController extends Controller
{
    public function index()
    {
        $this->authorize('view modules');
        $modules = Module::with('children')->whereNull('parent_id')->orderBy('sort_order')->get();
        return view('modules.index', compact('modules'));
    }

    public function reorder(Request $request)
    {
        $this->authorize('reorder modules');

        $validator = Validator::make($request->all(), [
            'order'              => ['required', 'array'],
            'order.*.id'         => ['required', 'exists:modules,id'],
            'order.*.sort_order' => ['required', 'integer', 'min:0'],
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()], 422);
        }

        foreach ($request->order as $item) {
            Module::where('id', $item['id'])->update(['sort_order' => $item['sort_order']]);
        }

        return response()->json(['status' => 'success', 'message' => 'Order updated.']);
    }
}
