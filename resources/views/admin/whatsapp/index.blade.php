@extends('layouts.admin')

@section('content')
<style>
    .whatsapp-inbox {
        display: grid;
        grid-template-columns: minmax(260px, 360px) minmax(0, 1fr);
        gap: 18px;
        align-items: start;
    }

    .whatsapp-conversation-list,
    .whatsapp-chat-panel {
        min-height: 560px;
    }

    .whatsapp-conversation-list {
        display: grid;
        gap: 10px;
        align-content: start;
    }

    .whatsapp-conversation-link {
        display: block;
        text-decoration: none;
        color: inherit;
        border: 1px solid #e5e7eb;
        border-radius: 14px;
        padding: 14px;
        background: #fff;
        transition: border-color .18s ease, box-shadow .18s ease, transform .18s ease;
    }

    .whatsapp-conversation-link:hover,
    .whatsapp-conversation-link.is-active {
        border-color: #111827;
        box-shadow: 0 14px 35px rgba(15, 23, 42, .08);
        transform: translateY(-1px);
    }

    .whatsapp-conversation-top,
    .whatsapp-chat-head {
        display: flex;
        justify-content: space-between;
        gap: 12px;
        align-items: flex-start;
    }

    .whatsapp-contact-name {
        margin: 0;
        font-size: 15px;
        font-weight: 800;
    }

    .whatsapp-contact-meta,
    .whatsapp-message-meta,
    .whatsapp-window-note {
        margin: 4px 0 0;
        color: #6b7280;
        font-size: 12px;
        line-height: 1.45;
    }

    .whatsapp-unread {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 22px;
        height: 22px;
        padding: 0 7px;
        border-radius: 999px;
        background: #111827;
        color: #fff;
        font-size: 11px;
        font-weight: 800;
    }

    .whatsapp-chat-panel {
        display: flex;
        flex-direction: column;
        overflow: hidden;
    }

    .whatsapp-chat-head {
        padding-bottom: 16px;
        border-bottom: 1px solid #e5e7eb;
        margin-bottom: 16px;
    }

    .whatsapp-message-list {
        display: flex;
        flex-direction: column;
        gap: 10px;
        overflow-y: auto;
        padding: 4px 4px 18px;
        min-height: 320px;
        max-height: 520px;
    }

    .whatsapp-message {
        max-width: min(78%, 620px);
        border: 1px solid #e5e7eb;
        border-radius: 16px;
        padding: 11px 13px;
        background: #fff;
    }

    .whatsapp-message.is-outgoing {
        align-self: flex-end;
        background: #111827;
        color: #fff;
        border-color: #111827;
    }

    .whatsapp-message.is-outgoing .whatsapp-message-meta {
        color: rgba(255, 255, 255, .72);
    }

    .whatsapp-message-body {
        margin: 0;
        white-space: pre-wrap;
        line-height: 1.5;
        font-size: 14px;
    }

    .whatsapp-reply-form {
        border-top: 1px solid #e5e7eb;
        padding-top: 16px;
        margin-top: auto;
    }

    .whatsapp-reply-actions {
        display: flex;
        justify-content: space-between;
        gap: 12px;
        align-items: center;
        margin-top: 10px;
    }

    .whatsapp-window-badge {
        display: inline-flex;
        align-items: center;
        border-radius: 999px;
        padding: 7px 10px;
        background: #ecfdf5;
        color: #047857;
        font-size: 12px;
        font-weight: 800;
    }

    .whatsapp-window-badge.is-closed {
        background: #fff7ed;
        color: #c2410c;
    }

    @media (max-width: 900px) {
        .whatsapp-inbox {
            grid-template-columns: 1fr;
        }

        .whatsapp-conversation-list,
        .whatsapp-chat-panel {
            min-height: auto;
        }

        .whatsapp-message {
            max-width: 92%;
        }
    }
</style>

<div class="header">
    <div>
        <h2>WhatsApp</h2>
        <p style="margin:6px 0 0;color:#6b7280;">Recibe, revisa y responde mensajes de clientes desde Vendly.</p>
    </div>
</div>

@if (session('success'))
    <div class="flash success">{{ session('success') }}</div>
@endif

@if (session('error'))
    <div class="flash error">{{ session('error') }}</div>
@endif

@if ($errors->any())
    <div class="flash error">{{ $errors->first() }}</div>
@endif

@if ($conversations->isEmpty())
    <div class="panel-empty">
        <h3>No hay conversaciones todavia</h3>
        <p>Cuando un cliente responda un mensaje de WhatsApp enviado desde Vendly, aparecera aqui.</p>
    </div>
@else
    <div class="whatsapp-inbox">
        <aside class="whatsapp-conversation-list">
            @foreach ($conversations as $conversation)
                @php
                    $isActive = $selectedConversation?->id === $conversation->id;
                    $displayName = $conversation->contact_name ?: 'Cliente WhatsApp';
                @endphp
                <a
                    href="{{ route('admin.whatsapp.index', ['conversation' => $conversation->id]) }}"
                    class="whatsapp-conversation-link {{ $isActive ? 'is-active' : '' }}"
                >
                    <div class="whatsapp-conversation-top">
                        <div>
                            <p class="whatsapp-contact-name">{{ $displayName }}</p>
                            <p class="whatsapp-contact-meta">
                                {{ $conversation->contact_phone }}
                                @if ($canManageAll)
                                    <br>{{ $conversation->store?->name ?? 'Sin tienda asociada' }}
                                @endif
                            </p>
                        </div>
                        @if ($conversation->unread_count > 0)
                            <span class="whatsapp-unread">{{ $conversation->unread_count }}</span>
                        @endif
                    </div>
                    <p class="whatsapp-contact-meta">
                        Ultimo mensaje:
                        {{ $conversation->last_message_at?->diffForHumans() ?? 'sin fecha' }}
                    </p>
                </a>
            @endforeach

            @if ($conversations->hasPages())
                <div class="list-card admin-pagination">
                    {{ $conversations->links('pagination::bootstrap-4') }}
                </div>
            @endif
        </aside>

        <section class="list-card whatsapp-chat-panel">
            @if ($selectedConversation)
                <div class="whatsapp-chat-head">
                    <div>
                        <h3 class="resource-card__title">{{ $selectedConversation->contact_name ?: 'Cliente WhatsApp' }}</h3>
                        <p class="resource-card__subtitle">
                            {{ $selectedConversation->contact_phone }}
                            @if ($canManageAll)
                                / {{ $selectedConversation->store?->name ?? 'Sin tienda asociada' }}
                            @endif
                        </p>
                    </div>
                    <span class="whatsapp-window-badge {{ $selectedConversation->canSendFreeText() ? '' : 'is-closed' }}">
                        {{ $selectedConversation->canSendFreeText() ? 'Ventana 24h activa' : 'Usar plantilla' }}
                    </span>
                </div>

                <div class="whatsapp-message-list">
                    @foreach ($selectedConversation->messages as $message)
                        <article class="whatsapp-message {{ $message->direction === 'outgoing' ? 'is-outgoing' : '' }}">
                            <p class="whatsapp-message-body">{{ $message->body ?: '[' . $message->message_type . ']' }}</p>
                            <p class="whatsapp-message-meta">
                                {{ $message->direction === 'outgoing' ? 'Vendly' : 'Cliente' }}
                                @if ($message->direction === 'outgoing' && $message->sender)
                                    / {{ $message->sender->name }}
                                @endif
                                / {{ $message->created_at?->format('d/m/Y H:i') }}
                                / {{ $message->status }}
                            </p>
                        </article>
                    @endforeach
                </div>

                <form method="POST" action="{{ route('admin.whatsapp.send', $selectedConversation) }}" class="whatsapp-reply-form">
                    @csrf
                    <label class="field-label" for="whatsappReply">Responder</label>
                    <textarea
                        id="whatsappReply"
                        name="body"
                        rows="4"
                        maxlength="4096"
                        placeholder="{{ $selectedConversation->canSendFreeText() ? 'Escribe tu respuesta para WhatsApp' : 'La ventana de 24 horas cerro. Usa una plantilla aprobada para iniciar otra conversacion.' }}"
                        {{ $selectedConversation->canSendFreeText() ? '' : 'disabled' }}
                    >{{ old('body') }}</textarea>

                    <div class="whatsapp-reply-actions">
                        <p class="whatsapp-window-note">
                            @if ($selectedConversation->canSendFreeText())
                                Puedes responder con texto libre porque el cliente escribio en las ultimas 24 horas.
                            @else
                                WhatsApp no permite texto libre fuera de 24 horas desde el ultimo mensaje del cliente.
                            @endif
                        </p>
                        <button class="btn" type="submit" {{ $selectedConversation->canSendFreeText() ? '' : 'disabled' }}>
                            Enviar
                        </button>
                    </div>
                </form>
            @else
                <div class="panel-empty">
                    <h3>Selecciona una conversacion</h3>
                    <p>El historial y la respuesta apareceran aqui.</p>
                </div>
            @endif
        </section>
    </div>
@endif
@endsection
