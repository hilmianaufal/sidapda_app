<?php

namespace App\Http\Controllers;

use App\Models\Institution;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;

class UserManagementController extends Controller
{
    public function index(Request $request)
    {
        $q      = $request->input('q');
        $role   = $request->input('role');
        $status = $request->input('status'); // active/inactive

        $roles = Role::orderBy('name')->pluck('name');

        $users = User::query()
            ->with([
                'roles',
                'institutions' => fn ($query) => $query
                    ->where('institutions.is_active', true)
                    ->wherePivot('is_active', true)
                    ->orderBy('institutions.sort_order'),
            ])
            ->when($q, function ($query) use ($q) {
                $query->where(function ($qq) use ($q) {
                    $qq->where('name', 'like', "%{$q}%")
                       ->orWhere('email', 'like', "%{$q}%")
                       ->orWhere('phone', 'like', "%{$q}%");
                });
            })
            ->when($status, function ($query) use ($status) {
                if ($status === 'active') {
                    $query->where('is_active', true);
                } elseif ($status === 'inactive') {
                    $query->where('is_active', false);
                }
            })
            ->when($role, function ($query) use ($role) {
                $query->whereHas('roles', function ($r) use ($role) {
                    $r->where('name', $role);
                });
            })
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('users.index', compact('users', 'q', 'roles', 'role', 'status'));
    }

    public function create()
    {
        $roles = Role::orderBy('name')->pluck('name');
        $institutions = Institution::where('is_active', true)->orderBy('sort_order')->get();

        return view('users.create', compact('roles', 'institutions'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'      => ['required', 'string', 'max:100'],
            'email'     => ['required', 'email', 'max:120', 'unique:users,email'],
            'phone'     => ['nullable', 'string', 'max:30'],
            'password'  => ['required', 'string', 'min:6'],
            'role'      => ['required', 'string', Rule::exists('roles', 'name')],
            'institution_ids' => [Rule::requiredIf(fn () => $request->input('role') !== 'admin'), 'array'],
            'institution_ids.*' => [
                'integer',
                Rule::exists('institutions', 'id')->where('is_active', true),
            ],
            'is_active' => ['nullable'],
            'notes'     => ['nullable', 'string', 'max:500'],
            'avatar'    => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
        ]);

        // upload avatar dulu (hasilnya string path)
        $avatarPath = null;
        if ($request->hasFile('avatar')) {
            $avatarPath = $request->file('avatar')->store('avatars', 'public');
        }

        $user = User::create([
            'name'      => $data['name'],
            'email'     => $data['email'],
            'phone'     => $data['phone'] ?? null,
            'password'  => Hash::make($data['password']),
            'is_active' => (bool) $request->boolean('is_active'),
            'notes'     => $data['notes'] ?? null,
            'avatar'    => $avatarPath, // ✅ string / null
        ]);

        $user->syncRoles([$data['role']]);
        $this->syncInstitutions($user, $data['institution_ids'] ?? []);

        return redirect()->route('users.index')->with('success', 'User berhasil dibuat.');
    }

    public function edit(User $user)
    {
        $roles = Role::orderBy('name')->pluck('name');
        $currentRole = $user->roles->pluck('name')->first();
        $institutions = Institution::where('is_active', true)->orderBy('sort_order')->get();
        $selectedInstitutionIds = $user->institutions()
            ->wherePivot('is_active', true)
            ->pluck('institutions.id')
            ->all();

        return view('users.edit', compact(
            'user',
            'roles',
            'currentRole',
            'institutions',
            'selectedInstitutionIds'
        ));
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'name'      => ['required', 'string', 'max:100'],
            'email'     => ['required', 'email', 'max:120', 'unique:users,email,' . $user->id],
            'phone'     => ['nullable', 'string', 'max:30'],
            'role'      => ['required', 'string', Rule::exists('roles', 'name')],
            'institution_ids' => [Rule::requiredIf(fn () => $request->input('role') !== 'admin'), 'array'],
            'institution_ids.*' => [
                'integer',
                Rule::exists('institutions', 'id')->where('is_active', true),
            ],
            'password'  => ['nullable', 'string', 'min:6'],
            'is_active' => ['nullable'],
            'notes'     => ['nullable', 'string', 'max:500'],
            'avatar'    => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
        ]);

        // update basic fields dulu
        $user->update([
            'name'      => $data['name'],
            'email'     => $data['email'],
            'phone'     => $data['phone'] ?? null,
            'is_active' => (bool) $request->boolean('is_active'),
            'notes'     => $data['notes'] ?? null,
        ]);

        // password opsional
        if (!empty($data['password'])) {
            $user->update(['password' => Hash::make($data['password'])]);
        }

        // upload avatar opsional
        if ($request->hasFile('avatar')) {
            // hapus avatar lama
            if ($user->avatar && Storage::disk('public')->exists($user->avatar)) {
                Storage::disk('public')->delete($user->avatar);
            }

            $path = $request->file('avatar')->store('avatars', 'public');
            $user->update(['avatar' => $path]);
        }

        $user->syncRoles([$data['role']]);
        $this->syncInstitutions($user, $data['institution_ids'] ?? []);

        return redirect()->route('users.index')->with('success', 'User berhasil diperbarui.');
    }

    public function destroy(User $user)
    {
        if (auth()->id() === $user->id) {
            return back()->with('error', 'Tidak bisa menghapus akun sendiri.');
        }

        // hapus avatar file kalau ada
        if ($user->avatar && Storage::disk('public')->exists($user->avatar)) {
            Storage::disk('public')->delete($user->avatar);
        }

        $user->delete();

        return back()->with('success', 'User berhasil dihapus.');
    }

    private function syncInstitutions(User $user, array $institutionIds): void
    {
        $syncData = collect($institutionIds)
            ->unique()
            ->mapWithKeys(fn ($id) => [(int) $id => ['is_active' => true]])
            ->all();

        $user->institutions()->sync($syncData);
    }
}
