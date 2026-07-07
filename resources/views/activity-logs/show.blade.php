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

    $old = $log->old_values ?? [];
    $new = $log->new_values ?? [];
    $keys = array_unique(array_merge(array_keys($old), array_keys($new)));

    // Filter out system columns we don't want to show
    $keys = array_filter($keys, function($k) {
        return !in_array($k, ['id', 'created_at', 'updated_at', 'deleted_at', 'password', 'remember_token']);
    });

    // Closure to rename columns
    $renameKey = function($key) {
        $map = [
            'supplier_id' => 'Supplier',
            'customer_id' => 'Customer',
            'user_id' => 'User',
            'created_by' => 'Created By',
            'location_id' => 'Location',
            'coupon_id' => 'Coupon',
            'customer_address_id' => 'Customer Address',
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

        // Handle Foreign Keys
        if (str_ends_with($key, '_id') || $key === 'created_by') {
            if ($key === 'supplier_id') {
                return \App\Models\Supplier::withTrashed()->find($val)?->name ?? "Supplier #$val";
            }
            if ($key === 'customer_id') {
                return \App\Models\Customer::withTrashed()->find($val)?->name ?? "Customer #$val";
            }
            if ($key === 'user_id' || $key === 'created_by') {
                return \App\Models\User::withTrashed()->find($val)?->name ?? "User #$val";
            }
            if ($key === 'location_id') {
                return \App\Models\Location::find($val)?->name ?? "Location #$val";
            }
            if ($key === 'coupon_id') {
                return \App\Models\Coupon::withTrashed()->find($val)?->name ?? "Coupon #$val";
            }
            if ($key === 'customer_address_id') {
                $addr = \App\Models\CustomerAddress::withTrashed()->find($val);
                return $addr ? "{$addr->name} ({$addr->address}, {$addr->city})" : "Address #$val";
            }
        }

        // Handle Status
        if ($key === 'status') {
            if ($log->module === 'Purchase') {
                $statusMap = [
                    1 => 'Pending',
                    2 => 'Approved',
                    3 => 'Declined',
                ];
                return $statusMap[$val] ?? "Status #$val";
            }
            if ($log->module === 'Sales') {
                $statusMap = [
                    1 => 'Pending',
                    2 => 'Approved',
                    3 => 'Shipped',
                    4 => 'Out for Delivery',
                    5 => 'Delivered',
                    6 => 'Declined',
                ];
                return $statusMap[$val] ?? "Status #$val";
            }
        }

        // Handle Payment Status
        if ($key === 'payment_status') {
            $statusMap = [
                1 => 'Pending',
                2 => 'Paid',
            ];
            return $statusMap[$val] ?? "Payment Status #$val";
        }

        if (is_array($val)) {
            return json_encode($val);
        }
        if (is_bool($val)) {
            return $val ? 'true' : 'false';
        }

        return $val;
    };
@endphp

<div class="px-2 py-1">
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
                <span class="fw-semibold text-dark fs-6">{{ $log->subject_type ? class_basename($log->subject_type) . ' #' . $log->subject_id : '-' }}</span>
            </div>
            <div class="col-12">
                <small class="text-muted d-block text-uppercase fw-semibold fs-tiny">Description</small>
                <span class="fw-semibold text-dark fs-6">{{ $log->description ?? '-' }}</span>
            </div>
        </div>
    </div>

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
                            $payStatusColors = [1 => 'bg-label-warning', 2 => 'bg-label-success'];
                            $payStatusLabels = [1 => 'Pending', 2 => 'Paid'];
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
                                        @if($item->variant)
                                            <br><small class="text-muted">{{ $item->variant->name }}</small>
                                        @endif
                                    </td>
                                    <td class="text-center text-dark">{{ $item->quantity }}</td>
                                    <td class="text-end text-dark">{{ number_format($item->purchase_price, 2) }}</td>
                                    <td class="text-end fw-bold text-dark">{{ number_format($item->total, 2) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-2">No items found</td>
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
                            $sStatusColors = [1 => 'bg-label-warning', 2 => 'bg-label-primary', 3 => 'bg-label-info', 4 => 'bg-label-info', 5 => 'bg-label-success', 6 => 'bg-label-danger'];
                            $sStatusLabels = [1 => 'Pending', 2 => 'Approved', 3 => 'Shipped', 4 => 'Out for Delivery', 5 => 'Delivered', 6 => 'Declined'];
                        @endphp
                        <span class="badge {{ $sStatusColors[$subject->status] ?? 'bg-label-secondary' }}">
                            {{ $sStatusLabels[$subject->status] ?? 'Unknown' }}
                        </span>
                    </div>
                    <div class="col-sm-6 col-md-4">
                        <small class="text-muted d-block text-uppercase fw-semibold fs-tiny">Payment Status</small>
                        @php
                            $payStatusColors = [1 => 'bg-label-warning', 2 => 'bg-label-success'];
                            $payStatusLabels = [1 => 'Pending', 2 => 'Paid'];
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
                                        @if($item->variant)
                                            <br><small class="text-muted">{{ $item->variant->name }}</small>
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
                            <td class="fw-bold text-dark">{{ $renameKey($key) }}</td>
                            
                            @if($key === 'permissions' || (is_array($oldVal) || is_array($newVal)))
                                @php
                                    $oldPermissions = is_array($oldVal) ? $oldVal : [];
                                    $newPermissions = is_array($newVal) ? $newVal : [];
                                    
                                    $addedPerms = array_diff($newPermissions, $oldPermissions);
                                    $removedPerms = array_diff($oldPermissions, $newPermissions);
                                    
                                    // Fetch modules for the permissions to group them
                                    $allPermNames = array_unique(array_merge($addedPerms, $removedPerms));
                                    $permModules = [];
                                    if (!empty($allPermNames)) {
                                        $permModules = \Spatie\Permission\Models\Permission::whereIn('name', $allPermNames)
                                            ->pluck('module', 'name')
                                            ->toArray();
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
                                
                                <!-- Old values cell for permissions (Removed Items) -->
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
                                
                                <!-- New values cell for permissions (Added Items) -->
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
                            @else
                                <!-- Standard single attribute fields with resolved names -->
                                <td class="text-danger fw-semibold">{{ $resolveValue($key, $oldVal, $log) }}</td>
                                <td class="text-success fw-semibold">{{ $resolveValue($key, $newVal, $log) }}</td>
                            @endif
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
