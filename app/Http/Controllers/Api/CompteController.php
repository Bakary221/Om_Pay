<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\TransactionService;
use App\Repositories\Interfaces\CompteRepositoryInterface;
use App\Http\Requests\DepotRequest;
use App\Traits\ApiResponseTrait;
use App\Http\Resources\CompteResource;
use App\Models\Compte;
use Illuminate\Http\JsonResponse;

/**
 * @OA\Tag(
 *     name="Comptes",
 *     description="Gestion des comptes utilisateur"
 * )
 */
class CompteController extends Controller
{
    use ApiResponseTrait;

    private CompteRepositoryInterface $compteRepository;
    private TransactionService $transactionService;

    public function __construct(
        CompteRepositoryInterface $compteRepository,
        TransactionService $transactionService
    ) {
        $this->compteRepository = $compteRepository;
        $this->transactionService = $transactionService;
    }

    // /**
    //  * @OA\Get(
    //  *     path="/compte/{compte}",
    //  *     summary="Informations du compte utilisateur",
    //  *     description="Retourne les informations du compte spécifié pour l'utilisateur connecté",
    //  *     operationId="getCompte",
    //  *     tags={"Comptes"},
    //  *     security={{"bearerAuth":{}}},
    //  *     @OA\Parameter(
    //  *         name="compte",
    //  *         in="path",
    //  *         required=true,
    //  *         description="ID du compte (UUID)",
    //  *         @OA\Schema(type="string", format="uuid", example="a054a43b-8206-4efe-914b-c1f96457d58e")
    //  *     ),
    //  *     @OA\Response(
    //  *         response=200,
    //  *         description="Informations du compte",
    //  *         @OA\JsonContent(
    //  *             @OA\Property(property="success", type="boolean", example=true),
    //  *             @OA\Property(property="data", type="object",
    //  *                 @OA\Property(property="compte", type="object",
    //  *                     @OA\Property(property="numero_compte", type="string", example="OM-2025-AB12-CD34"),
    //  *                     @OA\Property(property="solde", type="number", format="float", example=15000.50),
    //  *                     @OA\Property(property="qr_code_data", type="string", example="QR code data"),
    //  *                     @OA\Property(property="created_at", type="string", format="date-time", example="2025-11-10T09:00:00Z")
    //  *                 )
    //  *             )
    //  *         )
    //  *     ),
    //  *     @OA\Response(
    //  *         response=403,
    //  *         description="Accès non autorisé à ce compte"
    //  *     ),
    //  *     @OA\Response(
    //  *         response=404,
    //  *         description="Compte non trouvé"
    //  *     )
    //  * )
    //  */
    public function show(Compte $compte): JsonResponse
    {
        try {
            // Vérifier que le compte appartient à l'utilisateur authentifié
            if ($compte->user_id !== auth()->id()) {
                return $this->errorResponse('Accès non autorisé à ce compte', 403);
            }

            return $this->successResponse([
                'compte' => new CompteResource($compte)
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse('Erreur lors de la récupération du compte', 500, $e->getMessage());
        }
    }

    /**
     * @OA\Get(
     *     path="/compte/{compte}/solde",
     *     summary="Consulter le solde du compte",
     *     description="Retourne le solde actuel du compte spécifié",
     *     operationId="getSolde",
     *     tags={"Comptes"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="compte",
     *         in="path",
     *         required=true,
     *         description="ID du compte (UUID)",
     *         @OA\Schema(type="string", format="uuid", example="a054a43b-8206-4efe-914b-c1f96457d58e")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Solde du compte",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="solde", type="number", format="float", example=15000.50),
     *                 @OA\Property(property="numero_compte", type="string", example="OM-2025-AB12-CD34"),
     *                 @OA\Property(property="devise", type="string", example="FCFA")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Accès non autorisé à ce compte"
     *     )
     * )
     */
    public function solde(Compte $compte): JsonResponse
    {
        try {
            // Vérifier que le compte appartient à l'utilisateur authentifié
            if ($compte->user_id !== auth()->id()) {
                return $this->errorResponse('Accès non autorisé à ce compte', 403);
            }

            return $this->successResponse([
                'solde' => $compte->solde,
                'numero_compte' => $compte->numero_compte,
                'devise' => 'FCFA'
            ], 'Opération réussie');
        } catch (\Exception $e) {
            return $this->errorResponse('Erreur lors de la récupération du solde', 500, $e->getMessage());
        }
    }

    /**
     * Consulter le solde du compte
     */


    /**
     * @OA\Post(
     *     path="/compte/{compte}/depot",
     *     summary="Effectuer un dépôt d'argent",
     *     description="Crédite le compte spécifié avec le montant indiqué",
     *     operationId="depot",
     *     tags={"Comptes"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="compte",
     *         in="path",
     *         required=true,
     *         description="ID du compte (UUID)",
     *         @OA\Schema(type="string", format="uuid", example="a054a43b-8206-4efe-914b-c1f96457d58e")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"montant"},
     *             @OA\Property(property="montant", type="number", format="float", example=5000.00, description="Montant à déposer (min: 100, max: 1 000 000)")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Dépôt effectué avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Dépôt effectué avec succès"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="transaction", type="object",
     *                     @OA\Property(property="reference", type="string", example="TXN-20251110-ABC123"),
     *                     @OA\Property(property="type", type="string", example="depot"),
     *                     @OA\Property(property="montant", type="number", format="float", example=5000.00),
     *                     @OA\Property(property="statut", type="string", example="reussi"),
     *                     @OA\Property(property="compte_destinataire", type="object",
     *                         @OA\Property(property="numero_compte", type="string", example="OM-2025-AB12-CD34")
     *                     )
     *                 ),
     *                 @OA\Property(property="nouveau_solde", type="number", format="float", example=20000.50)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Montant invalide ou erreur de traitement"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Accès non autorisé à ce compte"
     *     )
     * )
     */
    public function depot(Compte $compte, DepotRequest $request): JsonResponse
    {
        try {
            // Vérifier que le compte appartient à l'utilisateur authentifié
            if ($compte->user_id !== auth()->id()) {
                return $this->errorResponse('Accès non autorisé à ce compte', 403);
            }

            $transaction = $this->transactionService->effectuerDepot(
                $compte,
                $request->input('montant')
            );

            return $this->successResponse([
                'transaction' => $transaction->load('compteDestinataire'),
                'nouveau_solde' => $compte->fresh()->solde,
            ], 'Dépôt effectué avec succès');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    /**
     * @OA\Get(
     *     path="/compte/{compte}/qrcode",
     *     summary="Récupérer l'image QR code du compte",
     *     description="Retourne l'URL de l'image QR code du compte pour faciliter les paiements",
     *     operationId="getQrCode",
     *     tags={"Comptes"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="compte",
     *         in="path",
     *         required=true,
     *         description="ID du compte (UUID)",
     *         @OA\Schema(type="string", format="uuid", example="a054a43b-8206-4efe-914b-c1f96457d58e")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="URL du QR code",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="qr_code_url", type="string", format="uri", example="https://ompay.onrender.com/storage/qrcodes/qr_OM-2025-AB12-CD34_abc123.png"),
     *                 @OA\Property(property="numero_compte", type="string", example="OM-2025-AB12-CD34")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Accès non autorisé à ce compte"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="QR code non trouvé"
     *     )
     * )
     */
    public function qrCode(Compte $compte): JsonResponse
    {
        try {
            // Vérifier que le compte appartient à l'utilisateur authentifié
            if ($compte->user_id !== auth()->id()) {
                return $this->errorResponse('Accès non autorisé à ce compte', 403);
            }

            // Vérifier si le QR code existe
            if (!$compte->hasQrCodeUrl()) {
                return $this->errorResponse('QR code non trouvé pour ce compte', 404);
            }

            return $this->successResponse([
                'qr_code_url' => $compte->qr_code_url,
                'numero_compte' => $compte->numero_compte
            ], 'QR code récupéré avec succès');
        } catch (\Exception $e) {
            return $this->errorResponse('Erreur lors de la récupération du QR code', 500, $e->getMessage());
        }
    }
}