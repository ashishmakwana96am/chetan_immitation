<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $attributes = DB::table('attributes')->get();
        $groupedAttrs = [];
        foreach ($attributes as $attr) {
            $key = mb_strtolower(trim($attr->name));
            $groupedAttrs[$key][] = $attr;
        }

        foreach ($groupedAttrs as $key => $group) {
            if (count($group) <= 1) {
                continue;
            }

            usort($group, function($a, $b) {
                $aDeleted = $a->deleted_at !== null ? 1 : 0;
                $bDeleted = $b->deleted_at !== null ? 1 : 0;
                if ($aDeleted !== $bDeleted) {
                    return $aDeleted <=> $bDeleted;
                }
                return $a->id <=> $b->id;
            });

            $primaryAttr = $group[0];
            $duplicateAttrIds = array_map(fn($a) => $a->id, array_slice($group, 1));

            DB::table('attribute_values')->whereIn('attribute_id', $duplicateAttrIds)->update(['attribute_id' => $primaryAttr->id]);

            DB::table('attributes')->whereIn('id', $duplicateAttrIds)->delete();
        }

        $attrValues = DB::table('attribute_values')->get();
        $groupedValues = [];
        foreach ($attrValues as $av) {
            $key = $av->attribute_id . '___' . mb_strtolower(trim($av->value));
            $groupedValues[$key][] = $av;
        }

        foreach ($groupedValues as $key => $group) {
            if (count($group) <= 1) {
                continue;
            }

            usort($group, function($a, $b) {
                $aDeleted = $a->deleted_at !== null ? 1 : 0;
                $bDeleted = $b->deleted_at !== null ? 1 : 0;
                if ($aDeleted !== $bDeleted) {
                    return $aDeleted <=> $bDeleted;
                }
                return $a->id <=> $b->id;
            });

            $primaryValue = $group[0];
            $duplicateValueIds = array_map(fn($v) => $v->id, array_slice($group, 1));

            DB::table('product_variants')->whereIn('attribute_value_id', $duplicateValueIds)->update(['attribute_value_id' => $primaryValue->id]);

            DB::table('attribute_values')->whereIn('id', $duplicateValueIds)->delete();
        }
    }

    public function down(): void
    {
    }
};
