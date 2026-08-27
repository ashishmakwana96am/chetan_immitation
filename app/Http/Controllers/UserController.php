<?php

namespace App\Http\Controllers;

use App\Models\Location;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use App\Models\Role;

class UserController extends Controller
{
    /**
     * Check if logged-in user is restricted to a specific location.
     * Returns location_id if restricted, null if not.
     */
    private function getRestrictedLocationId(): ?int
    {
        $user = auth()->user();
        if ($user->hasRole('super-admin')) {
            return null;
        }
        return $user->location_id ? (int) $user->location_id : null;
    }

    public function index()
    {
        $this->authorize('view users');

        $locationId = $this->getRestrictedLocationId();

        $users = User::with('roles')
            ->where('id', '!=', auth()->id())
            ->whereDoesntHave('roles', fn($q) => $q->where('name', 'super-admin'))
            ->when($locationId, fn($q) => $q->where('location_id', $locationId))
            ->orderBy('id', 'desc')
            ->get();

        $roles = Role::where('name', '!=', 'super-admin')->orderBy('name')->get();

        return view('users.index', compact('users', 'roles'));
    }

    public function data(Request $request)
    {
        $this->authorize('view users');

        $locationId   = $this->getRestrictedLocationId();
        $isSuperAdmin = auth()->user()->hasRole('super-admin');

        $query = User::with($isSuperAdmin ? ['roles', 'location'] : ['roles'])
            ->where('id', '!=', auth()->id())
            ->whereDoesntHave('roles', fn($q) => $q->where('name', 'super-admin'))
            ->when($locationId, fn($q) => $q->where('location_id', $locationId))
            ->orderBy('id', 'desc');

        if ($request->filled('role_id')) {
            $query->whereHas('roles', function ($q) use ($request) {
                $q->where('id', $request->role_id);
            });
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $users             = $query->get();
        $canEdit           = auth()->user()->can('edit users');
        $canDelete         = auth()->user()->can('delete users');
        $canChangePassword = auth()->user()->can('change users password');

        $data = $users->map(function ($user, $index) use ($canEdit, $canDelete, $canChangePassword, $isSuperAdmin) {
            $role = $user->roles->first()
                ? '<span class="badge bg-label-primary text-capitalize">' . $user->roles->first()->name . '</span>'
                : '<span class="badge bg-label-secondary">No Role</span>';

            $status = $canEdit
                ? '<div class="form-check form-switch mb-0"><input class="form-check-input user-status-toggle" type="checkbox" role="switch" data-url="' . route('admin.users.toggle-status', $user) . '" ' . ($user->status == 1 ? 'checked' : '') . ' /></div>'
                : status_badge($user->status);

            $actions = '';
            if ($canEdit || $canChangePassword || $canDelete) {
                $actions = '<div class="dropdown table-action-dropdown">';
                $actions .= '<button class="btn btn-sm btn-label-primary action-dropdown-btn dropdown-toggle" data-bs-toggle="dropdown" data-bs-boundary="viewport" aria-expanded="false"><span>Actions</span></button>';
                $actions .= '<div class="dropdown-menu dropdown-menu-end action-dropdown-menu m-0">';
                if ($canEdit) {
                    $actions .= '<button class="dropdown-item" data-common-modal="' . route('admin.users.edit', $user) . '" data-size="modal-lg"><i class="ti ti-pencil me-2"></i>Edit</button>';
                }
                if ($canChangePassword) {
                    $actions .= '<button class="dropdown-item" data-common-modal="' . route('admin.users.change-password', $user) . '"><i class="ti ti-key me-2"></i>Change Password</button>';
                }
                if ($canDelete) {
                    if ($canEdit || $canChangePassword) {
                        $actions .= '<div class="dropdown-divider"></div>';
                    }
                    $actions .= '<button class="dropdown-item text-danger" data-common-delete="' . route('admin.users.destroy', $user) . '" data-row-id="user-row-' . $user->id . '"><i class="ti ti-trash me-2"></i>Delete</button>';
                }
                $actions .= '</div></div>';
            }

            $row = [
                'index'      => $index + 1,
                'name'       => $user->name,
                'email'      => $user->email,
                'phone'      => $user->phone ?? '-',
                'role'       => $role,
                'status'     => $status,
                'actions'    => $actions,
                'raw_status'  => $user->status,
                'raw_role_id' => $user->role_id,
            ];

            if ($isSuperAdmin) {
                $row['location'] = $user->location->name ?? '-';
            }

            return $row;
        });

        return response()->json(['status' => 'success', 'data' => $data]);
    }

    public function create()
    {
        $this->authorize('create users');

        $locationId = $this->getRestrictedLocationId();

        $roles     = Role::where('name', '!=', 'super-admin')->orderBy('name')->get();
        $locations = Location::where('status', 1)->orderBy('name')->get();

        return view('users.create', compact('roles', 'locations', 'locationId'));
    }

    public function store(Request $request)
    {
        $this->authorize('create users');

        $locationId = $this->getRestrictedLocationId();

        $validator = Validator::make($request->all(), [
            'name'        => ['required', 'string', 'max:100'],
            'email'       => ['required', 'email', Rule::unique('users', 'email')->whereNull('deleted_at')],
            'phone'       => ['nullable', 'string', 'max:20'],
            'password'    => ['required', 'string', 'min:8'],
            'role'        => ['required', 'exists:roles,id'],
            'location_id' => ['nullable', 'exists:locations,id'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'error',
                'message' => $validator->errors(),
            ], 422);
        }

        // Clean up any soft-deleted record with the same email
        User::onlyTrashed()->where('email', $request->email)->forceDelete();

        $role = Role::findById($request->role);

        // Restricted user can only create users in their own location
        $assignedLocationId = $locationId ?? ($request->location_id ?: null);

        $user = User::create([
            'name'        => $request->name,
            'email'       => $request->email,
            'phone'       => $request->phone,
            'password'    => Hash::make($request->password),
            'role_id'     => $role->id,
            'location_id' => $assignedLocationId,
            'status'      => $request->has('status') ? 1 : 2,
        ]);

        $user->assignRole($role);

        return response()->json([
            'status'  => 'success',
            'message' => 'User created successfully.',
        ]);
    }

    public function edit(User $user)
    {
        $this->authorize('edit users');

        $locationId = $this->getRestrictedLocationId();

        // Restricted user cannot edit users from other locations
        if ($locationId && $user->location_id !== $locationId) {
            return redirect()->route('admin.dashboard');
        }

        $roles     = Role::where('name', '!=', 'super-admin')->orderBy('name')->get();
        $locations = Location::where('status', 1)->orderBy('name')->get();
        $userRole  = $user->roles->first()?->id;

        return view('users.edit', compact('user', 'roles', 'locations', 'userRole', 'locationId'));
    }

    public function update(Request $request, User $user)
    {
        $this->authorize('edit users');

        $locationId = $this->getRestrictedLocationId();

        // Restricted user cannot update users from other locations
        if ($locationId && $user->location_id !== $locationId) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized action.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'name'        => ['required', 'string', 'max:100'],
            'email'       => ['required', 'email', Rule::unique('users', 'email')->ignore($user->id)->whereNull('deleted_at')],
            'phone'       => ['nullable', 'string', 'max:20'],
            'role'        => ['required', 'exists:roles,id'],
            'location_id' => ['nullable', 'exists:locations,id'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'error',
                'message' => $validator->errors(),
            ], 422);
        }

        $role = Role::findById($request->role);

        // Restricted user keeps their own location; unrestricted can change it
        $assignedLocationId = $locationId ?? ($request->location_id ?: null);

        $user->update([
            'name'        => $request->name,
            'email'       => $request->email,
            'phone'       => $request->phone,
            'role_id'     => $role->id,
            'location_id' => $assignedLocationId,
            'status'      => $request->has('status') ? 1 : 2,
        ]);

        $user->syncRoles($role);

        return response()->json([
            'status'  => 'success',
            'message' => 'User updated successfully.',
        ]);
    }

    public function toggleStatus(User $user)
    {
        $this->authorize('edit users');

        $locationId = $this->getRestrictedLocationId();

        if ($locationId && $user->location_id !== $locationId) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized action.'], 403);
        }

        $user->update([
            'status' => $user->status == 1 ? 2 : 1,
        ]);

        return response()->json([
            'status'  => 'success',
            'message' => 'User status updated successfully.',
            'data'    => ['status' => $user->status],
        ]);
    }

    public function destroy(User $user)
    {
        $this->authorize('delete users');

        $locationId = $this->getRestrictedLocationId();

        if ($locationId && $user->location_id !== $locationId) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized action.'], 403);
        }

        if ($user->id === auth()->id()) {
            return response()->json([
                'status'  => 'error',
                'message' => 'You cannot delete your own account.',
            ], 422);
        }

        $user->delete();

        return response()->json([
            'status'  => 'success',
            'message' => 'User deleted successfully.',
        ]);
    }

    public function showChangePasswordForm(User $user)
    {
        $this->authorize('change users password');

        $locationId = $this->getRestrictedLocationId();

        if ($locationId && $user->location_id !== $locationId) {
            return redirect()->route('admin.dashboard');
        }

        return view('users.change-password', compact('user'));
    }

    public function changePassword(Request $request, User $user)
    {
        $this->authorize('change users password');

        $locationId = $this->getRestrictedLocationId();

        if ($locationId && $user->location_id !== $locationId) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized action.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'error',
                'message' => $validator->errors(),
            ], 422);
        }

        $user->update([
            'password' => Hash::make($request->password),
        ]);

        return response()->json([
            'status'  => 'success',
            'message' => 'User password changed successfully.',
        ]);
    }
}
