<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/**
 * Controller API pour la gestion des notifications
 *
 * Fournit les endpoints REST pour le frontend React
 */
class NotificationController extends Controller
{
    /**
     * Créer une nouvelle instance du controller
     *
     * @param NotificationService $notificationService
     */
    public function __construct(
        protected NotificationService $notificationService
    ) {
    }

    /**
     * Liste des notifications avec pagination et filtres
     *
     * GET /api/notifications
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'status' => ['sometimes', 'string', Rule::in([
                Notification::STATUS_DRAFT,
                Notification::STATUS_SCHEDULED,
                Notification::STATUS_SENT,
                Notification::STATUS_FAILED,
            ])],
            'type' => ['sometimes', 'string', Rule::in([
                Notification::TYPE_PAYMENT_REMINDER,
                Notification::TYPE_URGENT_INFO,
                Notification::TYPE_GENERAL,
            ])],
            'recipient_email' => ['sometimes', 'email'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'page' => ['sometimes', 'integer', 'min:1'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $query = Notification::with(['template', 'logs'])
            ->orderBy('created_at', 'desc');

        // Filtres
        if ($request->has('status')) {
            $query->ofStatus($request->status);
        }

        if ($request->has('type')) {
            $query->ofType($request->type);
        }

        if ($request->has('recipient_email')) {
            $query->where('recipient_email', $request->recipient_email);
        }

        $perPage = $request->get('per_page', 15);
        $notifications = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $notifications->items(),
            'pagination' => [
                'current_page' => $notifications->currentPage(),
                'last_page' => $notifications->lastPage(),
                'per_page' => $notifications->perPage(),
                'total' => $notifications->total(),
            ],
        ]);
    }

    /**
     * Créer une nouvelle notification
     *
     * POST /api/notifications
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'type' => ['required', 'string', Rule::in([
                Notification::TYPE_PAYMENT_REMINDER,
                Notification::TYPE_URGENT_INFO,
                Notification::TYPE_GENERAL,
            ])],
            'recipient_email' => ['required', 'email'],
            'recipient_name' => ['required', 'string', 'max:255'],
            'data' => ['sometimes', 'array'],
            'scheduled_at' => ['sometimes', 'date', 'after:now'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $data = $request->get('data', []);
            $scheduledAt = $request->has('scheduled_at')
                ? \Carbon\Carbon::parse($request->scheduled_at)
                : null;

            $notification = match ($request->type) {
                Notification::TYPE_PAYMENT_REMINDER => $this->notificationService->sendPaymentReminder(
                    $request->recipient_email,
                    $request->recipient_name,
                    $data,
                    $scheduledAt
                ),
                Notification::TYPE_URGENT_INFO => $this->notificationService->sendUrgentNotification(
                    $request->recipient_email,
                    $request->recipient_name,
                    $data,
                    $scheduledAt
                ),
                Notification::TYPE_GENERAL => $this->notificationService->sendGeneralNotification(
                    $request->recipient_email,
                    $request->recipient_name,
                    $data,
                    $scheduledAt
                ),
            };

            return response()->json([
                'success' => true,
                'message' => 'Notification créée avec succès',
                'data' => $notification->load(['template', 'logs']),
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création de la notification',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Afficher une notification spécifique
     *
     * GET /api/notifications/{id}
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        $notification = Notification::with(['template', 'logs'])
            ->find($id);

        if (!$notification) {
            return response()->json([
                'success' => false,
                'message' => 'Notification non trouvée',
            ], 404);
        }

        $stats = $this->notificationService->getNotificationStats($notification);

        return response()->json([
            'success' => true,
            'data' => $notification,
            'stats' => $stats,
        ]);
    }

    /**
     * Mettre à jour une notification (draft uniquement)
     *
     * PUT /api/notifications/{id}
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $notification = Notification::find($id);

        if (!$notification) {
            return response()->json([
                'success' => false,
                'message' => 'Notification non trouvée',
            ], 404);
        }

        if ($notification->status !== Notification::STATUS_DRAFT) {
            return response()->json([
                'success' => false,
                'message' => 'Seules les notifications en brouillon peuvent être modifiées',
            ], 422);
        }

        $validator = Validator::make($request->all(), [
            'recipient_email' => ['sometimes', 'email'],
            'recipient_name' => ['sometimes', 'string', 'max:255'],
            'subject' => ['sometimes', 'string', 'max:255'],
            'body' => ['sometimes', 'string'],
            'scheduled_at' => ['sometimes', 'date', 'after:now'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $notification->fill($request->only([
            'recipient_email',
            'recipient_name',
            'subject',
            'body',
            'scheduled_at',
        ]));

        // Mettre à jour le statut si une date est programmée
        if ($request->has('scheduled_at')) {
            $notification->status = Notification::STATUS_SCHEDULED;
        }

        $notification->save();

        return response()->json([
            'success' => true,
            'message' => 'Notification mise à jour avec succès',
            'data' => $notification->load(['template', 'logs']),
        ]);
    }

    /**
     * Supprimer une notification
     *
     * DELETE /api/notifications/{id}
     *
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(int $id): JsonResponse
    {
        $notification = Notification::find($id);

        if (!$notification) {
            return response()->json([
                'success' => false,
                'message' => 'Notification non trouvée',
            ], 404);
        }

        // Seules les notifications en brouillon ou échouées peuvent être supprimées
        if (!in_array($notification->status, [
            Notification::STATUS_DRAFT,
            Notification::STATUS_FAILED,
        ])) {
            return response()->json([
                'success' => false,
                'message' => 'Seules les notifications en brouillon ou échouées peuvent être supprimées',
            ], 422);
        }

        $notification->delete();

        return response()->json([
            'success' => true,
            'message' => 'Notification supprimée avec succès',
        ]);
    }

    /**
     * Obtenir les statistiques d'une notification
     *
     * GET /api/notifications/{id}/stats
     *
     * @param int $id
     * @return JsonResponse
     */
    public function stats(int $id): JsonResponse
    {
        $notification = Notification::find($id);

        if (!$notification) {
            return response()->json([
                'success' => false,
                'message' => 'Notification non trouvée',
            ], 404);
        }

        $stats = $this->notificationService->getNotificationStats($notification);

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    /**
     * Relancer l'envoi d'une notification échouée
     *
     * POST /api/notifications/{id}/retry
     *
     * @param int $id
     * @return JsonResponse
     */
    public function retry(int $id): JsonResponse
    {
        $notification = Notification::find($id);

        if (!$notification) {
            return response()->json([
                'success' => false,
                'message' => 'Notification non trouvée',
            ], 404);
        }

        if ($notification->status !== Notification::STATUS_FAILED) {
            return response()->json([
                'success' => false,
                'message' => 'Seules les notifications échouées peuvent être relancées',
            ], 422);
        }

        // Réinitialiser le statut et relancer
        $notification->update([
            'status' => Notification::STATUS_SCHEDULED,
            'error_message' => null,
        ]);

        $this->notificationService->dispatchNotification($notification);

        return response()->json([
            'success' => true,
            'message' => 'Notification relancée avec succès',
            'data' => $notification->load(['template', 'logs']),
        ]);
    }
}

