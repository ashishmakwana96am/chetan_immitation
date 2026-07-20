<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Attribute => list of values, exactly as supplied by the business.
     * Existing attributes/values are left untouched; this only adds
     * whatever from this list is missing.
     */
    private function attributeMap(): array
    {
        return [
            'SIZE' => [
                '2.2', '2.4', '2.6', '2.8', '2.10', '2.12',
                '6"', '7"', '8"', '9"', '10"', '11"', '12"', '16"', '18"', '20"', '22"', '24"', '26"', '30"',
                'ADJSTABLE', 'OPENABLE',
            ],
            'COLOUR' => [
                'PINK', 'GREEN', 'DARK GREEN', 'LIGHT GREEN', 'MARRUN', 'RED', 'BLUE', 'BLACK',
            ],
            'SIZE/COLOUR' => [
                '2.2-PINK', '2.4-PINK', '2.4-GREEN', '2.6-PEACH', '2.6-PINK',
            ],
            'POLISH' => [
                'GOLD', 'ROSE GOLD', 'SILWER', 'MEHNDI POLISH',
            ],
            'DIAL/COLOUR' => [
                'GREEN+WHITE', 'WHITE+GREEN', 'GREEN + GREEN', 'GOLD+PINK',
            ],
        ];
    }

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $now = now();
        $createdBy = DB::table('users')->orderBy('id')->value('id');

        foreach ($this->attributeMap() as $attributeName => $values) {
            $attribute = DB::table('attributes')
                ->whereNull('deleted_at')
                ->whereRaw('LOWER(TRIM(name)) = ?', [mb_strtolower($attributeName)])
                ->first();

            if ($attribute) {
                $attributeId = $attribute->id;
            } else {
                $attributeId = DB::table('attributes')->insertGetId([
                    'name'       => $attributeName,
                    'slug'       => $this->uniqueSlug($attributeName),
                    'status'     => 1,
                    'sort_order' => 0,
                    'created_by' => $createdBy,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            $existingValues = DB::table('attribute_values')
                ->where('attribute_id', $attributeId)
                ->whereNull('deleted_at')
                ->pluck('value')
                ->map(fn ($v) => mb_strtolower(trim($v)))
                ->flip();

            foreach ($values as $value) {
                if (isset($existingValues[mb_strtolower(trim($value))])) {
                    continue;
                }

                DB::table('attribute_values')->insert([
                    'attribute_id' => $attributeId,
                    'value'        => $value,
                    'created_at'   => $now,
                    'updated_at'   => $now,
                ]);

                $existingValues[mb_strtolower(trim($value))] = true;
            }
        }
    }

    private function uniqueSlug(string $name): string
    {
        $slug     = Str::slug($name);
        $original = $slug;
        $count    = 1;

        while (DB::table('attributes')->where('slug', $slug)->exists()) {
            $slug = $original . '-' . $count++;
        }

        return $slug;
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Intentionally left as a no-op: this migration only ever adds
        // attributes/values that were missing, and by the time it could be
        // rolled back they may already be in use by products, so removing
        // them automatically here would be unsafe.
    }
};
