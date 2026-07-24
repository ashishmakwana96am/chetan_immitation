<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Utility Report</title>
    <style>
        @page { size: A4 landscape; margin: 12px; }
        * { box-sizing: border-box; }
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 9px; color: #222; margin: 0; padding: 0; }
        
        .header-table { width: 100%; border-collapse: collapse; margin-bottom: 12px; border-bottom: 2px solid #333; padding-bottom: 6px; }
        .company-name { font-size: 16px; font-weight: bold; color: #111; letter-spacing: 0.5px; }
        .report-title { font-size: 13px; font-weight: bold; color: #444; text-transform: uppercase; text-align: right; }
        .report-meta { font-size: 8.5px; color: #555; margin-top: 3px; }
        
        .summary-box { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
        .summary-box td { border: 1px solid #bbb; padding: 6px 10px; background: #f8f9fa; }
        .summary-label { font-size: 8px; color: #555; text-transform: uppercase; font-weight: bold; }
        .summary-value { font-size: 11px; font-weight: bold; color: #111; margin-top: 2px; }
        
        table.data-table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        table.data-table th, table.data-table td { border: 1px solid #999; padding: 5px 6px; text-align: left; font-size: 8.5px; }
        table.data-table th { background-color: #e9ecef; font-weight: bold; text-transform: uppercase; color: #222; border-bottom: 2px solid #666; }
        
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .fw-bold { font-weight: bold; }

        .badge {
            display: inline-block;
            padding: 2px 5px;
            font-size: 7.5px;
            font-weight: bold;
            border-radius: 3px;
            text-transform: uppercase;
        }
        .bg-success { background-color: #28c76f; color: #fff; }
        .bg-warning { background-color: #ff9f43; color: #fff; }
        .bg-danger { background-color: #ea5455; color: #fff; }
        .bg-info { background-color: #00cfdd; color: #fff; }
        .bg-secondary { background-color: #82868b; color: #fff; }
        .bg-primary { background-color: #7367f0; color: #fff; }
    </style>
</head>
<body>
    <table class="header-table">
        <tr>
            <td style="vertical-align: top;">
                <div class="company-name">CHETAN IMITATION</div>
            </td>
            <td style="vertical-align: top; text-align: right;">
                <div class="report-title">UTILITY REPORT</div>
                <div class="report-meta">
                    Generated Date: {{ date('d-m-Y h:i A') }}
                    @if(!empty($startDate) || !empty($endDate))
                        <br>Period: {{ $startDate ? date('d-m-Y', strtotime($startDate)) : 'All' }} to {{ $endDate ? date('d-m-Y', strtotime($endDate)) : 'Today' }}
                    @endif
                </div>
            </td>
        </tr>
    </table>

    <table class="summary-box">
        <tr>
            <td style="width: 100%;">
                <div class="summary-label">Total Activity Logs</div>
                <div class="summary-value">{{ $logs->count() }}</div>
            </td>
        </tr>
    </table>

    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 4%;" class="text-center">#</th>
                <th style="width: 12%;">Date & Time</th>
                <th style="width: 15%;">User</th>
                <th style="width: 13%;">Location</th>
                <th style="width: 12%;">Module</th>
                <th style="width: 10%;">Action</th>
                <th style="width: 24%;">Description</th>
                <th style="width: 10%;">IP Address</th>
            </tr>
        </thead>
        <tbody>
            @forelse($logs as $index => $log)
                @php
                    $actionClasses = [
                        'create'       => 'bg-success',
                        'update'       => 'bg-warning',
                        'delete'       => 'bg-danger',
                        'login'        => 'bg-info',
                        'login_failed' => 'bg-danger',
                        'logout'       => 'bg-secondary',
                        'import'       => 'bg-info',
                        'export'       => 'bg-info',
                    ];
                    $badgeClass = $actionClasses[$log->action] ?? 'bg-secondary';
                @endphp
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>{{ $log->created_at ? $log->created_at->format('d-m-Y h:i A') : '-' }}</td>
                    <td>
                        <strong>{{ $log->user_name ?? 'System' }}</strong>
                        @if($log->user_role)
                            <br><small style="color: #666;">({{ ucwords(str_replace('-', ' ', $log->user_role)) }})</small>
                        @endif
                    </td>
                    <td>{{ $log->location_name ?? '-' }}</td>
                    <td><span class="badge bg-primary">{{ $log->module }}</span></td>
                    <td><span class="badge {{ $badgeClass }}">{{ ucwords(str_replace('_', ' ', $log->action)) }}</span></td>
                    <td>{{ $log->description ?? '-' }}</td>
                    <td>{{ $log->ip_address ?? '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="text-center" style="padding: 15px;">No activity logs found for the selected criteria.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
