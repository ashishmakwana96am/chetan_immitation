<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE products MODIFY COLUMN type ENUM('regular', 'variable', 'normal') NOT NULL DEFAULT 'regular' AFTER `status`");
        DB::statement("UPDATE products SET type = 'normal' WHERE type = 'regular'");
        DB::statement("ALTER TABLE products MODIFY COLUMN type ENUM('normal', 'variable') NOT NULL DEFAULT 'normal' AFTER `status`");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE products MODIFY COLUMN type ENUM('normal', 'variable', 'regular') NOT NULL DEFAULT 'normal' AFTER `status`");
        DB::statement("UPDATE products SET type = 'regular' WHERE type = 'normal'");
        DB::statement("ALTER TABLE products MODIFY COLUMN type ENUM('regular', 'variable') NOT NULL DEFAULT 'regular' AFTER `status`");
    }
};
