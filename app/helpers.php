<?php

use Illuminate\Support\Str;

if (!function_exists('generate_slug')) {
    /**
     * Generate a unique slug for a given model.
     *
     * Usage: generate_slug(\App\Models\Location::class, 'Main Branch')
     *        generate_slug(\App\Models\Location::class, 'Main Branch', 5) // ignore id 5 on update
     */
    function generate_slug(string $model, string $value, ?int $ignoreId = null): string
    {
        $slug     = Str::slug($value);
        $original = $slug;
        $count    = 1;

        $query = in_array(\Illuminate\Database\Eloquent\SoftDeletes::class, class_uses_recursive($model))
            ? $model::withTrashed()
            : $model::query();

        while (
            (clone $query)->where('slug', $slug)
                ->when($ignoreId, fn($q) => $q->where('id', '!=', $ignoreId))
                ->exists()
        ) {
            $slug = $original . '-' . $count++;
        }

        return $slug;
    }
}

if (!function_exists('active_menu')) {
    /**
     * Return 'active' class if the current route matches the given pattern.
     *
     * Usage: active_menu('admin/users*')
     *        active_menu(['admin/users*', 'admin/roles*'])
     */
    function active_menu(string|array $patterns): string
    {
        $patterns = (array) $patterns;
        foreach ($patterns as $pattern) {
            if (request()->is($pattern)) {
                return 'active';
            }
        }
        return '';
    }
}

if (!function_exists('active_menu_open')) {
    /**
     * Return 'active open' class for parent menu items.
     *
     * Usage: active_menu_open(['admin/roles*', 'admin/permissions*'])
     */
    function active_menu_open(string|array $patterns): string
    {
        $patterns = (array) $patterns;
        foreach ($patterns as $pattern) {
            if (request()->is($pattern)) {
                return 'active open';
            }
        }
        return '';
    }
}

if (!function_exists('can_any')) {
    /**
     * Check if the authenticated user has any of the given permissions.
     * Super-admin always returns true.
     *
     * Usage: can_any(['view users', 'create users'])
     */
    function can_any(array $permissions): bool
    {
        $user = auth()->user();
        if (!$user) return false;
        if ($user->hasRole('super-admin')) return true;

        foreach ($permissions as $permission) {
            if ($user->hasPermissionTo($permission)) {
                return true;
            }
        }
        return false;
    }
}

if (!function_exists('format_date')) {
    /**
     * Format a date to a readable string.
     *
     * Usage: format_date($model->created_at)
     *        format_date($model->created_at, 'd/m/Y H:i')
     */
    function format_date(?string $date, string $format = 'd M Y'): string
    {
        if (!$date) return '-';
        return \Carbon\Carbon::parse($date)->format($format);
    }
}

if (!function_exists('status_badge')) {
    /**
     * Return a Bootstrap badge HTML for a status value.
     *
     * Usage: {!! status_badge($user->status) !!}
     */
    function status_badge($status): string
    {
        $map = [
            1   => 'bg-label-success',
            2 => 'bg-label-danger',
            '1'   => 'bg-label-success',
            '2' => 'bg-label-danger',
        ];

        $labels = [
            1   => 'Active',
            2 => 'Inactive',
            '1'   => 'Active',
            '2' => 'Inactive',
        ];

        $class = $map[$status] ?? 'bg-label-secondary';
        $label = $labels[$status] ?? ucfirst($status);
        return '<span class="badge ' . $class . '">' . $label . '</span>';
    }
}

if (!function_exists('parse_user_agent')) {
    /**
     * Parse a raw user agent string into a clean browser name.
     */
    function parse_user_agent(?string $userAgent): string
    {
        if (!$userAgent) {
            return '-';
        }

        $browser = 'Unknown';

        // Detect Browser
        if (preg_match('/edg/i', $userAgent)) {
            $browser = 'Edge';
        } elseif (preg_match('/opr/i', $userAgent) || preg_match('/opera/i', $userAgent)) {
            $browser = 'Opera';
        } elseif (preg_match('/chrome|crios/i', $userAgent)) {
            $browser = 'Chrome';
        } elseif (preg_match('/firefox|fxios/i', $userAgent)) {
            $browser = 'Firefox';
        } elseif (preg_match('/safari/i', $userAgent)) {
            $browser = 'Safari';
        } elseif (preg_match('/msie|trident/i', $userAgent)) {
            $browser = 'Internet Explorer';
        }

        return $browser;
    }
}

if (!function_exists('generate_invoice_no')) {
    /**
     * Generate a unique invoice number.
     *
     * Usage: generate_invoice_no('PUR', Purchase::class)
     *        generate_invoice_no('ORD', Order::class, 'order_no')
     */
    function generate_invoice_no(string $prefix, string $model, string $column = 'invoice_no'): string
    {
        $resolvedPrefix = $prefix;
        if ($prefix === 'OR' || $prefix === 'ORD') {
            $resolvedPrefix = \App\Models\Setting::getValue('prefix_online_order', 'OR');
        } elseif ($prefix === 'SA') {
            $resolvedPrefix = \App\Models\Setting::getValue('prefix_offline_sale', 'SA');
        } elseif ($prefix === 'PS' || $prefix === 'PUR') {
            $resolvedPrefix = \App\Models\Setting::getValue('prefix_supplier_purchase', 'PS');
        } elseif ($prefix === 'GP') {
            $resolvedPrefix = \App\Models\Setting::getValue('prefix_supplier_purchase_gst', 'GP');
        } elseif ($prefix === 'GS') {
            $resolvedPrefix = \App\Models\Setting::getValue('prefix_offline_sale_gst', 'GS');
        } elseif ($prefix === 'ST' || $prefix === 'PB') {
            $resolvedPrefix = \App\Models\Setting::getValue('prefix_stock_transfer', 'ST');
        }

        $resolvedPrefix = strtoupper($resolvedPrefix);

        $query = in_array(\Illuminate\Database\Eloquent\SoftDeletes::class, class_uses_recursive($model))
            ? $model::withTrashed()
            : $model::query();

        $searchPrefix = $resolvedPrefix . '-';
        $prefixLength = strlen($searchPrefix);
        $lastRow = $query->where($column, 'like', $searchPrefix . '%')
            ->get()
            ->filter(function ($item) use ($column, $searchPrefix, $prefixLength) {
                $val = $item->$column;
                $suffix = substr($val, $prefixLength);
                return is_numeric($suffix) && strlen($suffix) > 0;
            })
            ->sortByDesc(function ($item) use ($column, $prefixLength) {
                return (int) substr($item->$column, $prefixLength);
            })
            ->first();

        $last = $lastRow ? $lastRow->$column : null;

        $next = 1;
        if ($last) {
            $numericPart = substr($last, $prefixLength);
            if (is_numeric($numericPart)) {
                $next = (int) $numericPart + 1;
            }
        }

        return $searchPrefix . str_pad($next, 2, '0', STR_PAD_LEFT);
    }
}

if (!function_exists('currency_symbol')) {
    /**
     * Return the currency symbol from .env CURRENCY_SYMBOL.
     *
     * Usage: currency_symbol()
     */
    function currency_symbol(): string
    {
        return config('app.currency_symbol', '₹');
    }
}

if (!function_exists('format_price')) {
    /**
     * Return a formatted price with currency symbol in Indian numbering format.
     */
    function format_price(float|int|string|null $amount, int $decimals = 2): string
    {
        $amount = (float) ($amount ?? 0);
        $negative = $amount < 0 ? '-' : '';
        $amount = abs($amount);
        
        $parts = explode('.', number_format($amount, $decimals, '.', ''));
        $integer = $parts[0];
        $decimal = isset($parts[1]) ? '.' . $parts[1] : '';
        
        $last_three = substr($integer, -3);
        $remaining = substr($integer, 0, -3);
        
        if ($remaining !== '') {
            $remaining = preg_replace('/\B(?=(\d{2})+(?!\d))/', ',', $remaining);
            $integer = $remaining . ',' . $last_three;
        } else {
            $integer = $last_three;
        }
        
        return currency_symbol() . "\u{A0}" . $negative . $integer . $decimal;
    }
}

if (!function_exists('website_price')) {
    function website_price(float|int|string|null $amount): string
    {
        $amount = (float) ($amount ?? 0);
        
        $decimals = (fmod($amount, 1) != 0) ? 2 : 0;
        
        if ($decimals > 0) {
            $formatted = rtrim(number_format($amount, 2, '.', ''), '0');
            $formatted = rtrim($formatted, '.');
        } else {
            $formatted = number_format($amount, 0, '.', '');
        }
        
        $parts = explode('.', $formatted);
        $integer = $parts[0];
        $decimal = isset($parts[1]) ? '.' . $parts[1] : '';
        
        $last_three = substr($integer, -3);
        $remaining = substr($integer, 0, -3);
        
        if ($remaining !== '') {
            $remaining = preg_replace('/\B(?=(\d{2})+(?!\d))/', ',', $remaining);
            $integer = $remaining . ',' . $last_three;
        } else {
            $integer = $last_three;
        }
        
        return '₹' . $integer . $decimal;
    }
}

if (!function_exists('format_stock_quantity')) {
    /**
     * Format stock quantity based on product type (Pair or Pcs) and optional custom size value.
     */
    function format_stock_quantity(?\App\Models\Product $product, int|float $pcs, float|int|null $customSizeValue = null): string
    {
        $pcs = (int) round($pcs);
        if (!$product || !$product->pair_product) {
            return $pcs . ' Pcs';
        }

        if ($customSizeValue !== null && (float) $customSizeValue > 0) {
            $pairSize = (int) round($customSizeValue);
        } else {
            $sizes = collect($product->custom_sizes ?? [])->pluck('size')->map(fn($s) => (int)$s)->filter(fn($s) => $s > 0);
            $pairSize = $sizes->count() > 0 ? (int) $sizes->max() : 2;
        }

        if ($pairSize <= 0) {
            $pairSize = 2;
        }

        $pairs = (int) floor($pcs / $pairSize);
        $remPcs = $pcs % $pairSize;

        if ($pairs > 0 && $remPcs > 0) {
            return $pairs . ' Pair ' . $remPcs . ' Pcs';
        } elseif ($pairs > 0) {
            return $pairs . ' Pair';
        } else {
            return $remPcs . ' Pcs';
        }
    }
}

if (!function_exists('can_modify_past_date_record')) {
    /**
     * Check if current authenticated user has permission to edit/create/delete past or future date records.
     */
    function can_modify_past_date_record($date = null): bool
    {
        $user = auth()->user();
        if (!$user) {
            return false;
        }

        $hasPermission = $user->hasRole('super-admin') || $user->can('edit past date records');

        if (!$date) {
            return $hasPermission;
        }

        if ($hasPermission) {
            return true;
        }

        $formattedDate = $date instanceof \Carbon\Carbon
            ? $date->format('Y-m-d')
            : date('Y-m-d', strtotime($date));

        if ($formattedDate !== date('Y-m-d')) {
            return false;
        }

        return true;
    }
}
