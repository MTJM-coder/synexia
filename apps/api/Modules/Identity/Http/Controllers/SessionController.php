<?php

namespace Modules\Identity\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Identity\Models\LoginHistory;

class SessionController extends Controller
{
    /**
     * Liste les tokens Sanctum actifs de l'utilisateur connecté — chacun
     * représente un appareil/session connecté.
     */
    public function index(Request $request): JsonResponse
    {
        $currentTokenId = $request->user()->currentAccessToken()?->id;

        $sessions = $request->user()->tokens()
            ->orderByDesc('last_used_at')
            ->get()
            ->map(fn ($token) => [
                'id' => $token->id,
                'name' => $token->name, // device_name fourni au login
                'is_current' => $token->id === $currentTokenId,
                'last_used_at' => $token->last_used_at?->toIso8601String(),
                'created_at' => $token->created_at?->toIso8601String(),
            ]);

        return response()->json(['data' => $sessions]);
    }

    /**
     * Révoque un token précis (déconnecte un appareil spécifique) — pas
     * nécessairement celui utilisé pour cette requête.
     */
    public function destroy(Request $request, int $tokenId): JsonResponse
    {
        $deleted = $request->user()->tokens()->where('id', $tokenId)->delete();

        abort_unless($deleted > 0, 404, 'Session introuvable.');

        return response()->json(['message' => 'Session déconnectée.']);
    }

    /**
     * Révoque toutes les sessions SAUF celle en cours.
     */
    public function destroyOthers(Request $request): JsonResponse
    {
        $currentTokenId = $request->user()->currentAccessToken()?->id;

        $request->user()->tokens()
            ->when($currentTokenId, fn ($q) => $q->where('id', '!=', $currentTokenId))
            ->delete();

        return response()->json(['message' => 'Toutes les autres sessions ont été déconnectées.']);
    }

    /**
     * Historique des tentatives de connexion (succès ET échecs) — utile pour
     * qu'un utilisateur détecte une tentative d'accès suspecte.
     */
    public function loginHistory(Request $request): JsonResponse
    {
        $history = LoginHistory::where('user_id', $request->user()->id)
            ->orderByDesc('created_at')
            ->paginate(20);

        return response()->json([
            'data' => $history->through(fn (LoginHistory $entry) => [
                'ip_address' => $entry->ip_address,
                'user_agent' => $entry->user_agent,
                'status' => $entry->status,
                'created_at' => $entry->created_at?->toIso8601String(),
            ]),
        ]);
    }
}
