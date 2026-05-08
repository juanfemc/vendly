@extends('layouts.admin')

@section('content')
<div class="header">
    <h2>Usuarios</h2>
    <a href="/admin/users/create" class="btn">Crear usuario</a>
</div>

@if (session('success'))
    <div class="flash success">{{ session('success') }}</div>
@endif

@if ($users->isEmpty())
    <div class="users-empty">
        <h3>No hay usuarios registrados</h3>
        <p>Crea el primer usuario para asignarle una tienda y controlar su acceso.</p>
        <a href="/admin/users/create" class="btn">Crear usuario</a>
    </div>
@endif

<style>
    .users-list {
        display: grid;
        gap: 14px;
    }

    .users-empty {
        padding: 28px;
        border: 1px dashed #cbd5e1;
        border-radius: 12px;
        background: #ffffff;
        text-align: center;
    }

    .users-empty h3 {
        margin: 0 0 8px;
        color: #111827;
        font-size: 20px;
    }

    .users-empty p {
        margin: 0 0 18px;
        color: #6b7280;
    }

    .user-card {
        display: grid;
        grid-template-columns: minmax(0, 1fr) auto;
        gap: 18px;
        align-items: start;
        padding: 18px;
        border: 1px solid #e5e7eb;
    }

    .user-card-main {
        min-width: 0;
    }

    .user-card-header {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 14px;
    }

    .user-identity {
        display: flex;
        align-items: center;
        gap: 12px;
        min-width: 0;
    }

    .user-avatar {
        width: 44px;
        height: 44px;
        flex: 0 0 44px;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: #eef2ff;
        color: #3730a3;
        font-weight: 800;
        text-transform: uppercase;
    }

    .user-name {
        margin: 0;
        color: #111827;
        font-size: 18px;
        line-height: 1.2;
    }

    .user-email {
        margin: 4px 0 0;
        color: #6b7280;
        font-size: 14px;
        overflow-wrap: anywhere;
    }

    .user-badges {
        display: flex;
        flex-wrap: wrap;
        justify-content: flex-end;
        gap: 8px;
    }

    .user-badge {
        min-height: 28px;
        padding: 6px 10px;
        border-radius: 999px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 12px;
        font-weight: 800;
        line-height: 1;
        white-space: nowrap;
    }

    .user-badge--role {
        background: #f3f4f6;
        color: #374151;
    }

    .user-badge--active {
        background: #dcfce7;
        color: #166534;
    }

    .user-badge--inactive {
        background: #fee2e2;
        color: #991b1b;
    }

    .user-access {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 10px;
        margin-top: 16px;
    }

    .user-access-item {
        min-width: 0;
        padding: 12px;
        border: 1px solid #e5e7eb;
        border-radius: 10px;
        background: #f9fafb;
    }

    .user-access-label {
        display: block;
        margin-bottom: 6px;
        color: #6b7280;
        font-size: 12px;
        font-weight: 800;
        text-transform: uppercase;
    }

    .user-access-value {
        color: #111827;
        font-size: 14px;
        font-weight: 800;
        overflow-wrap: anywhere;
    }

    .user-access-value--warning {
        color: #b45309;
    }

    .user-access-value--danger {
        color: #991b1b;
    }

    .user-actions {
        min-width: 190px;
        display: grid;
        gap: 10px;
        justify-items: stretch;
    }

    .user-actions form {
        margin: 0;
    }

    .user-actions .btn {
        width: 100%;
    }

    .btn-warning {
        background: #f59e0b;
        color: #ffffff;
    }

    .btn-success {
        background: #16a34a;
        color: #ffffff;
    }

    .extend-modal[hidden] {
        display: none;
    }

    .extend-modal {
        position: fixed;
        inset: 0;
        z-index: 110;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 18px;
    }

    .extend-modal-backdrop {
        position: absolute;
        inset: 0;
        background: rgba(17, 24, 39, 0.58);
    }

    .extend-modal-dialog {
        position: relative;
        width: min(100%, 460px);
        padding: 22px;
        border-radius: 14px;
        background: #ffffff;
        box-shadow: 0 24px 70px rgba(15, 23, 42, 0.28);
    }

    .extend-modal-dialog h2 {
        margin: 0 0 8px;
        color: #111827;
        font-size: 22px;
        line-height: 1.15;
    }

    .extend-modal-dialog p {
        margin: 0 0 16px;
        color: #4b5563;
        line-height: 1.55;
    }

    .extend-presets {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 8px;
        margin-bottom: 12px;
    }

    .extend-preset {
        min-height: 38px;
        border: 1px solid #d1d5db;
        border-radius: 8px;
        background: #ffffff;
        color: #111827;
        cursor: pointer;
        font-weight: 700;
    }

    .extend-summary {
        padding: 12px;
        border-radius: 10px;
        background: #f9fafb;
        color: #374151;
        font-size: 14px;
        line-height: 1.5;
    }

    .extend-modal-actions {
        display: flex;
        justify-content: flex-end;
        gap: 10px;
        margin-top: 18px;
    }

    @media (max-width: 720px) {
        .user-card {
            grid-template-columns: 1fr;
        }

        .user-card-header {
            display: grid;
        }

        .user-badges {
            justify-content: flex-start;
        }

        .user-access {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .user-actions {
            display: grid;
            min-width: 0;
        }

        .extend-presets {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .extend-modal-actions {
            flex-direction: column-reverse;
        }
    }

    @media (max-width: 480px) {
        .user-access {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="users-list">
    @foreach($users as $user)
        @php
            $roleLabel = $user->role === 'admin' ? 'Administrador' : 'Usuario de tienda';
            $isCurrentlyActive = $user->isActive();
            $remainingLabel = $user->active_remaining_label;
            $remainingClass = $remainingLabel === 'Vencida' ? 'user-access-value--danger' : ($remainingLabel === 'Vence hoy' ? 'user-access-value--warning' : '');
        @endphp

        <article class="list-card user-card">
            <div class="user-card-main">
                <div class="user-card-header">
                    <div class="user-identity">
                        <div class="user-avatar" aria-hidden="true">{{ \Illuminate\Support\Str::substr($user->name, 0, 1) }}</div>
                        <div>
                            <h3 class="user-name">{{ $user->name }}</h3>
                            <p class="user-email">{{ $user->email }}</p>
                        </div>
                    </div>

                    <div class="user-badges">
                        <span class="user-badge user-badge--role">{{ $roleLabel }}</span>
                        <span class="user-badge {{ $isCurrentlyActive ? 'user-badge--active' : 'user-badge--inactive' }}">
                            {{ $isCurrentlyActive ? 'Activa' : 'Inactiva' }}
                        </span>
                    </div>
                </div>

                @if($user->role === 'store')
                    <div class="user-access" aria-label="Acceso de la cuenta">
                        <div class="user-access-item">
                            <span class="user-access-label">Duracion</span>
                            <span class="user-access-value">{{ $user->active_duration_days ? $user->active_duration_days . ' dia(s)' : 'Sin limite' }}</span>
                        </div>
                        <div class="user-access-item">
                            <span class="user-access-label">Inicio</span>
                            <span class="user-access-value">{{ $user->active_starts_at ? $user->active_starts_at->format('d/m/Y') : 'Sin fecha' }}</span>
                        </div>
                        <div class="user-access-item">
                            <span class="user-access-label">Vence</span>
                            <span class="user-access-value">{{ $user->active_ends_at ? $user->active_ends_at->format('d/m/Y') : 'Sin fecha final' }}</span>
                        </div>
                        <div class="user-access-item">
                            <span class="user-access-label">Restante</span>
                            <span class="user-access-value {{ $remainingClass }}">{{ $remainingLabel }}</span>
                        </div>
                    </div>
                @endif
            </div>

            <div class="user-actions">
                <a href="{{ route('admin.users.edit', $user) }}" class="btn">Editar</a>

                @if($user->role === 'store')
                    <button
                        type="button"
                        class="btn btn-secondary"
                        data-extend-user
                        data-action="{{ route('admin.users.extend', $user) }}"
                        data-name="{{ $user->name }}"
                        data-current-end="{{ $user->active_ends_at ? $user->active_ends_at->format('d/m/Y') : 'Sin fecha final' }}"
                    >
                        Extender acceso
                    </button>

                    <form method="POST" action="{{ route('admin.users.toggle', $user) }}" onsubmit="return confirm('Deseas {{ $user->is_active ? 'pausar' : 'reactivar' }} esta cuenta?');">
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="btn {{ $user->is_active ? 'btn-warning' : 'btn-success' }}">
                            {{ $user->is_active ? 'Pausar cuenta' : 'Reactivar cuenta' }}
                        </button>
                    </form>

                    <form method="POST" action="{{ route('admin.users.destroy', $user) }}" data-confirm-delete data-confirm-message="Eliminar este usuario y su tienda? Esta accion no se puede deshacer.">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger">Eliminar</button>
                    </form>
                @endif
            </div>
        </article>
    @endforeach
</div>

<div class="extend-modal" data-extend-modal hidden>
    <div class="extend-modal-backdrop" data-extend-cancel></div>
    <form method="POST" action="#" class="extend-modal-dialog" data-extend-form>
        @csrf
        @method('PATCH')
        <h2>Extender acceso</h2>
        <p>
            <span data-extend-user-name>Usuario</span><br>
            Vence actualmente: <strong data-extend-current-end>Sin fecha final</strong>
        </p>

        <label class="field-label" for="extend_days">Dias a agregar</label>
        <div class="extend-presets" aria-label="Dias rapidos">
            <button type="button" class="extend-preset" data-extend-days="7">+7</button>
            <button type="button" class="extend-preset" data-extend-days="15">+15</button>
            <button type="button" class="extend-preset" data-extend-days="30">+30</button>
            <button type="button" class="extend-preset" data-extend-days="90">+90</button>
        </div>
        <input type="number" id="extend_days" name="extend_days" min="1" max="3650" value="30" required data-extend-input>
        <div class="extend-summary">
            Si la cuenta sigue activa, los dias se suman desde su fecha final actual. Si ya vencio, se suman desde hoy.
        </div>

        <div class="extend-modal-actions">
            <button type="button" class="btn btn-secondary" data-extend-cancel>Cancelar</button>
            <button type="submit" class="btn">Extender acceso</button>
        </div>
    </form>
</div>

@push('scripts')
<script>
    (() => {
        const modal = document.querySelector('[data-extend-modal]');
        const form = document.querySelector('[data-extend-form]');
        const input = document.querySelector('[data-extend-input]');
        const nameTarget = document.querySelector('[data-extend-user-name]');
        const currentEndTarget = document.querySelector('[data-extend-current-end]');
        const openButtons = document.querySelectorAll('[data-extend-user]');
        const cancelButtons = document.querySelectorAll('[data-extend-cancel]');
        const presetButtons = document.querySelectorAll('[data-extend-days]');

        if (!modal || !form || !input) {
            return;
        }

        const closeModal = () => {
            modal.hidden = true;
        };

        openButtons.forEach((button) => {
            button.addEventListener('click', () => {
                form.action = button.dataset.action || '#';
                input.value = '30';

                if (nameTarget) {
                    nameTarget.textContent = button.dataset.name || 'Usuario';
                }

                if (currentEndTarget) {
                    currentEndTarget.textContent = button.dataset.currentEnd || 'Sin fecha final';
                }

                modal.hidden = false;
                input.focus();
                input.select();
            });
        });

        presetButtons.forEach((button) => {
            button.addEventListener('click', () => {
                input.value = button.dataset.extendDays || '30';
                input.focus();
            });
        });

        cancelButtons.forEach((button) => {
            button.addEventListener('click', closeModal);
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && !modal.hidden) {
                closeModal();
            }
        });
    })();
</script>
@endpush
@endsection
