<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Models\Concerns\HasAdminRouteKey;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasAdminRouteKey, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'is_active',
        'active_starts_at',
        'active_duration_days',
        'active_ends_at',
        'admin_token',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'active_starts_at' => 'date',
            'active_ends_at' => 'date',
        ];
    }
    public function stores()
    {
        return $this->hasMany(Store::class);
    }

    public function customerFollowups()
    {
        return $this->hasMany(CustomerFollowup::class);
    }

    public function store()
    {
        return $this->hasOne(Store::class);
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isActive(): bool
    {
        if (! $this->is_active) {
            return false;
        }

        $today = now()->toDateString();

        if ($this->active_starts_at && $this->active_starts_at->toDateString() > $today) {
            return false;
        }

        if ($this->active_ends_at && $this->active_ends_at->toDateString() < $today) {
            return false;
        }

        return true;
    }

    protected function activeRemainingLabel(): Attribute
    {
        return Attribute::get(function (): string {
            if (! $this->is_active) {
                return 'Pausada';
            }

            if ($this->active_starts_at && $this->active_starts_at->isFuture()) {
                $remainingDays = (int) now()->startOfDay()->diffInDays($this->active_starts_at->copy()->startOfDay());

                return 'Inicia en ' . $remainingDays . ' dia(s)';
            }

            if (! $this->active_ends_at) {
                return 'Sin fecha final';
            }

            if ($this->active_ends_at->isPast() && ! $this->active_ends_at->isToday()) {
                return 'Vencida';
            }

            if ($this->active_ends_at->isToday()) {
                return 'Vence hoy';
            }

            $remainingDays = (int) now()->startOfDay()->diffInDays($this->active_ends_at->copy()->startOfDay());

            return $remainingDays . ' dia(s)';
        });
    }
}
