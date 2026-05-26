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

        while (
            $model::where('slug', $slug)
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
        if ($user->type === 'super-admin') return true;

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
    function status_badge(string $status): string
    {
        $map = [
            'active'   => 'bg-label-success',
            'inactive' => 'bg-label-danger',
        ];

        $class = $map[$status] ?? 'bg-label-secondary';
        return '<span class="badge ' . $class . '">' . ucfirst($status) . '</span>';
    }
}

if (!function_exists('generate_invoice_no')) {
    /**
     * Generate a unique invoice number.
     *
     * Usage: generate_invoice_no('PUR', PurchaseInvoice::class)
     *        generate_invoice_no('ORD', Order::class, 'order_no')
     */
    function generate_invoice_no(string $prefix, string $model, string $column = 'invoice_no'): string
    {
        $date   = now()->format('Ymd');
        $prefix = strtoupper($prefix) . '-' . $date . '-';

        $last = $model::where($column, 'like', $prefix . '%')
            ->orderByDesc($column)
            ->value($column);

        $next = $last ? (int) substr($last, -4) + 1 : 1;

        return $prefix . str_pad($next, 4, '0', STR_PAD_LEFT);
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
        return config('app.currency_symbol', '$');
    }
}

if (!function_exists('format_price')) {
    /**
     * Return a formatted price with currency symbol.
     *
     * Usage: format_price(1999.5)        → $ 1,999.50
     *        format_price(1999.5, 0)     → $ 2,000
     */
    function format_price(float|int|string $amount, int $decimals = 2): string
    {
        return currency_symbol() . ' ' . number_format((float) $amount, $decimals);
    }
}
