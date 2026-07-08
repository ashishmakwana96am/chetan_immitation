<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Location;
use App\Models\User;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    public function index()
    {
        $this->authorize('view activity logs');

        $users     = User::orderBy('name')->get(['id', 'name']);
        $locations = Location::orderBy('name')->get(['id', 'name']);
        $modules   = ActivityLog::select('module')->distinct()->orderBy('module')->pluck('module');
        $actions   = ActivityLog::select('action')->distinct()->orderBy('action')->pluck('action');

        return view('activity-logs.index', compact('users', 'locations', 'modules', 'actions'));
    }

    public function data(Request $request)
    {
        $this->authorize('view activity logs');

        $logs = $this->filteredQuery($request)->orderByDesc('created_at')->orderByDesc('id')->get();

        $data = $logs->map(function ($log, $index) {
            $actionColors = [
                'create'       => 'bg-label-success',
                'update'       => 'bg-label-warning',
                'delete'       => 'bg-label-danger',
                'login'        => 'bg-label-info',
                'login_failed' => 'bg-label-danger',
                'logout'       => 'bg-label-secondary',
                'import'       => 'bg-label-info',
                'export'       => 'bg-label-info',
            ];
            $actionBadge = '<span class="badge ' . ($actionColors[$log->action] ?? 'bg-label-secondary') . '">' . ucwords(str_replace('_', ' ', $log->action)) . '</span>';

            $userHtml = '<div><span class="fw-semibold">' . e($log->user_name ?? 'System') . '</span>';
            if ($log->user_role) {
                $userHtml .= '<br><small class="text-muted">' . e(ucwords(str_replace('-', ' ', $log->user_role))) . '</small>';
            }
            $userHtml .= '</div>';

            $actions = '<div class="dropdown table-action-dropdown">';
            $actions .= '<button class="btn btn-sm btn-label-primary action-dropdown-btn dropdown-toggle" data-bs-toggle="dropdown" data-bs-boundary="viewport" aria-expanded="false"><span>Actions</span></button>';
            $actions .= '<div class="dropdown-menu dropdown-menu-end action-dropdown-menu m-0">';
            $actions .= '<button type="button" class="dropdown-item" data-common-modal="' . route('admin.reports.utility.show', $log) . '"><i class="ti ti-eye me-2"></i>View</button>';
            $actions .= '</div></div>';

            return [
                'index'       => $index + 1,
                'created_at'  => format_date($log->created_at, 'h:i A'),
                'date_group'  => $log->created_at ? format_date($log->created_at, 'd M Y') : '-',
                'date_sort'   => $log->created_at?->format('YmdHis'),
                'user'        => $userHtml,
                'location'    => e($log->location_name ?? '-'),
                'module'      => '<span class="badge bg-label-primary">' . e($log->module) . '</span>',
                'action'      => $actionBadge,
                'description' => e($log->description ?? '-'),
                'ip_address'  => e($log->ip_address ?? '-'),
                'actions'     => $actions,
            ];
        });

        return response()->json(['status' => 'success', 'data' => $data])
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->header('Pragma', 'no-cache')
            ->header('Expires', 'Sat, 01 Jan 2000 00:00:00 GMT');
    }

    public function show(ActivityLog $activityLog)
    {
        $this->authorize('view activity logs');

        return view('activity-logs.show', ['log' => $activityLog]);
    }

    private function filteredQuery(Request $request)
    {
        $query = ActivityLog::query();

        $startDate = $request->start_date ?: now()->subDays(30)->format('Y-m-d');
        $endDate   = $request->end_date ?: now()->format('Y-m-d');

        $query->whereDate('created_at', '>=', $startDate)
            ->whereDate('created_at', '<=', $endDate)
            ->when($request->filled('user_id'), fn ($q) => $q->where('user_id', $request->user_id))
            ->when($request->filled('module'), fn ($q) => $q->where('module', $request->module))
            ->when($request->filled('action'), fn ($q) => $q->where('action', $request->action))
            ->when($request->filled('location_id'), fn ($q) => $q->where('location_id', $request->location_id));

        return $query;
    }
}
