@php
    $subject = null;
    if ($log->subject_type && $log->subject_id) {
        try {
            $query = $log->subject_type::query();
            if (method_exists($log->subject_type, 'withTrashed')) {
                $query->withTrashed();
            }
            $subject = $query->find($log->subject_id);
        } catch (\Exception $e) {
            $subject = null;
        }
    }

    $oldRaw = $log->old_values ?? [];
    $newRaw = $log->new_values ?? [];

    $old = [];
    if (is_array($oldRaw)) {
        if (isset($oldRaw['fields']) && is_array($oldRaw['fields'])) {
            $old = array_merge($oldRaw['fields'], \Illuminate\Support\Arr::except($oldRaw, ['fields']));
        } elseif (isset($oldRaw['attributes']) && is_array($oldRaw['attributes'])) {
            $old = array_merge($oldRaw['attributes'], \Illuminate\Support\Arr::except($oldRaw, ['attributes']));
        } elseif (isset($oldRaw['data']) && is_array($oldRaw['data'])) {
            $old = array_merge($oldRaw['data'], \Illuminate\Support\Arr::except($oldRaw, ['data']));
        } else {
            $old = $oldRaw;
        }
    }

    $new = [];
    if (is_array($newRaw)) {
        if (isset($newRaw['fields']) && is_array($newRaw['fields'])) {
            $new = array_merge($newRaw['fields'], \Illuminate\Support\Arr::except($newRaw, ['fields']));
        } elseif (isset($newRaw['attributes']) && is_array($newRaw['attributes'])) {
            $new = array_merge($newRaw['attributes'], \Illuminate\Support\Arr::except($newRaw, ['attributes']));
        } elseif (isset($newRaw['data']) && is_array($newRaw['data'])) {
            $new = array_merge($newRaw['data'], \Illuminate\Support\Arr::except($newRaw, ['data']));
        } else {
            $new = $newRaw;
        }
    }

    $keys = array_unique(array_merge(array_keys($old), array_keys($new)));

    // Filter out system columns we don't want to show
    $keys = array_filter($keys, function($k) {
        return !in_array($k, ['id', 'created_at', 'updated_at', 'deleted_at', 'password', 'remember_token', 'sort_order','purchase_ids']);
    });

    // Closure to rename columns
    $renameKey = function($key) {
        $map = [
            'supplier_id' => 'Supplier',
            'customer_id' => 'Customer',
            'user_id' => 'User',
            'created_by' => 'Created By',
            'accepted_by' => 'Accepted By',
            'updated_by' => 'Updated By',
            'location_id' => 'Location',
            'from_location_id' => 'From Location',
            'to_location_id' => 'To Location',
            'coupon_id' => 'Coupon',
            'customer_address_id' => 'Customer Address',
            'category_id' => 'Category',
            'sub_category_id' => 'Sub Category',
            'product_id' => 'Product',
            'attribute_id' => 'Attribute',
            'attribute_value_id' => 'Attribute Value',
            'parent_id' => 'Parent Category',
            'state_id' => 'State',
            'role_id' => 'Role',
            'payment_method' => 'Payment Method',
            'payment_status' => 'Payment Status',
            'is_gst' => 'GST Applicable',
            'is_active' => 'Active Status',
            'is_default' => 'Default Location',
            'is_featured' => 'Featured',
            'custom_sizes' => 'Pair Size',
            'stock_items' => 'Stock Items',
            'qty_added' => 'Qty Added',
            'qty_deducted' => 'Qty Deducted',
        ];
        return $map[$key] ?? ucwords(str_replace('_', ' ', $key));
    };

    // Closure to resolve IDs & Statuses to human-readable text
    $resolveValue = function($key, $val, $log) {
        if (is_null($val) || $val === '') {
            if ($key === 'customer_id') {
                return 'Walk-in Customer';
            }
            return '-';
        }

        // Handle User Relations
        if (in_array($key, ['created_by', 'accepted_by', 'updated_by', 'user_id'])) {
            return \App\Models\User::withTrashed()->find($val)?->name ?? "User #$val";
        }

        // Handle Location Relations
        if (in_array($key, ['location_id', 'from_location_id', 'to_location_id'])) {
            return \App\Models\Location::find($val)?->name ?? "Location #$val";
        }

        // Handle Category Relations
        if ($key === 'category_id' || $key === 'parent_id') {
            return \App\Models\Category::withTrashed()->find($val)?->name ?? "Category #$val";
        }

        // Handle SubCategory Relations
        if ($key === 'sub_category_id') {
            return \App\Models\SubCategory::withTrashed()->find($val)?->name ?? "SubCategory #$val";
        }

        // Handle Product Relations
        if ($key === 'product_id') {
            $p = \App\Models\Product::withTrashed()->find($val);
            return $p ? ($p->barcode ? "{$p->name} ({$p->barcode})" : $p->name) : "Product #$val";
        }

        // Handle Variant Relations
        if (in_array($key, ['product_variant_id', 'variant_id'])) {
            $v = \App\Models\ProductVariant::withTrashed()->find($val);
            return $v ? $v->name : "Variant #$val";
        }

        // Handle Attribute & Attribute Value Relations
        if ($key === 'attribute_id') {
            return \App\Models\Attribute::withTrashed()->find($val)?->name ?? "Attribute #$val";
        }
        if ($key === 'attribute_value_id') {
            return \App\Models\AttributeValue::withTrashed()->find($val)?->value ?? "Attribute Value #$val";
        }

        // Handle Foreign Keys
        if (str_ends_with($key, '_id')) {
            if ($key === 'supplier_id') {
                return \App\Models\Supplier::withTrashed()->find($val)?->name ?? "Supplier #$val";
            }
            if ($key === 'customer_id') {
                return \App\Models\Customer::withTrashed()->find($val)?->name ?? "Customer #$val";
            }
            if ($key === 'coupon_id') {
                return \App\Models\Coupon::withTrashed()->find($val)?->code ?? "Coupon #$val";
            }
            if ($key === 'state_id') {
                return \App\Models\State::find($val)?->name ?? "State #$val";
            }
            if ($key === 'customer_address_id') {
                $addr = \App\Models\CustomerAddress::withTrashed()->find($val);
                return $addr ? "{$addr->name} ({$addr->address}, {$addr->city})" : "Address #$val";
            }
        }

        // Handle Payment Method
        if ($key === 'payment_method') {
            $valStr = strtolower((string) $val);
            if ($valStr === 'online_cash') {
                return 'Online + Cash';
            }
            if ($valStr === 'cod') {
                return 'COD';
            }
            if ($valStr === 'upi') {
                return 'UPI';
            }
            return ucwords(str_replace('_', ' + ', $valStr));
        }

        // Handle Status
        if ($key === 'status') {
            $valStr = (string) $val;
            if ($log->module === 'Purchase') {
                $statusMap = [
                    '1' => 'Pending',
                    '2' => 'Approved',
                    '3' => 'Declined',
                ];
                return $statusMap[$valStr] ?? "Status #$val";
            }
            if ($log->module === 'Sales') {
                $statusMap = [
                    '1' => 'Pending',
                    '2' => 'Approved',
                    '3' => 'Shipped',
                    '4' => 'Out for Delivery',
                    '5' => 'Delivered',
                    '6' => 'Declined',
                ];
                return $statusMap[$valStr] ?? "Status #$val";
            }
            if ($log->module === 'Purchase Bill') {
                $statusMap = [
                    '1' => 'Pending',
                    '2' => 'Accepted',
                    '3' => 'Rejected',
                ];
                return $statusMap[$valStr] ?? "Status #$val";
            }
            // Master Tables Status (Active/Inactive)
            $statusMap = [
                '1' => 'Active',
                '2' => 'Inactive',
            ];
            return $statusMap[$valStr] ?? "Status #$val";
        }

        // Handle Payment Status
        if ($key === 'payment_status') {
            $valStr = (string) $val;
            $statusMap = [
                '1' => 'Pending',
                '2' => 'Paid',
                '3' => 'Partially Paid',
            ];
            return $statusMap[$valStr] ?? "Payment Status #$val";
        }

        // Handle Booleans
        if (is_bool($val)) {
            return $val ? 'Yes' : 'No';
        }
        if ($val === 'false' || $val === 'true') {
            return $val === 'true' ? 'Yes' : 'No';
        }

        // Handle Monetary / Currency fields
        if (in_array($key, [
            'total_amount',
            'tax_amount',
            'paid_amount',
            'discount_amount',
            'final_amount',
            'shipping_charge',
            'order_discount_value',
            'price',
            'total',
            'mrp',
            'unit_price',
            'subtotal',
            'sale_price','purchase_price','display_sale_price','display_mrp'
        ])) {
            return is_numeric($val) ? format_price($val) : ($val ?: '-');
        }

        // Handle Discount Type
        if ($key === 'discount_type') {
            if ($val === 'percentage' || $val === 'percent') return 'Percentage (%)';
            if ($val === 'flat' || $val === 'fixed') return 'Flat Amount';
            return ucwords((string) $val);
        }

        if (is_array($val)) {
            return json_encode($val);
        }

        // Handle Date/Datetime values (show time in 12-hour format)
        if (is_string($val) && preg_match('/^\d{4}-\d{2}-\d{2}(?:[ T]\d{2}:\d{2}:\d{2})?$/', $val)) {
            $hasTime = str_contains($val, ':');
            return format_date($val, $hasTime ? 'd M Y h:i A' : 'd M Y');
        }

        return $val;
    };

    $formatSingleValue = function($key, $val, $log) use ($resolveValue) {
        if (is_null($val) || $val === '') {
            return '<span class="text-muted small">-</span>';
        }

        $resolved = $resolveValue($key, $val, $log);

        if (is_string($resolved) && str_contains($resolved, '<') && str_contains($resolved, '>')) {
            return strip_tags($resolved, '<p><br><ul><ol><li><strong><b><em><i><span><div><a><code>');
        }

        return e($resolved);
    };

    // Helper closure to format any array/JSON value into clean human-readable HTML (NO raw JSON dumps)
    $renderArrayValue = function($key, $val, $log) use ($resolveValue, $renameKey) {
        if (is_null($val) || $val === '') {
            return '<span class="text-muted small">-</span>';
        }

        if (!is_array($val)) {
            return e($resolveValue($key, $val, $log));
        }

        if (empty($val)) {
            return '<span class="text-muted small">-</span>';
        }

        if ($key === 'purchase_details' && is_array($val)) {
            $html = '';

            foreach ($val as $purchase) {
                if (!is_array($purchase)) {
                    continue;
                }

                $purchaseId = $purchase['purchase_id'] ?? '-';
                $purchaseNo = $purchase['purchase_no'] ?? '-';
                $supplier = $purchase['supplier'] ?? '-';
                $totalAmount = $purchase['total_amount'] ?? 0;
                $paidAmount = $purchase['paid_amount'] ?? 0;
                $items = $purchase['items'] ?? [];

                $html .= '<div class="border rounded p-3 mb-3 bg-white">';

                // Purchase Header
                $html .= '<div class="row g-3 mb-3">';

                $html .= '<div class="col-md-2">';
                $html .= '<small class="text-muted d-block text-uppercase fw-semibold fs-tiny">Purchase ID</small>';
                $html .= '<span class="fw-bold text-dark">' . e($purchaseId) . '</span>';
                $html .= '</div>';

                $html .= '<div class="col-md-2">';
                $html .= '<small class="text-muted d-block text-uppercase fw-semibold fs-tiny">Purchase No</small>';
                $html .= '<span class="badge bg-label-primary">' . e($purchaseNo) . '</span>';
                $html .= '</div>';

                $html .= '<div class="col-md-3">';
                $html .= '<small class="text-muted d-block text-uppercase fw-semibold fs-tiny">Supplier</small>';
                $html .= '<span class="fw-semibold text-dark">' . e($supplier) . '</span>';
                $html .= '</div>';

                $html .= '<div class="col-md-2">';
                $html .= '<small class="text-muted d-block text-uppercase fw-semibold fs-tiny">Total Amount</small>';
                $html .= '<span class="fw-semibold text-dark">' . e(format_price($totalAmount)) . '</span>';
                $html .= '</div>';

                $html .= '<div class="col-md-2">';
                $html .= '<small class="text-muted d-block text-uppercase fw-semibold fs-tiny">Paid Amount</small>';
                $html .= '<span class="fw-semibold text-dark">' . e(format_price($paidAmount)) . '</span>';
                $html .= '</div>';

                $html .= '</div>';

                // Products / Items
                if (!empty($items) && is_array($items)) {
                    $html .= '<div class="table-responsive border rounded">';
                    $html .= '<table class="table table-sm table-hover mb-0">';
                    $html .= '<thead class="table-light">';
                    $html .= '<tr>';
                    $html .= '<th>Product</th>';
                    $html .= '<th>Barcode</th>';
                    $html .= '<th class="text-center">Qty</th>';
                    $html .= '<th class="text-end">Price</th>';
                    $html .= '<th class="text-end">Total</th>';
                    $html .= '</tr>';
                    $html .= '</thead>';
                    $html .= '<tbody>';

                    foreach ($items as $item) {
                        if (!is_array($item)) {
                            continue;
                        }

                        $productName = $item['product_name'] ?? $item['name'] ?? '-';
                        $barcode = $item['barcode'] ?? '-';
                        $quantity = $item['quantity'] ?? $item['qty'] ?? '-';
                        $price = $item['price'] ?? $item['purchase_price'] ?? 0;
                        $itemTotal = $item['total'] ?? 0;

                        $html .= '<tr>';

                        $html .= '<td class="fw-semibold text-dark">';
                        $html .= e($productName);

                        if (!empty($item['variant_id'])) {
                            $html .= '<br><small class="text-muted">Variant #' . e($item['variant_id']) . '</small>';
                        }

                        $html .= '</td>';

                        $html .= '<td class="text-muted">';
                        $html .= e($barcode);
                        $html .= '</td>';

                        $html .= '<td class="text-center">';
                        $html .= e($quantity);
                        $html .= '</td>';

                        $html .= '<td class="text-end">';
                        $html .= e(format_price($price));
                        $html .= '</td>';

                        $html .= '<td class="text-end fw-semibold">';
                        $html .= e(format_price($itemTotal));
                        $html .= '</td>';

                        $html .= '</tr>';
                    }

                    $html .= '</tbody>';
                    $html .= '</table>';
                    $html .= '</div>';
                }

                $html .= '</div>';
            }

            return $html !== ''
                ? $html
                : '<span class="text-muted small">-</span>';
        }

        $isSequential = array_keys($val) === range(0, count($val) - 1);

        // Sequential list of scalar items (e.g. ['Red', 'Blue'])
        if ($isSequential && collect($val)->every(fn($i) => is_scalar($i))) {
            $badges = array_map(fn($item) => '<span class="badge bg-label-secondary me-1 mb-1">' . e($item) . '</span>', $val);
            return implode(' ', $badges);
        }

        // Key-value dictionary
        if (!$isSequential) {
            $html = '<div class="d-flex flex-column gap-1 small">';
            foreach ($val as $subKey => $subVal) {
                $label = $renameKey($subKey);
                $resolved = is_array($subVal) ? implode(', ', array_map('strval', $subVal)) : $resolveValue($subKey, $subVal, $log);
                $html .= '<div><span class="text-muted me-1">' . e($label) . ':</span> <span class="fw-semibold">' . e($resolved) . '</span></div>';
            }
            $html .= '</div>';
            return $html;
        }

        // List of objects/arrays (e.g. custom sizes, options)
        $first = $val[0] ?? [];
        if (is_array($first)) {
            $headers = array_keys($first);
            $html = '<table class="table table-sm table-borderless mb-0 small">';
            $html .= '<thead><tr class="text-muted text-uppercase fs-tiny">';
            foreach ($headers as $h) {
                $html .= '<th>' . e($renameKey($h)) . '</th>';
            }
            $html .= '</tr></thead><tbody>';
            foreach ($val as $row) {
                $html .= '<tr>';
                foreach ($headers as $h) {
                    $cellVal = $row[$h] ?? '-';

                    if (is_array($cellVal)) {
                        $resolvedCell = collect($cellVal)
                            ->map(function ($item) {
                                if (is_array($item)) {
                                    return collect($item)
                                        ->map(fn ($v) => is_scalar($v) ? (string) $v : json_encode($v))
                                        ->implode(', ');
                                }

                                return is_scalar($item) ? (string) $item : json_encode($item);
                            })
                            ->implode(', ');
                    } else {
                        $resolvedCell = $resolveValue($h, $cellVal, $log);
                    }

                    // Final safety check in case resolveValue() itself returns an array
                    if (is_array($resolvedCell)) {
                        $resolvedCell = json_encode($resolvedCell);
                    }

                    $html .= '<td>' . e((string) $resolvedCell) . '</td>';
                }

                $html .= '</tr>';
            }

            $html .= '</tbody></table>';

            return $html;
        }

        return e(
            collect($val)
                ->map(fn ($item) => is_scalar($item) ? (string) $item : json_encode($item))
                ->implode(', ')
        );
    };
@endphp

<!-- Log Metadata Header -->
<div class="bg-light p-3 rounded mb-4 border" style="background-color: #f8f9fa !important;">
    <div class="row g-3">
        <div class="col-sm-6 col-md-4">
            <small class="text-muted d-block text-uppercase fw-semibold fs-tiny">User</small>
            <span class="fw-semibold text-dark fs-6">{{ $log->user_name ?? 'System' }}</span>
        </div>
        <div class="col-sm-6 col-md-4">
            <small class="text-muted d-block text-uppercase fw-semibold fs-tiny">Role</small>
            <span class="fw-semibold text-dark fs-6">{{ $log->user_role ? ucwords(str_replace('-', ' ', $log->user_role)) : '-' }}</span>
        </div>
        <div class="col-sm-6 col-md-4">
            <small class="text-muted d-block text-uppercase fw-semibold fs-tiny">Location</small>
            <span class="fw-semibold text-dark fs-6">{{ $log->location_name ?? '-' }}</span>
        </div>
        <div class="col-sm-6 col-md-4">
            <small class="text-muted d-block text-uppercase fw-semibold fs-tiny">Module</small>
            <span class="badge bg-label-primary fs-7">{{ $log->module }}</span>
        </div>
        <div class="col-sm-6 col-md-4">
            <small class="text-muted d-block text-uppercase fw-semibold fs-tiny">Action</small>
            <span class="badge bg-label-info fs-7">{{ ucwords(str_replace('_', ' ', $log->action)) }}</span>
        </div>
        <div class="col-sm-6 col-md-4">
            <small class="text-muted d-block text-uppercase fw-semibold fs-tiny">Date &amp; Time</small>
            <span class="fw-semibold text-dark fs-6">{{ format_date($log->created_at, 'd M Y h:i A') }}</span>
        </div>
        <div class="col-sm-6 col-md-4">
            <small class="text-muted d-block text-uppercase fw-semibold fs-tiny">IP Address</small>
            <span class="fw-semibold text-dark fs-6">{{ $log->ip_address ?? '-' }}</span>
        </div>
        <div class="col-sm-6 col-md-4">
            <small class="text-muted d-block text-uppercase fw-semibold fs-tiny">Browser</small>
            <span class="fw-semibold text-dark fs-6"><i class="ti ti-brand-chrome me-1 text-muted"></i>{{ parse_user_agent($log->user_agent) }}</span>
        </div>
        <div class="col-sm-6 col-md-4">
            <small class="text-muted d-block text-uppercase fw-semibold fs-tiny">Record</small>
            <span class="fw-semibold text-dark fs-6">
                @if($log->subject_type && class_basename($log->subject_type) === 'Inventory' && $subject instanceof \App\Models\Inventory)
                    {{ $subject->product?->name ?? ('Inventory #' . $log->subject_id) }}{{ $subject->location?->name ? ' (' . $subject->location->name . ')' : '' }}
                @elseif($log->subject_type && class_basename($log->subject_type) === 'Purchase' && $subject instanceof \App\Models\Purchase)
                    Purchase #{{ $subject->invoice_no }}
                @elseif($log->subject_type && class_basename($log->subject_type) === 'Order' && $subject instanceof \App\Models\Order)
                    Sale #{{ $subject->order_no }}
                @else
                    {{ $log->subject_type ? class_basename($log->subject_type) . ' #' . $log->subject_id : '-' }}
                @endif
            </span>
        </div>
        <div class="col-12">
            <small class="text-muted d-block text-uppercase fw-semibold fs-tiny">Description</small>
            <span class="fw-semibold text-dark fs-6">{{ $log->description ?? '-' }}</span>
        </div>
    </div>
</div>

@php
    $transferSubject = null;
    if ($log->module === 'Purchase Bill' && $subject instanceof \App\Models\PurchaseBill) {
        $transferSubject = $subject;
    } elseif ($log->description && preg_match('/#([A-Za-z0-9-]+)/i', $log->description, $matches)) {
        $trNo = $matches[1];
        $transferSubject = \App\Models\PurchaseBill::with(['fromLocation', 'toLocation', 'createdBy', 'acceptedBy'])->where('transfer_no', $trNo)->first();
    }
@endphp

<!-- Stock Transfer Summary Card -->
@if($transferSubject)
    <div class="card border shadow-none mb-4" style="background-color: #f8f9fa;">
        <div class="card-body p-3">
            <h6 class="card-title fw-bold mb-3 d-flex align-items-center text-info fs-5 border-bottom pb-2">
                <i class="ti ti-arrows-exchange me-2 fs-4"></i> Stock Transfer Details (#{{ $transferSubject->transfer_no }})
            </h6>
            <div class="row g-3">
                <div class="col-sm-6 col-md-3">
                    <small class="text-muted d-block text-uppercase fw-semibold fs-tiny">From Branch (Source)</small>
                    <span class="badge bg-label-secondary fs-6"><i class="ti ti-building me-1"></i>{{ $transferSubject->fromLocation?->name ?? '-' }}</span>
                </div>
                <div class="col-sm-6 col-md-3">
                    <small class="text-muted d-block text-uppercase fw-semibold fs-tiny">To Branch (Destination)</small>
                    <span class="badge bg-label-info fs-6"><i class="ti ti-building me-1"></i>{{ $transferSubject->toLocation?->name ?? '-' }}</span>
                </div>
                <div class="col-sm-6 col-md-3">
                    <small class="text-muted d-block text-uppercase fw-semibold fs-tiny">Transfer Status</small>
                    @php
                        $tStatusColors = [1 => 'bg-label-warning', 2 => 'bg-label-success', 3 => 'bg-label-danger'];
                        $tStatusLabels = [1 => 'Pending', 2 => 'Accepted', 3 => 'Rejected'];
                    @endphp
                    <span class="badge {{ $tStatusColors[$transferSubject->status] ?? 'bg-label-secondary' }}">
                        {{ $tStatusLabels[$transferSubject->status] ?? 'Unknown' }}
                    </span>
                </div>
                <div class="col-sm-6 col-md-3">
                    <small class="text-muted d-block text-uppercase fw-semibold fs-tiny">Transfer Date</small>
                    <span class="fw-bold text-dark fs-6">{{ format_date($transferSubject->created_at, 'd M Y h:i A') }}</span>
                </div>
            </div>
        </div>
    </div>
@endif

<!-- Purchase Summary Card -->
@if($log->module === 'Purchase' && $subject)
    <div class="card border shadow-none mb-4" style="background-color: #fcfcfc;">
        <div class="card-body p-3">
            <h6 class="card-title fw-bold mb-3 d-flex align-items-center text-primary fs-5 border-bottom pb-2">
                <i class="ti ti-shopping-cart-discount me-2 fs-4"></i> Purchase Summary
            </h6>
            <div class="row g-3 mb-3">
                <div class="col-sm-6 col-md-4">
                    <small class="text-muted d-block text-uppercase fw-semibold fs-tiny">Invoice No</small>
                    <span class="fw-bold text-dark fs-6">{{ $subject->invoice_no }}</span>
                </div>
                <div class="col-sm-6 col-md-4">
                    <small class="text-muted d-block text-uppercase fw-semibold fs-tiny">Supplier</small>
                    <span class="fw-bold text-dark fs-6">{{ $subject->supplier?->name ?? '-' }}</span>
                </div>
                <div class="col-sm-6 col-md-4">
                    <small class="text-muted d-block text-uppercase fw-semibold fs-tiny">Date</small>
                    <span class="fw-bold text-dark fs-6">{{ format_date($subject->created_at, 'd M Y') }}</span>
                </div>
                <div class="col-sm-6 col-md-4">
                    <small class="text-muted d-block text-uppercase fw-semibold fs-tiny">Status</small>
                    @php
                        $pStatusColors = [1 => 'bg-label-warning', 2 => 'bg-label-success', 3 => 'bg-label-danger'];
                        $pStatusLabels = [1 => 'Pending', 2 => 'Approved', 3 => 'Declined'];
                    @endphp
                    <span class="badge {{ $pStatusColors[$subject->status] ?? 'bg-label-secondary' }}">
                        {{ $pStatusLabels[$subject->status] ?? 'Unknown' }}
                    </span>
                </div>
                <div class="col-sm-6 col-md-4">
                    <small class="text-muted d-block text-uppercase fw-semibold fs-tiny">Payment Status</small>
                    @php
                        $payStatusColors = [1 => 'bg-label-warning', 2 => 'bg-label-success', 3 => 'bg-label-primary'];
                        $payStatusLabels = [1 => 'Pending', 2 => 'Paid', 3 => 'Partially Paid'];
                    @endphp
                    <span class="badge {{ $payStatusColors[$subject->payment_status] ?? 'bg-label-secondary' }}">
                        {{ $payStatusLabels[$subject->payment_status] ?? 'Unknown' }}
                    </span>
                </div>
                <div class="col-sm-6 col-md-4">
                    <small class="text-muted d-block text-uppercase fw-semibold fs-tiny">Total Amount</small>
                    <span class="fw-bold text-dark fs-6">{{ number_format($subject->total_amount, 2) }}</span>
                </div>
            </div>

            <h6 class="fw-bold mt-4 mb-2 text-dark fs-6">Items Detail</h6>
            <div class="table-responsive border rounded">
                <table class="table table-sm table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Product</th>
                            <th>Barcode</th>
                            <th class="text-center">Qty</th>
                            <th class="text-end">Price</th>
                            <th class="text-end">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($subject->items as $item)
                            <tr>
                                <td>
                                    <span class="fw-semibold text-dark">{{ $item->product?->name ?? '-' }}</span>
                                    @if($item->variant && trim((string)$item->variant->name) !== '')
                                        <br><small class="text-muted">{{ trim($item->variant->name) }}</small>
                                    @endif
                                </td>
                                <td class="text-muted">{{ $item->product?->barcode ?? '-' }}</td>
                                <td class="text-center text-dark">{{ $item->quantity }}</td>
                                <td class="text-end text-dark">{{ number_format($item->purchase_price, 2) }}</td>
                                <td class="text-end fw-bold text-dark">{{ number_format($item->total, 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted py-2">No items found</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endif

<!-- Sales Summary Card -->
@if($log->module === 'Sales' && $subject)
    <div class="card border shadow-none mb-4" style="background-color: #fcfcfc;">
        <div class="card-body p-3">
            <h6 class="card-title fw-bold mb-3 d-flex align-items-center text-success fs-5 border-bottom pb-2">
                <i class="ti ti-receipt me-2 fs-4"></i> Sales Summary
            </h6>
            <div class="row g-3 mb-3">
                <div class="col-sm-6 col-md-4">
                    <small class="text-muted d-block text-uppercase fw-semibold fs-tiny">Order No</small>
                    <span class="fw-bold text-dark fs-6">{{ $subject->order_no }}</span>
                </div>
                <div class="col-sm-6 col-md-4">
                    <small class="text-muted d-block text-uppercase fw-semibold fs-tiny">Customer</small>
                    <span class="fw-bold text-dark fs-6">{{ $subject->customer?->name ?? 'Walk-in Customer' }}</span>
                </div>
                <div class="col-sm-6 col-md-4">
                    <small class="text-muted d-block text-uppercase fw-semibold fs-tiny">Date</small>
                    <span class="fw-bold text-dark fs-6">{{ format_date($subject->created_at, 'd M Y') }}</span>
                </div>
                <div class="col-sm-6 col-md-4">
                    <small class="text-muted d-block text-uppercase fw-semibold fs-tiny">Status</small>
                    @php
                        $sStatusColors = [1 => 'bg-label-warning', 2 => 'bg-label-primary', 3 => 'bg-label-info', 4 => 'bg-label-info', 5 => 'bg-label-delivered', 6 => 'bg-label-danger'];
                        $sStatusLabels = [1 => 'Pending', 2 => 'Approved', 3 => 'Shipped', 4 => 'Out for Delivery', 5 => 'Delivered', 6 => 'Declined'];
                    @endphp
                    <span class="badge {{ $sStatusColors[$subject->status] ?? 'bg-label-secondary' }}">
                        {{ $sStatusLabels[$subject->status] ?? 'Unknown' }}
                    </span>
                </div>
                <div class="col-sm-6 col-md-4">
                    <small class="text-muted d-block text-uppercase fw-semibold fs-tiny">Payment Status</small>
                    @php
                        $payStatusColors = [1 => 'bg-label-warning', 2 => 'bg-label-success', 3 => 'bg-label-primary'];
                        $payStatusLabels = [1 => 'Pending', 2 => 'Paid', 3 => 'Partially Paid'];
                    @endphp
                    <span class="badge {{ $payStatusColors[$subject->payment_status] ?? 'bg-label-secondary' }}">
                        {{ $payStatusLabels[$subject->payment_status] ?? 'Unknown' }}
                    </span>
                </div>
                <div class="col-sm-6 col-md-4">
                    <small class="text-muted d-block text-uppercase fw-semibold fs-tiny">Final Amount</small>
                    <span class="fw-bold text-dark fs-6">{{ number_format($subject->final_amount, 2) }}</span>
                </div>
                <div class="col-sm-6 col-md-4">
                    <small class="text-muted d-block text-uppercase fw-semibold fs-tiny">Discount</small>
                    <span class="fw-bold text-dark fs-6">
                        @if($subject->order_discount_value > 0)
                            {{ number_format($subject->order_discount_value, 2) }}
                            {{ $subject->order_discount_type == 'percentage' ? '%' : '' }}
                        @else
                            -
                        @endif
                    </span>
                </div>
                <div class="col-sm-6 col-md-4">
                    <small class="text-muted d-block text-uppercase fw-semibold fs-tiny">Shipping Charge</small>
                    <span class="fw-bold text-dark fs-6">{{ number_format($subject->shipping_charge, 2) }}</span>
                </div>
            </div>

            <h6 class="fw-bold mt-4 mb-2 text-dark fs-6">Items Detail</h6>
            <div class="table-responsive border rounded">
                <table class="table table-sm table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Product</th>
                            <th class="text-center">Qty</th>
                            <th class="text-end">Price</th>
                            <th class="text-end">Discount</th>
                            <th class="text-end">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($subject->items as $item)
                            <tr>
                                <td>
                                    <span class="fw-semibold text-dark">{{ $item->product?->name ?? '-' }}</span>
                                    @if($item->variant && trim((string)$item->variant->name) !== '')
                                        <br><small class="text-muted">{{ trim($item->variant->name) }}</small>
                                    @endif
                                </td>
                                <td class="text-center text-dark">{{ $item->quantity }}</td>
                                <td class="text-end text-dark">{{ number_format($item->price, 2) }}</td>
                                <td class="text-end text-dark">
                                    @if($item->discount_value > 0)
                                        {{ number_format($item->discount_value, 2) }}
                                        {{ $item->discount_type == 'percentage' ? '%' : '' }}
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="text-end fw-bold text-dark">{{ number_format($item->total, 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted py-2">No items found</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endif

<!-- Changes Log / Comparison table -->
@if(count($keys) > 0)
    <h6 class="fw-bold mb-3 mt-4 text-dark fs-5"><i class="ti ti-git-compare me-2 text-warning fs-4"></i> Attribute Changes</h6>
    <div class="table-responsive border rounded">
        <table class="table table-striped table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th style="width: 25%;">Field</th>
                    <th style="width: 37.5%;">Old Value</th>
                    <th style="width: 37.5%;">New Value</th>
                </tr>
            </thead>
            <tbody>
                @foreach($keys as $key)
                    @php
                        $oldVal = $old[$key] ?? null;
                        $newVal = $new[$key] ?? null;
                    @endphp
                    <tr>
                        @if($key === 'purchase_details' && is_array($newVal))
                            <td colspan="3" class="p-3">
                                <div class="fw-bold text-dark mb-3">
                                    Purchase Details
                                </div>
                                @php
                                    $purchaseDetails = $newVal;
                                @endphp
                                @if(!empty($purchaseDetails))
                                    @foreach($purchaseDetails as $purchase)
                                        @if(!is_array($purchase))
                                            @continue
                                        @endif
                                        @php
                                            $purchaseNo = $purchase['purchase_no'] ?? '-';
                                            $supplier = $purchase['supplier'] ?? '-';
                                            $totalAmount = $purchase['total_amount'] ?? 0;
                                            $paidAmount = $purchase['paid_amount'] ?? 0;
                                            $items = $purchase['items'] ?? [];
                                        @endphp
                                        <div class="border rounded p-3 mb-3 bg-white">
                                            {{-- Purchase Header --}}
                                            <div class="row g-3 mb-3">
                                                <div class="col-sm-6 col-md-3">
                                                    <small class="text-muted d-block text-uppercase fw-semibold fs-tiny">
                                                        Purchase No
                                                    </small>
                                                    <span class="badge bg-label-primary">
                                                        {{ $purchaseNo }}
                                                    </span>
                                                </div>
                                                <div class="col-sm-6 col-md-3">
                                                    <small class="text-muted d-block text-uppercase fw-semibold fs-tiny">
                                                        Supplier
                                                    </small>
                                                    <span class="fw-semibold text-dark">
                                                        {{ $supplier }}
                                                    </span>
                                                </div>
                                                <div class="col-sm-6 col-md-3">
                                                    <small class="text-muted d-block text-uppercase fw-semibold fs-tiny">
                                                        Total Amount
                                                    </small>
                                                    <span class="fw-semibold text-dark">
                                                        {{ format_price($totalAmount) }}
                                                    </span>
                                                </div>
                                                <div class="col-sm-6 col-md-3">
                                                    <small class="text-muted d-block text-uppercase fw-semibold fs-tiny">
                                                        Paid Amount
                                                    </small>
                                                    <span class="fw-semibold text-dark">
                                                        {{ format_price($paidAmount) }}
                                                    </span>
                                                </div>
                                            </div>
                                            {{-- Product Details --}}
                                            @if(!empty($items) && is_array($items))
                                                <div class="table-responsive border rounded">
                                                    <table class="table table-sm table-hover mb-0 w-100">
                                                        <thead class="table-light">
                                                            <tr>
                                                                <th style="width: 35%;">Product</th>
                                                                <th style="width: 20%;">Barcode</th>
                                                                <th style="width: 10%;" class="text-center">Qty</th>
                                                                <th style="width: 17.5%;" class="text-end">Price</th>
                                                                <th style="width: 17.5%;" class="text-end">Total</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            @foreach($items as $item)
                                                                @if(!is_array($item))
                                                                    @continue
                                                                @endif
                                                                @php
                                                                    $productId = $item['product_id'] ?? null;
                                                                    $product = $productId ? \App\Models\Product::withTrashed()->find($productId) : null;
                                                                    $productName = $item['product_name'] ?? ($product?->name ?? '-');
                                                                    $variantId = $item['variant_id'] ?? $item['product_variant_id'] ?? null;
                                                                    $variant = $variantId ? \App\Models\ProductVariant::withTrashed()->find($variantId) : null;
                                                                    $variantName = $variant?->name ?? $variant?->attributeValue?->value ?? null;
                                                                    $barcode = $item['barcode'] ?? $product?->barcode ?? '-';
                                                                    $quantity = $item['quantity'] ?? $item['qty'] ?? '-';
                                                                    $price = $item['price'] ?? $item['purchase_price'] ?? 0;
                                                                    $itemTotal = $item['total'] ?? 0;
                                                                @endphp
                                                                <tr>
                                                                    <td>
                                                                        <span class="fw-semibold text-dark">{{ $productName }}</span>
                                                                        @if($variantName)
                                                                            <br><small class="text-muted">{{ $variantName }}</small>
                                                                        @endif
                                                                    </td>
                                                                    <td class="text-muted">{{ $barcode }}</td>
                                                                    <td class="text-center text-dark">{{ $quantity }}</td>
                                                                    <td class="text-end text-dark">{{ format_price($price) }}</td>
                                                                    <td class="text-end fw-semibold text-dark">{{ format_price($itemTotal) }}</td>
                                                                </tr>
                                                            @endforeach
                                                        </tbody>
                                                    </table>
                                                </div>
                                            @else
                                                <span class="text-muted small">No items found</span>
                                            @endif
                                        </div>
                                    @endforeach
                                @else
                                    <span class="text-muted small">No purchase details found</span>
                                @endif
                            </td>
                        @elseif($key === 'permissions')
                            @php
                                $oldPermissions = is_array($oldVal) ? array_filter($oldVal, 'is_scalar') : [];
                                $newPermissions = is_array($newVal) ? array_filter($newVal, 'is_scalar') : [];
                                $addedPerms = array_diff($newPermissions, $oldPermissions);
                                $removedPerms = array_diff($oldPermissions, $newPermissions);
                                $allPermNames = array_unique(array_merge($addedPerms, $removedPerms));
                                $permModules = [];
                                if (!empty($allPermNames)) {
                                    $permModules = \Spatie\Permission\Models\Permission::whereIn('name', $allPermNames)->pluck('module', 'name')->toArray();
                                }
                                $groupedChanges = [];
                                foreach ($addedPerms as $p) {
                                    $mod = $permModules[$p] ?? 'General';
                                    $groupedChanges[$mod]['added'][] = $p;
                                }
                                foreach ($removedPerms as $p) {
                                    $mod = $permModules[$p] ?? 'General';
                                    $groupedChanges[$mod]['removed'][] = $p;
                                }
                            @endphp
                            <td class="fw-bold text-dark">{{ $renameKey($key) }}</td>
                            <td class="text-danger">
                                @if(!empty($removedPerms))
                                    <div class="d-flex flex-column gap-2">
                                        @foreach($groupedChanges as $module => $changes)
                                            @if(!empty($changes['removed']))
                                                <div>
                                                    <span class="badge bg-label-danger mb-1 fw-bold fs-tiny text-uppercase">{{ $module }}</span>
                                                    <ul class="list-unstyled ps-2 mb-0">
                                                        @foreach($changes['removed'] as $perm)
                                                            <li class="text-danger small"><i class="ti ti-minus me-1"></i>{{ ucwords($perm) }}</li>
                                                        @endforeach
                                                    </ul>
                                                </div>
                                            @endif
                                        @endforeach
                                    </div>
                                @else
                                    <span class="text-muted small">-</span>
                                @endif
                            </td>
                            <td class="text-success">
                                @if(!empty($addedPerms))
                                    <div class="d-flex flex-column gap-2">
                                        @foreach($groupedChanges as $module => $changes)
                                            @if(!empty($changes['added']))
                                                <div>
                                                    <span class="badge bg-label-success mb-1 fw-bold fs-tiny text-uppercase">{{ $module }}</span>
                                                    <ul class="list-unstyled ps-2 mb-0">
                                                        @foreach($changes['added'] as $perm)
                                                            <li class="text-success small"><i class="ti ti-plus me-1"></i>{{ ucwords($perm) }}</li>
                                                        @endforeach
                                                    </ul>
                                                </div>
                                            @endif
                                        @endforeach
                                    </div>
                                @else
                                    <span class="text-muted small">-</span>
                                @endif
                            </td>
                        @elseif($key === 'stock_items' && (is_array($oldVal) || is_array($newVal)))
                            @php
                                $oldStockItems = is_array($oldVal) ? $oldVal : [];
                                $newStockItems = is_array($newVal) ? $newVal : [];

                                $stockRows = [];

                                foreach ($oldStockItems as $oldItem) {
                                    $oldItem = (array) $oldItem;

                                    $matchKey = ($oldItem['product_id'] ?? $oldItem['product_name'] ?? '') . '|' .
                                                ($oldItem['location_id'] ?? $oldItem['location'] ?? '');

                                    $stockRows[$matchKey]['old'] = $oldItem;
                                }

                                foreach ($newStockItems as $newItem) {
                                    $newItem = (array) $newItem;

                                    $matchKey = ($newItem['product_id'] ?? $newItem['product_name'] ?? '') . '|' .
                                                ($newItem['location_id'] ?? $newItem['location'] ?? '');

                                    $stockRows[$matchKey]['new'] = $newItem;
                                }
                            @endphp

                            <td colspan="3" class="p-3">
                                <div class="fw-bold text-dark mb-3">
                                    {{ $renameKey($key) }}
                                </div>
                                @if(!empty($stockRows))
                                    <div class="table-responsive">
                                        <table class="table table-sm table-hover align-middle mb-0 w-100">
                                            <thead class="table-light">
                                                <tr>
                                                    <th style="min-width: 220px;">Product</th>
                                                    <th style="min-width: 120px;">Barcode</th>
                                                    <th style="min-width: 180px;">Location</th>
                                                    <th class="text-center" style="width: 110px;">
                                                        Old Stock
                                                    </th>
                                                    <th class="text-center" style="width: 110px;">
                                                        New Stock
                                                    </th>
                                                    <th class="text-center" style="width: 100px;">
                                                        Change
                                                    </th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($stockRows as $row)
                                                    @php
                                                        $old = $row['old'] ?? [];
                                                        $new = $row['new'] ?? [];

                                                        $item = !empty($new) ? $new : $old;

                                                        $productName = $item['product_name'] ?? '-';
                                                        $barcode = $item['barcode'] ?? '-';
                                                        $location = $item['location'] ?? '-';

                                                        $oldStock = isset($old['stock'])
                                                            ? (float) $old['stock']
                                                            : (float) ($old['quantity'] ?? 0);

                                                        $newStock = isset($new['stock'])
                                                            ? (float) $new['stock']
                                                            : (float) ($new['quantity'] ?? 0);

                                                        $change = $newStock - $oldStock;
                                                    @endphp
                                                    <tr>
                                                        <td>
                                                            <span class="fw-semibold text-dark">
                                                                {{ $productName }}
                                                            </span>
                                                        </td>

                                                        <td>
                                                            <code>{{ $barcode }}</code>
                                                        </td>

                                                        <td>
                                                            <span class="text-dark">
                                                                {{ $location }}
                                                            </span>
                                                        </td>

                                                        <td class="text-center">
                                                            <span class="badge bg-label-secondary">
                                                                {{ rtrim(rtrim(number_format($oldStock, 2), '0'), '.') }}
                                                            </span>
                                                        </td>

                                                        <td class="text-center">
                                                            <span class="badge bg-label-primary">
                                                                {{ rtrim(rtrim(number_format($newStock, 2), '0'), '.') }}
                                                            </span>
                                                        </td>

                                                        <td class="text-center">
                                                            @if($change > 0)
                                                                <span class="text-success fw-bold">
                                                                    +{{ rtrim(rtrim(number_format($change, 2), '0'), '.') }}
                                                                </span>
                                                            @elseif($change < 0)
                                                                <span class="text-danger fw-bold">
                                                                    {{ rtrim(rtrim(number_format($change, 2), '0'), '.') }}
                                                                </span>
                                                            @else
                                                                <span class="text-muted fw-semibold">
                                                                    0
                                                                </span>
                                                            @endif
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @else
                                    <span class="text-muted small">
                                        No stock items found
                                    </span>
                                @endif
                            </td>
                        @elseif($key === 'items' && (is_array($oldVal) || is_array($newVal)))
                            @php
                                $formatItemsList = function($items) {
                                    if (!is_array($items)) return [];
                                    return array_map(function($item) {
                                        $item = (array) $item;
                                        $productId = $item['product_id'] ?? null;
                                        $prod = $productId ? \App\Models\Product::withTrashed()->find($productId) : null;
                                        $rawName = $item['product_name'] ?? $item['name'] ?? ($prod ? $prod->name : '-');
                                        $barcode = $item['barcode'] ?? ($prod ? $prod->barcode : null);
                                        $productName = $rawName;
                                        if (!empty($barcode) && !str_contains($productName, $barcode)) {
                                            $productName .= ' (' . $barcode . ')';
                                        }
                                        if (!empty($item['product_variant_id'])) {
                                            $variant = \App\Models\ProductVariant::withTrashed()->find($item['product_variant_id']);
                                            if ($variant) {

                                                $variantLabel = trim(
                                                    (string) (
                                                        $variant->name
                                                        ?? $variant->attributeValue?->value
                                                        ?? ''
                                                    )
                                                );

                                                if ($variantLabel !== '') {
                                                    $productName .= ' (' . $variantLabel . ')';
                                                }
                                            }
                                        }

                                        $priceVal = $item['price']
                                            ?? $item['purchase_price']
                                            ?? $item['unit_price']
                                            ?? null;

                                        return [
                                            'name' => $productName,

                                            'quantity' => $item['quantity']
                                                ?? $item['qty']
                                                ?? '-',

                                            'price' => is_numeric($priceVal)
                                                ? format_price($priceVal)
                                                : '-',
                                        ];

                                    }, $items);
                                };

                                $oldItemsList = $formatItemsList($oldVal);
                                $newItemsList = $formatItemsList($newVal);
                            @endphp

                            <td class="fw-bold text-dark">
                                {{ $renameKey($key) }}
                            </td>

                            <td class="text-danger fw-semibold">
                                @if(!empty($oldItemsList))
                                    <div class="table-responsive">
                                        <table class="table table-sm table-borderless mb-0">
                                            <thead>
                                                <tr class="text-uppercase fs-tiny text-muted">
                                                    <th>Product</th>
                                                    <th class="text-center">Qty</th>
                                                    <th class="text-end">Price</th>
                                                </tr>
                                            </thead>

                                            <tbody>
                                                @foreach($oldItemsList as $it)
                                                    <tr>
                                                        <td>
                                                            {{ $it['name'] }}
                                                        </td>

                                                        <td class="text-center">
                                                            {{ $it['quantity'] }}
                                                        </td>

                                                        <td class="text-end">
                                                            {{ $it['price'] }}
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @else
                                    <span class="text-muted small">
                                        -
                                    </span>
                                @endif
                            </td>

                            <td class="text-success fw-semibold">
                                @if(!empty($newItemsList))
                                    <div class="table-responsive">
                                        <table class="table table-sm table-borderless mb-0">
                                            <thead>
                                                <tr class="text-uppercase fs-tiny text-muted">
                                                    <th>Product</th>
                                                    <th class="text-center">Qty</th>
                                                    <th class="text-end">Price</th>
                                                </tr>
                                            </thead>

                                            <tbody>
                                                @foreach($newItemsList as $it)
                                                    <tr>
                                                        <td>
                                                            {{ $it['name'] }}
                                                        </td>

                                                        <td class="text-center">
                                                            {{ $it['quantity'] }}
                                                        </td>

                                                        <td class="text-end">
                                                            {{ $it['price'] }}
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @else
                                    <span class="text-muted small">
                                        -
                                    </span>
                                @endif
                            </td>

                        @elseif(is_array($oldVal) || is_array($newVal))

                            <td class="fw-bold text-dark">
                                {{ $renameKey($key) }}
                            </td>

                            <td class="text-danger fw-semibold">
                                {!! $renderArrayValue($key, $oldVal, $log) !!}
                            </td>

                            <td class="text-success fw-semibold">
                                {!! $renderArrayValue($key, $newVal, $log) !!}
                            </td>

                        @else
                            <td class="fw-bold text-dark">
                                {{ $renameKey($key) }}
                            </td>

                            <td class="text-danger fw-semibold">
                                {!! $formatSingleValue($key, $oldVal, $log) !!}
                            </td>

                            <td class="text-success fw-semibold">
                                {!! $formatSingleValue($key, $newVal, $log) !!}
                            </td>
                        @endif
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif
