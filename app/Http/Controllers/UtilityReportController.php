<?php

namespace App\Http\Controllers;

use App\Models\UtilityReport;
use App\Models\Location;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

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

        $orderColumnMap = [
            1 => 'created_at',
            2 => 'user_name',
            3 => 'location_name',
            4 => 'module',
            5 => 'action',
            6 => 'description',
        ];

        $query = $this->filteredQuery($request);

        $orderArr = $request->input('order', []);
        $sortKey = 'created_at';
        $sortDir = 'desc';
        if (!empty($orderArr) && isset($orderArr[0]['column'], $orderArr[0]['dir'])) {
            $colIdx = (int) $orderArr[0]['column'];
            $dir = strtolower($orderArr[0]['dir']) === 'asc' ? 'asc' : 'desc';
            if (isset($orderColumnMap[$colIdx])) {
                $sortKey = $orderColumnMap[$colIdx];
                $sortDir = $dir;
            }
        }

        if ($sortKey === 'user_name') {
            $query->orderByRaw("COALESCE(user_name, 'System') {$sortDir}");
        } elseif ($sortKey === 'location_name') {
            $query->orderByRaw("COALESCE(location_name, '-') {$sortDir}");
        } else {
            $query->orderBy($sortKey, $sortDir);
        }
        $query->orderBy('id', $sortDir);

        $logs = $query->skip($start)
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
                'index'           => $start + $index + 1,
                'created_at'      => format_date($log->created_at, 'h:i A'),
                'date_group'      => $log->created_at ? format_date($log->created_at, 'd M Y') : '-',
                'date_sort'       => $log->created_at?->format('YmdHis'),
                'user'            => $userHtml,
                'raw_user_name'   => $log->user_name ?? 'System',
                'location'        => e($log->location_name ?? '-'),
                'raw_location_name'=> $log->location_name ?? '-',
                'module'          => '<span class="badge bg-label-primary">' . e($log->module) . '</span>',
                'raw_module'      => $log->module,
                'action'          => $actionBadge,
                'raw_action'      => $log->action,
                'description'     => e($log->description ?? '-'),
                'raw_description' => $log->description ?? '-',
                'ip_address'      => e($log->ip_address ?? '-'),
                'actions'         => $actions,
                'view_url'        => route('admin.reports.utility.show', $log),
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

    public function exportExcel(Request $request)
    {
        $this->authorize('view utility report');

        $logs = $this->filteredQuery($request)->orderByDesc('created_at')->orderByDesc('id')->get();

        if ($logs->isEmpty()) {
            return redirect()->back()->with('error', 'No data found for the selected filters. Nothing to export.');
        }

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Activity Logs');

        $titleStyle = [
            'font' => ['bold' => true, 'size' => 13, 'color' => ['rgb' => '111827']],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical'   => Alignment::VERTICAL_CENTER,
            ],
            'fill' => [
                'fillType'   => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'F2F4F7'],
            ],
        ];

        $sheet->mergeCells('A1:I1');
        $sheet->setCellValue('A1', 'Activity Log Utility Report Data');
        $sheet->getStyle('A1:I1')->applyFromArray($titleStyle);
        $sheet->getRowDimension(1)->setRowHeight(30);

        $headers = ['#', 'Date & Time', 'User Name', 'Role', 'Location', 'Module', 'Action', 'Description', 'IP Address'];
        $columns = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I'];

        foreach ($headers as $colIdx => $hText) {
            $sheet->setCellValue($columns[$colIdx] . '2', $hText);
        }

        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => '1D2939'], 'size' => 11],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'EAECF0']],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
        ];
        $sheet->getStyle('A2:I2')->applyFromArray($headerStyle);
        $sheet->getRowDimension(2)->setRowHeight(26);

        $rowIndex = 3;
        foreach ($logs as $idx => $log) {
            $dateStr = $log->created_at ? $log->created_at->format('d-m-Y h:i A') : '-';
            $userRole = $log->user_role ? ucwords(str_replace('-', ' ', $log->user_role)) : '-';
            $actionStr = ucwords(str_replace('_', ' ', $log->action));

            $sheet->setCellValue('A' . $rowIndex, $idx + 1);
            $sheet->setCellValue('B' . $rowIndex, $dateStr);
            $sheet->setCellValue('C' . $rowIndex, $log->user_name ?? 'System');
            $sheet->setCellValue('D' . $rowIndex, $userRole);
            $sheet->setCellValue('E' . $rowIndex, $log->location_name ?? '-');
            $sheet->setCellValue('F' . $rowIndex, $log->module);
            $sheet->setCellValue('G' . $rowIndex, $actionStr);
            $sheet->setCellValue('H' . $rowIndex, $log->description ?? '-');
            $sheet->setCellValue('I' . $rowIndex, $log->ip_address ?? '-');

            $sheet->getRowDimension($rowIndex)->setRowHeight(20);
            $rowIndex++;
        }

        $lastRow = $rowIndex - 1;
        $borderStyle = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'D0D5DD'],
                ],
            ],
        ];
        $sheet->getStyle('A2:I' . $lastRow)->applyFromArray($borderStyle);

        $sheet->getStyle('A2:A' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('B2:B' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('I2:I' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        foreach ($columns as $colLetter) {
            $sheet->getColumnDimension($colLetter)->setAutoSize(true);
        }

        ActivityLogger::log('Utility Report', 'export', null, null, null, 'Activity log exported to Excel');

        $writer = new Xlsx($spreadsheet);
        $filename = 'utility_report_' . date('Y_m_d_His') . '.xlsx';

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'max-age=0',
        ]);
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
