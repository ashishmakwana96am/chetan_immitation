<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->foreignId('location_id')->nullable()->after('id')->constrained('locations')->nullOnDelete();
        });

        $this->backfillLocations();
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropForeign(['location_id']);
            $table->dropColumn('location_id');
        });
    }

    /**
     * Assign every existing customer to the branch they've sold the most at
     * (matched via orders.customer_id / orders.location_id). Customers with
     * no sale history fall back to the default location — same convention
     * used elsewhere in the app (Location::where('is_default', true)).
     */
    private function backfillLocations(): void
    {
        $defaultLocationId = DB::table('locations')->where('is_default', true)->value('id')
            ?? DB::table('locations')->orderBy('id')->value('id');

        if (!$defaultLocationId) {
            return;
        }

        $topLocationByCustomer = DB::table('orders')
            ->select('customer_id', 'location_id', DB::raw('COUNT(*) as cnt'))
            ->where('order_type', 'sale')
            ->whereNotNull('customer_id')
            ->whereNotNull('location_id')
            ->whereNull('deleted_at')
            ->groupBy('customer_id', 'location_id')
            ->orderBy('customer_id')
            ->orderByDesc('cnt')
            ->get()
            ->unique('customer_id')
            ->pluck('location_id', 'customer_id');

        $customerIdsByLocation = [];
        foreach ($topLocationByCustomer as $customerId => $locationId) {
            $customerIdsByLocation[$locationId][] = $customerId;
        }

        foreach ($customerIdsByLocation as $locationId => $customerIds) {
            DB::table('customers')->whereIn('id', $customerIds)->update(['location_id' => $locationId]);
        }

        DB::table('customers')->whereNull('location_id')->update(['location_id' => $defaultLocationId]);
    }
};
