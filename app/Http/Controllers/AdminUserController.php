<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\AdminUpdateService;
use App\Services\StoreFileService;
use Illuminate\Support\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class AdminUserController extends Controller
{
    public function __construct(
        private StoreFileService $storeFileService,
        private AdminUpdateService $adminUpdateService,
    ) {
    }

    public function index(): View
    {
        $users = User::orderByRaw("case when role = 'admin' then 0 else 1 end")
            ->latest()
            ->get();

        return view('admin.users.index', compact('users'));
    }

    public function create(): View
    {
        return view('admin.users.create');
    }

    public function edit(User $user): View
    {
        return view('admin.users.edit', compact('user'));
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email'],
            'role' => ['required', 'in:admin,store'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'active_starts_at' => ['nullable', 'date'],
            'active_duration_days' => ['nullable', 'integer', 'min:1', 'max:3650'],
        ]);

        $role = $request->input('role', 'store');
        $activePeriod = $role === 'store' ? $this->activePeriodData($request) : [
            'active_starts_at' => null,
            'active_duration_days' => null,
            'active_ends_at' => null,
        ];

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $role,
            'is_active' => true,
            ...$activePeriod,
        ]);

        $this->adminUpdateService->record(
            $role === 'admin' ? 'Administrador creado' : 'Usuario de tienda creado',
            $user->name,
            'usuario',
            route('admin.users.edit', $user)
        );

        return redirect('/admin/users')->with('success', $role === 'admin' ? 'Administrador creado.' : 'Usuario de tienda creado.');
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'password' => ['nullable', 'confirmed', Password::defaults()],
            'active_starts_at' => ['nullable', 'date'],
            'active_duration_days' => ['nullable', 'integer', 'min:1', 'max:3650'],
        ]);

        $data = [
            'name' => $request->name,
            'email' => $request->email,
            ...($user->role === 'store' ? $this->activePeriodData($request) : []),
        ];

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $user->update($data);

        $this->adminUpdateService->record(
            'Usuario actualizado',
            $user->name,
            'usuario',
            route('admin.users.edit', $user)
        );

        return redirect('/admin/users')->with('success', 'Usuario actualizado.');
    }

    public function toggleActive(User $user): RedirectResponse
    {
        abort_unless($user->role === 'store', 404);

        $nextState = ! $user->is_active;

        $user->update([
            'is_active' => $nextState,
        ]);

        $user->stores()->update([
            'is_active' => $nextState,
        ]);

        $this->adminUpdateService->record(
            $nextState ? 'Usuario reactivado' : 'Usuario pausado',
            $user->name,
            'usuario',
            route('admin.users.edit', $user)
        );

        return redirect('/admin/users')->with(
            'success',
            $nextState ? 'Usuario y tienda reactivados.' : 'Usuario y tienda pausados.'
        );
    }

    public function destroy(User $user): RedirectResponse
    {
        abort_unless($user->role === 'store', 404);

        foreach ($user->stores as $store) {
            $this->storeFileService->deleteStoreFiles($store);
        }

        $userName = $user->name;
        $user->delete();

        $this->adminUpdateService->record('Usuario eliminado', $userName, 'usuario');

        return redirect('/admin/users')->with('success', 'Usuario eliminado.');
    }

    private function activePeriodData(Request $request): array
    {
        $startsAt = $request->filled('active_starts_at')
            ? Carbon::parse($request->active_starts_at)->toDateString()
            : null;

        $durationDays = $request->filled('active_duration_days')
            ? (int) $request->active_duration_days
            : null;

        $endsAt = $startsAt && $durationDays
            ? Carbon::parse($startsAt)->addDays($durationDays)->toDateString()
            : null;

        return [
            'active_starts_at' => $startsAt,
            'active_duration_days' => $durationDays,
            'active_ends_at' => $endsAt,
        ];
    }
}
