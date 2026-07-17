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
        Module::where('route', 'admin.accounting.branch-balances')
            ->update([
                'name'           => 'Opening Balance',
                'route'          => 'admin.accounting.opening-balances',
                'active_pattern' => 'admin/accounting/opening-balances*',
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Module::where('route', 'admin.accounting.opening-balances')
            ->update([
                'name'           => 'Branch Balances',
                'route'          => 'admin.accounting.branch-balances',
                'active_pattern' => 'admin/accounting/branch-balances*',
            ]);
    }
};
