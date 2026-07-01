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
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'location_id')) {
                $table->dropColumn('location_id');
            }
            if (Schema::hasColumn('users', 'type')) {
                $table->dropColumn('type');
            }
            $table->foreignId('role_id')->nullable()->constrained('roles')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['role_id']);
            $table->dropColumn('role_id');
            $table->string('type')->nullable();
            $table->unsignedBigInteger('location_id')->nullable();
        });
    }
};
