<?php

namespace App\Services;

use Illuminate\Database\Connection;

class ProductBarcodes
{
    public static function key(?string $barcode): string
    {
        return mb_strtolower(trim($barcode ?? ''));
    }

    public static function isTaken(Connection $connection, string $barcode, bool $lock = false): bool
    {
        foreach (['products', 'product_variants'] as $table) {
            $query = $connection->table($table)->where('barcode', $barcode);

            if ($lock) {
                $query->lockForUpdate();
            }

            if ($query->first(['id']) !== null) {
                return true;
            }
        }

        return false;
    }

    public static function allocateVariant(Connection $connection, int $productId): array
    {
        $parentBarcode = trim((string) $connection->table('products')
            ->where('id', $productId)->lockForUpdate()->value('barcode'));

        if ($parentBarcode === '') {
            return [];
        }

        $sequence = (int) $connection->table('product_variants')
            ->where('product_id', $productId)->orderByDesc('barcode_sequence')
            ->lockForUpdate()->value('barcode_sequence');

        do {
            $barcode = $parentBarcode . str_pad((string) ++$sequence, 2, '0', STR_PAD_LEFT);
        } while (self::isTaken($connection, $barcode, lock: true));

        return ['barcode' => $barcode, 'barcode_sequence' => $sequence];
    }

    public static function backfillProduct(Connection $connection, int $productId): void
    {
        $connection->table('product_variants')->where('product_id', $productId)
            ->whereNull('barcode')->chunkById(100, function ($variants) use ($connection, $productId) {
                foreach ($variants as $variant) {
                    $attributes = self::allocateVariant($connection, $productId);
                    if ($attributes !== []) {
                        $connection->table('product_variants')->where('id', $variant->id)->update($attributes);
                    }
                }
            }, 'id');
    }
}
