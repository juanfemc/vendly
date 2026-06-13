<?php

namespace App\Http\Controllers;

use App\Models\WhatsAppConversation;
use App\Services\WhatsAppInboxService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Throwable;

class WhatsAppInboxController extends Controller
{
    public function __construct(private WhatsAppInboxService $inbox)
    {
    }

    public function index(Request $request): View
    {
        $user = $request->user();
        $this->inbox->syncRecentTemplateMessages(
            $user->isAdmin() ? null : $user->stores()->pluck('id')->all(),
        );

        $conversationsQuery = $this->visibleConversations($request)
            ->with('store')
            ->withMax('messages', 'created_at')
            ->orderByDesc('last_message_at')
            ->orderByDesc('id');

        $conversations = $conversationsQuery->paginate(15)->withQueryString();
        $selectedConversation = $this->selectedConversation($request);

        if ($selectedConversation) {
            $selectedConversation->load([
                'store',
                'messages' => fn ($query) => $query->with('sender')->oldest()->take(80),
            ]);

            if ($request->filled('conversation')) {
                $this->inbox->markConversationRead($selectedConversation);
            }
        }

        return view('admin.whatsapp.index', [
            'conversations' => $conversations,
            'selectedConversation' => $selectedConversation,
            'canManageAll' => $user->isAdmin(),
        ]);
    }

    public function send(Request $request, WhatsAppConversation $conversation): RedirectResponse
    {
        $this->authorizeConversation($request, $conversation);

        $validated = $request->validate([
            'body' => ['required', 'string', 'max:4096'],
        ]);

        try {
            $this->inbox->sendReply($conversation, $request->user(), $validated['body']);
        } catch (Throwable $exception) {
            return redirect()
                ->route('admin.whatsapp.index', ['conversation' => $conversation->id])
                ->with('error', $exception->getMessage());
        }

        return redirect()
            ->route('admin.whatsapp.index', ['conversation' => $conversation->id])
            ->with('success', 'Mensaje enviado por WhatsApp.');
    }

    private function selectedConversation(Request $request): ?WhatsAppConversation
    {
        $conversationId = $request->integer('conversation');

        if (! $conversationId) {
            return null;
        }

        $conversation = WhatsAppConversation::findOrFail($conversationId);
        $this->authorizeConversation($request, $conversation);

        return $conversation;
    }

    private function visibleConversations(Request $request)
    {
        $query = WhatsAppConversation::query();

        if (! $request->user()->isAdmin()) {
            $storeIds = $request->user()->stores()->pluck('id');
            $query->whereIn('store_id', $storeIds);
        }

        return $query;
    }

    private function authorizeConversation(Request $request, WhatsAppConversation $conversation): void
    {
        if ($request->user()->isAdmin()) {
            return;
        }

        $allowed = $request->user()
            ->stores()
            ->whereKey($conversation->store_id)
            ->exists();

        abort_unless($allowed, 403);
    }
}
