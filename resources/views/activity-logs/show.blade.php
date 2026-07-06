<div class="p-4">
    <div class="row g-3 mb-3">
        <div class="col-md-6">
            <small class="text-muted d-block">User</small>
            <span class="fw-semibold">{{ $log->user_name ?? 'System' }}</span>
        </div>
        <div class="col-md-6">
            <small class="text-muted d-block">Role</small>
            <span class="fw-semibold">{{ $log->user_role ? ucwords(str_replace('-', ' ', $log->user_role)) : '-' }}</span>
        </div>
        <div class="col-md-6">
            <small class="text-muted d-block">Location</small>
            <span class="fw-semibold">{{ $log->location_name ?? '-' }}</span>
        </div>
        <div class="col-md-6">
            <small class="text-muted d-block">Module</small>
            <span class="fw-semibold">{{ $log->module }}</span>
        </div>
        <div class="col-md-6">
            <small class="text-muted d-block">Action</small>
            <span class="fw-semibold">{{ ucwords(str_replace('_', ' ', $log->action)) }}</span>
        </div>
        <div class="col-md-6">
            <small class="text-muted d-block">Date &amp; Time</small>
            <span class="fw-semibold">{{ format_date($log->created_at, 'd M Y H:i:s') }}</span>
        </div>
        <div class="col-md-6">
            <small class="text-muted d-block">IP Address</small>
            <span class="fw-semibold">{{ $log->ip_address ?? '-' }}</span>
        </div>
        <div class="col-md-6">
            <small class="text-muted d-block">Record</small>
            <span class="fw-semibold">{{ $log->subject_type ? class_basename($log->subject_type) . ' #' . $log->subject_id : '-' }}</span>
        </div>
        <div class="col-12">
            <small class="text-muted d-block">Description</small>
            <span class="fw-semibold">{{ $log->description ?? '-' }}</span>
        </div>
        <div class="col-12">
            <small class="text-muted d-block">Browser / Device</small>
            <span class="fw-semibold text-break">{{ $log->user_agent ?? '-' }}</span>
        </div>
    </div>

    @php
        $old = $log->old_values ?? [];
        $new = $log->new_values ?? [];
        $keys = array_unique(array_merge(array_keys($old), array_keys($new)));
    @endphp

    @if(count($keys) > 0)
        <hr>
        <h6 class="fw-semibold mb-3">Changes</h6>
        <div class="table-responsive">
            <table class="table table-sm table-bordered">
                <thead>
                    <tr>
                        <th>Field</th>
                        <th>Old Value</th>
                        <th>New Value</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($keys as $key)
                        @php
                            $oldVal = $old[$key] ?? null;
                            $newVal = $new[$key] ?? null;
                            $format = fn($v) => is_array($v) ? json_encode($v) : (is_bool($v) ? ($v ? 'true' : 'false') : (is_null($v) ? '-' : $v));
                        @endphp
                        <tr>
                            <td class="fw-semibold">{{ ucwords(str_replace('_', ' ', $key)) }}</td>
                            <td class="text-danger">{{ $format($oldVal) }}</td>
                            <td class="text-success">{{ $format($newVal) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
