<?php

namespace App\Http\Controllers;

use App\Models\Location;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index()
    {
        $this->authorize('view users');
        $users = User::with('roles')
            ->where('type', '!=', 'super-admin')
            ->latest()
            ->get();
        return view('users.index', compact('users'));
    }

    public function data()
    {
        $this->authorize('view users');

        $users     = User::with('roles')->where('type', '!=', 'super-admin')->latest()->get();
        $canEdit   = auth()->user()->can('edit users');
        $canDelete = auth()->user()->can('delete users');

        $data = $users->map(function ($user, $index) use ($canEdit, $canDelete) {
            $role = $user->roles->first()
                ? '<span class="badge bg-label-primary text-capitalize">' . $user->roles->first()->name . '</span>'
                : '<span class="badge bg-label-secondary">No Role</span>';

            $status = $canEdit
                ? '<div class="form-check form-switch mb-0"><input class="form-check-input user-status-toggle" type="checkbox" role="switch" data-url="' . route('admin.users.toggle-status', $user) . '" ' . ($user->status === 'active' ? 'checked' : '') . ' /></div>'
                : status_badge($user->status);

            $actions = '';
            if ($canEdit) {
                $actions .= '<button class="btn btn-sm btn-icon btn-label-info me-1" data-common-modal="' . route('admin.users.edit', $user) . '" data-size="modal-lg"><i class="ti ti-pencil"></i></button>';
            }
            if ($canDelete) {
                $actions .= '<button class="btn btn-sm btn-icon btn-label-danger" data-common-delete="' . route('admin.users.destroy', $user) . '" data-row-id="user-row-' . $user->id . '"><i class="ti ti-trash"></i></button>';
            }

            return [
                'index'      => $index + 1,
                'name'       => $user->name,
                'email'      => $user->email,
                'phone'      => $user->phone ?? '-',
                'role'       => $role,
                'status'     => $status,
                'actions'    => $actions,
                'raw_status' => $user->status,
                'raw_type'   => $user->type,
            ];
        });

        return response()->json(['status' => 'success', 'data' => $data]);
    }

    public function create()
    {
        $this->authorize('create users');
        $roles     = Role::where('name', '!=', 'super-admin')->orderBy('name')->get();
        $locations = Location::where('status', 'active')->orderBy('name')->get();
        return view('users.create', compact('roles', 'locations'));
    }

    public function store(Request $request)
    {
        $this->authorize('create users');

        $validator = Validator::make($request->all(), [
            'name'        => ['required', 'string', 'max:100'],
            'email'       => ['required', 'email', 'unique:users,email'],
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

        $role = Role::findById($request->role);

        $user = User::create([
            'name'        => $request->name,
            'email'       => $request->email,
            'phone'       => $request->phone,
            'password'    => Hash::make($request->password),
            'type'        => $role->name,
            'location_id' => $request->location_id ?: null,
            'status'      => $request->has('status') ? 'active' : 'inactive',
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
        $roles     = Role::where('name', '!=', 'super-admin')->orderBy('name')->get();
        $locations = Location::where('status', 'active')->orderBy('name')->get();
        $userRole  = $user->roles->first()?->id;
        return view('users.edit', compact('user', 'roles', 'locations', 'userRole'));
    }

    public function update(Request $request, User $user)
    {
        $this->authorize('edit users');

        $validator = Validator::make($request->all(), [
            'name'        => ['required', 'string', 'max:100'],
            'email'       => ['required', 'email', 'unique:users,email,' . $user->id],
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

        $user->update([
            'name'        => $request->name,
            'email'       => $request->email,
            'phone'       => $request->phone,
            'type'        => $role->name,
            'location_id' => $request->location_id ?: null,
            'status'      => $request->has('status') ? 'active' : 'inactive',
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

        $user->update([
            'status' => $user->status === 'active' ? 'inactive' : 'active',
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
}
