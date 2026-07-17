<?php

use App\Models\Module;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $parent = Module::where('name', 'Accounting')->first();
        if ($parent) {
            Module::create([
                'parent_id'      => $parent->id,
                'name'           => 'Opening Balance',
                'icon'           => 'ti ti-building-bank',
                'route'          => 'admin.accounting.opening-balances',
                'active_pattern' => 'admin/accounting/opening-balances*',
                'permission'     => 'manage branch balances',
                'sort_order'     => 6,
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Module::where('route', 'admin.accounting.branch-balances')->forceDelete();
    }
};
