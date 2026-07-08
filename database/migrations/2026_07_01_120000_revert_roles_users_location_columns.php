<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * - roles table માંથી location_id remove
     * - users table માંથી role_id remove, type & location_id add
     */
    public function up(): void
    {
        // 1. roles table માંથી location_id foreign key & column drop
        Schema::table('roles', function (Blueprint $table) {
            if (Schema::hasColumn('roles', 'location_id')) {
                $table->dropForeign(['location_id']);
                $table->dropColumn('location_id');
            }
        });

        // 2. users table માંથી role_id drop, type & location_id add
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'role_id')) {
                $table->dropForeign(['role_id']);
                $table->dropColumn('role_id');
            }
            if (!Schema::hasColumn('users', 'type')) {
                $table->string('type')->nullable()->after('password');
            }
            if (!Schema::hasColumn('users', 'location_id')) {
                $table->unsignedBigInteger('location_id')->nullable()->after('type');
            }
        });
    }

    /**
     * Reverse the migrations.
     * - roles table માં location_id add
     * - users table માંથી type & location_id remove, role_id add
     */
    public function down(): void
    {
        // 1. roles table માં location_id પાછું add
        Schema::table('roles', function (Blueprint $table) {
            if (!Schema::hasColumn('roles', 'location_id')) {
                $table->foreignId('location_id')->nullable()->constrained('locations')->nullOnDelete();
            }
        });

        // 2. users table - type & location_id drop, role_id add
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'type')) {
                $table->dropColumn('type');
            }
            if (Schema::hasColumn('users', 'location_id')) {
                $table->dropColumn('location_id');
            }
            if (!Schema::hasColumn('users', 'role_id')) {
                $table->foreignId('role_id')->nullable()->constrained('roles')->nullOnDelete();
            }
        });
    }
};
