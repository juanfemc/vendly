<?php

namespace App\Http\Controllers;

use App\Models\StoreBanner;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class AdminUserController extends Controller
{
    public function index(): View
    {
        $users = User::where('role', 'store')->latest()->get();

        return view('admin.users.index', compact('users'));
    }

    public function create(): View
    {
        return view('admin.users.create');
    }

    public function edit(User $user): View
    {
        abort_unless($user->role === 'store', 404);

        return view('admin.users.edit', compact('user'));
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'active_starts_at' => ['nullable', 'date'],
            'active_duration_days' => ['nullable', 'integer', 'min:1', 'max:3650'],
        ]);

        $activePeriod = $this->activePeriodData($request);

        User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 'store',
            'is_active' => true,
            ...$activePeriod,
        ]);

        return redirect('/admin/users')->with('success', 'Usuario de tienda creado.');
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        abort_unless($user->role === 'store', 404);

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
            ...$this->activePeriodData($request),
        ];

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $user->update($data);

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

        return redirect('/admin/users')->with(
            'success',
            $nextState ? 'Usuario y tienda reactivados.' : 'Usuario y tienda pausados.'
        );
    }

    public function destroy(User $user): RedirectResponse
    {
        abort_unless($user->role === 'store', 404);

        foreach ($user->stores as $store) {
            foreach ($store->products as $product) {
                if ($product->image) {
                    $this->deletePublicFile($product->image);
                }
            }

            foreach ($store->banners as $banner) {
                $this->deleteBannerImageIfUnused($banner->image, $store->id);
            }

            if ($store->cover_image) {
                $this->deletePublicFile($store->cover_image);
            }

            if ($store->logo_image) {
                $this->deletePublicFile($store->logo_image);
            }
        }

        $user->delete();

        return redirect('/admin/users')->with('success', 'Usuario eliminado.');
    }

    private function deleteBannerImageIfUnused(?string $image, int $storeId): void
    {
        if (! $image) {
            return;
        }

        $isShared = StoreBanner::where('image', $image)
            ->where('store_id', '!=', $storeId)
            ->exists();

        if (! $isShared) {
            $this->deletePublicFile($image);
        }
    }

    private function deletePublicFile(?string $path): void
    {
        if (! $path) {
            return;
        }

        $disk = Storage::disk('public');
        $disk->delete($path);

        if ($disk->exists($path)) {
            @unlink($disk->path($path));
        }
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
