<?php

namespace App\Http\Controllers;

use App\Services\ProductCollectionSubCategoryUpdateService;
use Illuminate\Http\Request;

class ProductCollectionSubCategoryUpdateController extends Controller
{
    public function update(Request $request, ProductCollectionSubCategoryUpdateService $service)
    {
        $request->validate([
            'excel_file' => ['required', 'file', 'mimes:xlsx,xls', 'max:10240'],
        ]);

        $isDryRun = $request->boolean('dry_run', false);

        set_time_limit(0);

        try {
            $result = $service->processFile($request->file('excel_file'), auth()->id(), $isDryRun);

            return response()->json([
                'status'  => 'success',
                'message' => $isDryRun ? 'Dry run simulation completed! No database changes were saved.' : 'Product SubCategory & Collection updated successfully!',
                'summary' => $result['summary'],
                'details' => $result['details'],
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status'  => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
