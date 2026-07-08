<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('role_id')->nullable()->after('location_id');
        });

        $modelHasRoles = DB::table('model_has_roles')
            ->where('model_type', 'App\\Models\\User')
            ->get();

        foreach ($modelHasRoles as $pivot) {
            DB::table('users')
                ->where('id', $pivot->model_id)
                ->update(['role_id' => $pivot->role_id]);
        }

        Schema::table('users', function (Blueprint $table) {
            $table->foreign('role_id')->references('id')->on('roles')->onDelete('set null');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'role_id')) {
                $table->dropForeign(['role_id']);
                $table->dropColumn('role_id');
            }
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('type')->nullable()->after('location_id');
        });

        $modelHasRoles = DB::table('model_has_roles')
            ->where('model_type', 'App\\Models\\User')
            ->get();

        foreach ($modelHasRoles as $pivot) {
            $role = DB::table('roles')->where('id', $pivot->role_id)->first();
            if ($role) {
                DB::table('users')
                    ->where('id', $pivot->model_id)
                    ->update(['type' => $role->name]);
            }
        }
    }
};
