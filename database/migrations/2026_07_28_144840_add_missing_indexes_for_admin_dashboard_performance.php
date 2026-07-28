<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('orders')) {
            Schema::table('orders', function (Blueprint $table) {
                if (!$this->indexExists('orders', 'orders_status_index')) {
                    $table->index('status', 'orders_status_index');
                }
                if (!$this->indexExists('orders', 'orders_location_id_status_index')) {
                    $table->index(['location_id', 'status'], 'orders_location_id_status_index');
                }
                if (!$this->indexExists('orders', 'orders_order_type_status_index')) {
                    $table->index(['order_type', 'status'], 'orders_order_type_status_index');
                }
            });
        }

        if (Schema::hasTable('contact_inquiries')) {
            Schema::table('contact_inquiries', function (Blueprint $table) {
                if (!$this->indexExists('contact_inquiries', 'contact_inquiries_created_at_index')) {
                    $table->index('created_at', 'contact_inquiries_created_at_index');
                }
            });
        }

        if (Schema::hasTable('purchase_bills')) {
            Schema::table('purchase_bills', function (Blueprint $table) {
                if (!$this->indexExists('purchase_bills', 'purchase_bills_status_index')) {
                    $table->index('status', 'purchase_bills_status_index');
                }
                if (!$this->indexExists('purchase_bills', 'purchase_bills_from_location_id_status_index')) {
                    $table->index(['from_location_id', 'status'], 'purchase_bills_from_location_id_status_index');
                }
                if (!$this->indexExists('purchase_bills', 'purchase_bills_to_location_id_status_index')) {
                    $table->index(['to_location_id', 'status'], 'purchase_bills_to_location_id_status_index');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('orders')) {
            Schema::table('orders', function (Blueprint $table) {
                if ($this->indexExists('orders', 'orders_status_index')) {
                    $table->dropIndex('orders_status_index');
                }
                if ($this->indexExists('orders', 'orders_location_id_status_index')) {
                    $table->dropIndex('orders_location_id_status_index');
                }
                if ($this->indexExists('orders', 'orders_order_type_status_index')) {
                    $table->dropIndex('orders_order_type_status_index');
                }
            });
        }

        if (Schema::hasTable('contact_inquiries')) {
            Schema::table('contact_inquiries', function (Blueprint $table) {
                if ($this->indexExists('contact_inquiries', 'contact_inquiries_created_at_index')) {
                    $table->dropIndex('contact_inquiries_created_at_index');
                }
            });
        }

        if (Schema::hasTable('purchase_bills')) {
            Schema::table('purchase_bills', function (Blueprint $table) {
                if ($this->indexExists('purchase_bills', 'purchase_bills_status_index')) {
                    $table->dropIndex('purchase_bills_status_index');
                }
                if ($this->indexExists('purchase_bills', 'purchase_bills_from_location_id_status_index')) {
                    $table->dropIndex('purchase_bills_from_location_id_status_index');
                }
                if ($this->indexExists('purchase_bills', 'purchase_bills_to_location_id_status_index')) {
                    $table->dropIndex('purchase_bills_to_location_id_status_index');
                }
            });
        }
    }

    private function indexExists(string $table, string $indexName): bool
    {
        $connection = Schema::getConnection();
        $indexes = $connection->select("SHOW INDEX FROM `{$table}` WHERE Key_name = ?", [$indexName]);

        return count($indexes) > 0;
    }
};
