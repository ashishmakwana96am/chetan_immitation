<?php

namespace App\Http\Controllers;

use App\Models\UtilityReport;
use App\Models\Location;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class UtilityReportController extends Controller
{
    public function index()
    {
        $this->authorize('view utility report');

        $user = auth()->user();
        $isRestricted = $user->location_id && !$user->hasRole('super-admin');

        $usersQuery    = User::orderBy('name');
        $locationsQuery = Location::orderBy('name');
        $modulesQuery  = UtilityReport::select('module')->distinct();
        $actionsQuery  = UtilityReport::select('action')->distinct();

        if ($isRestricted) {
            $usersQuery->where('location_id', $user->location_id);
            $locationsQuery->where('id', $user->location_id);
            $modulesQuery->where('location_id', $user->location_id);
            $actionsQuery->where('location_id', $user->location_id);
        }

        $users     = $usersQuery->get(['id', 'name']);
        $locations = $locationsQuery->get(['id', 'name']);
        $modules   = $modulesQuery->orderBy('module')->pluck('module');
        $actions   = $actionsQuery->orderBy('action')->pluck('action');

        return view('utility-reports.index', compact('users', 'locations', 'modules', 'actions', 'isRestricted'));
    }

    public function data(Request $request)
    {
        $this->authorize('view utility report');

        $user = auth()->user();
        $isRestricted = $user->location_id && !$user->hasRole('super-admin');

        $recordsTotal = UtilityReport::when($isRestricted, fn ($q) => $q->where('location_id', $user->location_id))->count();
        $recordsFiltered = $this->filteredQuery($request)->count();

        $start  = (int) $request->input('start', 0);
        $length = (int) $request->input('length', 25);
        if ($length <= 0) {
            $length = 25;
        }

        $logs = $this->filteredQuery($request)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->skip($start)
            ->take($length)
            ->get();

        $data = $logs->map(function ($log, $index) use ($start) {
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
            $actions .= '<a href="' . route('admin.reports.utility.show', $log) . '" class="dropdown-item"><i class="ti ti-eye me-2"></i>View</a>';
            $actions .= '</div></div>';

            return [
                'index'       => $start + $index + 1,
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
                'view_url'    => route('admin.reports.utility.show', $log),
            ];
        });

        return response()->json([
                'draw'            => (int) $request->input('draw', 1),
                'recordsTotal'    => $recordsTotal,
                'recordsFiltered' => $recordsFiltered,
                'status'          => 'success',
                'data'            => $data,
            ])
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->header('Pragma', 'no-cache')
            ->header('Expires', 'Sat, 01 Jan 2000 00:00:00 GMT');
    }

    public function export(Request $request)
    {
        $this->authorize('view utility report');

        $logs = $this->filteredQuery($request)->orderByDesc('created_at')->orderByDesc('id')->get();

        if ($logs->isEmpty()) {
            return redirect()->back()->with('error', 'No data found for the selected filters. Nothing to export.');
        }

        if ($request->boolean('auto_print') && !$request->boolean('stream')) {
            return view('sales.pdf-print-wrapper', [
                'title'  => 'Utility Report',
                'pdfUrl' => route('admin.reports.utility.export', array_merge($request->all(), ['stream' => 1])),
            ]);
        }

        $pdf = Pdf::loadView('reports.pdf.utility', [
            'logs'      => $logs,
            'startDate' => $request->start_date,
            'endDate'   => $request->end_date,
        ])->setPaper('a4', 'landscape');

        ActivityLogger::log('Utility Report', 'export', null, null, null, 'Activity log exported to PDF');

        return $pdf->stream('utility_report_' . date('Y_m_d_His') . '.pdf');
    }

    public function show(UtilityReport $utilityReport)
    {
        $this->authorize('view utility report');

        return view('utility-reports.show', ['log' => $utilityReport]);
    }

    private function filteredQuery(Request $request)
    {
        $query = UtilityReport::query();

        $user = auth()->user();
        $isRestricted = $user->location_id && !$user->hasRole('super-admin');

        $startDate = $request->start_date ?: now()->subDays(30)->format('Y-m-d');
        $endDate   = $request->end_date ?: now()->format('Y-m-d');

        $query->whereDate('created_at', '>=', $startDate)
            ->whereDate('created_at', '<=', $endDate)
            ->when($request->filled('user_id'), fn ($q) => $q->where('user_id', $request->user_id))
            ->when($request->filled('module'), fn ($q) => $q->where('module', $request->module))
            ->when($request->filled('action'), fn ($q) => $q->where('action', $request->action))
            ->when($request->input('search.value'), function ($q, $search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('user_name', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%")
                        ->orWhere('module', 'like', "%{$search}%")
                        ->orWhere('action', 'like', "%{$search}%")
                        ->orWhere('location_name', 'like', "%{$search}%");
                });
            });

        if ($isRestricted) {
            $query->where('location_id', $user->location_id);
        } else {
            $query->when($request->filled('location_id'), fn ($q) => $q->where('location_id', $request->location_id));
        }

        return $query;
    }
}
