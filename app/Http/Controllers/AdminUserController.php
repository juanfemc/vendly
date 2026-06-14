<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Store;
use App\Services\AdminUpdateService;
use App\Services\CustomerFollowupScheduler;
use App\Services\StoreFileService;
use Illuminate\Support\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class AdminUserController extends Controller
{
    public function __construct(
        private StoreFileService $storeFileService,
        private AdminUpdateService $adminUpdateService,
        private CustomerFollowupScheduler $customerFollowups,
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

        if ($user->role === 'store') {
            $this->syncUserStoresSubscription($user->refresh());
        }

        $this->adminUpdateService->record(
            'Usuario actualizado',
            $user->name,
            'usuario',
            route('admin.users.edit', $user)
        );

        return redirect('/admin/users')->with('success', 'Usuario actualizado.');
    }

    public function extendAccess(Request $request, User $user): RedirectResponse
    {
        abort_unless($user->role === 'store', 404);

        $request->validate([
            'extend_days' => ['required', 'integer', 'min:1', 'max:3650'],
        ]);

        $days = (int) $request->extend_days;
        $today = now()->startOfDay();
        $currentEndsAt = $user->active_ends_at?->copy()->startOfDay();
        $baseDate = $currentEndsAt && $currentEndsAt->greaterThanOrEqualTo($today)
            ? $currentEndsAt
            : $today;
        $startsAt = $user->active_starts_at?->copy()->startOfDay() ?? $today;
        $newEndsAt = $baseDate->copy()->addDays($days);

        $user->update([
            'is_active' => true,
            'active_starts_at' => $startsAt->toDateString(),
            'active_duration_days' => (int) $startsAt->diffInDays($newEndsAt),
            'active_ends_at' => $newEndsAt->toDateString(),
        ]);

        $user->stores()->get()->each(function ($store) use ($newEndsAt) {
            $store->update([
                'is_active' => true,
                'subscription_status' => Store::SUBSCRIPTION_ACTIVE,
                'subscription_ends_at' => $newEndsAt->copy()->endOfDay(),
            ]);

            $this->scheduleSubscriptionReminders($store);
        });

        $this->adminUpdateService->record(
            'Acceso extendido',
            $user->name . ' +' . $days . ' dia(s)',
            'usuario',
            route('admin.users.edit', $user)
        );

        return redirect('/admin/users')->with(
            'success',
            'Acceso extendido hasta el ' . $newEndsAt->format('d/m/Y') . '.'
        );
    }

    public function toggleActive(User $user): RedirectResponse
    {
        abort_unless($user->role === 'store', 404);

        $nextState = ! $user->is_active;

        $user->update([
            'is_active' => $nextState,
        ]);

        $user = $user->refresh();
        $subscriptionIsActive = $nextState && $user->isActive() && $user->active_ends_at;
        $storeUpdates = [
            'is_active' => $nextState,
        ];

        if (Store::supportsSubscriptionColumns()) {
            $storeUpdates['subscription_status'] = $subscriptionIsActive
                ? Store::SUBSCRIPTION_ACTIVE
                : Store::SUBSCRIPTION_PAUSED;
        }

        $user->stores()->update($storeUpdates);

        $user->stores()->get()->each(function ($store) use ($nextState, $user, $subscriptionIsActive) {
            if ($nextState) {
                if (Store::supportsSubscriptionColumns() && $subscriptionIsActive) {
                    $store->update([
                        'subscription_status' => Store::SUBSCRIPTION_ACTIVE,
                        'subscription_ends_at' => $user->active_ends_at->copy()->endOfDay(),
                    ]);
                }

                $this->scheduleSubscriptionReminders($store);

                return;
            }

            $this->cancelPendingFollowups($store, 'El usuario o la tienda fue pausado.');
        });

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

    private function syncUserStoresSubscription(User $user): void
    {
        $subscriptionIsActive = $user->isActive() && $user->active_ends_at;

        $user->stores()->get()->each(function ($store) use ($user, $subscriptionIsActive) {
            $store->update([
                'is_active' => (bool) $user->is_active,
                'subscription_status' => $subscriptionIsActive
                    ? Store::SUBSCRIPTION_ACTIVE
                    : Store::SUBSCRIPTION_PAUSED,
                'subscription_ends_at' => $user->active_ends_at?->copy()->endOfDay(),
            ]);

            if (! $subscriptionIsActive) {
                $this->cancelPendingSubscriptionReminders($store, 'La fecha de vencimiento del usuario cambio o ya no aplica.');

                return;
            }

            $this->scheduleSubscriptionReminders($store);
        });
    }

    private function scheduleSubscriptionReminders($store): void
    {
        try {
            $store = $store->refresh();

            if ($store->subscriptionStatus() !== Store::SUBSCRIPTION_ACTIVE
                || ! $store->subscription_ends_at
                || ! $store->is_active
                || ! $store->user?->isActive()) {
                $this->customerFollowups->cancelPendingSubscriptionReminders(
                    $store,
                    'El plan de la tienda cambio o ya no tiene fecha de vencimiento.',
                );

                return;
            }

            $this->customerFollowups->scheduleSubscriptionReminders($store);
        } catch (\Throwable $exception) {
            Log::warning('No se pudieron programar recordatorios de suscripcion desde usuarios.', [
                'store_id' => $store->id,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    private function cancelPendingFollowups($store, string $reason): void
    {
        try {
            $this->customerFollowups->cancelPendingForStore($store->refresh(), $reason);
        } catch (\Throwable $exception) {
            Log::warning('No se pudieron cancelar seguimientos desde usuarios.', [
                'store_id' => $store->id,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    private function cancelPendingSubscriptionReminders($store, string $reason): void
    {
        try {
            $this->customerFollowups->cancelPendingSubscriptionReminders($store->refresh(), $reason);
        } catch (\Throwable $exception) {
            Log::warning('No se pudieron cancelar recordatorios de suscripcion desde usuarios.', [
                'store_id' => $store->id,
                'error' => $exception->getMessage(),
            ]);
        }
    }
}
