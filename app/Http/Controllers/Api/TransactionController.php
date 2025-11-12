<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\TransactionService;
use App\Repositories\Interfaces\TransactionRepositoryInterface;
use App\Repositories\Interfaces\CompteRepositoryInterface;
use App\Http\Requests\PaiementRequest;
use App\Http\Requests\TransfertRequest;
use App\Traits\ApiResponseTrait;
use App\Http\Resources\TransactionResource;
use Illuminate\Http\JsonResponse;

/**
 * @OA\Tag(
 *     name="Transactions",
 *     description="Gestion des transactions (paiements, transferts)"
 * )
 */
class TransactionController extends Controller
{
    use ApiResponseTrait;

    private TransactionService $transactionService;
    private TransactionRepositoryInterface $transactionRepository;
    private CompteRepositoryInterface $compteRepository;

    public function __construct(
        TransactionService $transactionService,
        TransactionRepositoryInterface $transactionRepository,
        CompteRepositoryInterface $compteRepository
    ) {
        $this->transactionService = $transactionService;
        $this->transactionRepository = $transactionRepository;
        $this->compteRepository = $compteRepository;
    }

    /**
     * @OA\Post(
     *     path="/transactions/paiement",
     *     summary="Effectuer un paiement",
     *     description="Effectue un paiement vers un marchand (code marchand) ou vers un client (numéro de téléphone)",
     *     operationId="paiement",
     *     tags={"Transactions"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"destinataire", "montant"},
     *             @OA\Property(property="destinataire", type="string", example="MARCHAND001", description="Code marchand ou numéro de téléphone du destinataire"),
     *             @OA\Property(property="montant", type="number", format="float", example=2500.00, description="Montant du paiement")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Paiement effectué avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Paiement effectué avec succès"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="transaction", type="object",
     *                     @OA\Property(property="reference", type="string", example="TXN-20251110-ABC123"),
     *                     @OA\Property(property="type", type="string", example="paiement"),
     *                     @OA\Property(property="montant", type="number", format="float", example=2500.00),
     *                     @OA\Property(property="frais", type="number", format="float", example=0),
     *                     @OA\Property(property="statut", type="string", example="reussi"),
     *                     @OA\Property(property="compteEmetteur", type="object",
     *                         @OA\Property(property="numero_compte", type="string", example="OM-2025-AB12-CD34")
     *                     ),
     *                     @OA\Property(property="marchand", type="object",
     *                         @OA\Property(property="raison_sociale", type="string", example="Boutique Express"),
     *                         @OA\Property(property="code_marchand", type="string", example="MARCHAND001")
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Erreur de traitement (solde insuffisant, montant invalide, etc.)"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Marchand ou compte non trouvé"
     *     )
     * )
     */
    public function paiement(PaiementRequest $request): JsonResponse
    {
        try {
            $user = auth()->user();
            $compte = $this->compteRepository->findByUser($user);

            if (!$compte) {
                return $this->errorResponse('Aucun compte trouvé', 404);
            }

            $transaction = $this->transactionService->effectuerPaiement(
                $compte,
                $request->input('destinataire'),
                $request->input('montant')
            );

            // Charger les relations appropriées selon le type de paiement
            if ($transaction->marchand_id) {
                $transaction->load(['compteEmetteur', 'marchand']);
            } else {
                $transaction->load(['compteEmetteur.user', 'compteDestinataire.user']);
            }

            return $this->successResponse([
                'transaction' => new TransactionResource($transaction)
            ], 'Paiement effectué avec succès');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    /**
     * @OA\Post(
     *     path="/transactions/transfert",
     *     summary="Effectuer un transfert P2P",
     *     description="Transfère de l'argent vers un autre compte utilisateur",
     *     operationId="transfert",
     *     tags={"Transactions"},
     *     security={{"bearerAuth":{}}},
     * @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"numero_destinataire", "montant"},
     *             @OA\Property(property="numero_destinataire", type="string", example="781562041", description="Numéro de téléphone du destinataire"),
     *             @OA\Property(property="montant", type="number", format="float", example=10000.00, description="Montant du transfert")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Transfert effectué avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Transfert effectué avec succès"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="transaction", type="object",
     *                     @OA\Property(property="reference", type="string", example="TXN-20251110-DEF456"),
     *                     @OA\Property(property="type", type="string", example="transfert"),
     *                     @OA\Property(property="montant", type="number", format="float", example=10000.00),
     *                     @OA\Property(property="frais", type="number", format="float", example=100.00),
     *                     @OA\Property(property="statut", type="string", example="reussi"),
     *                     @OA\Property(property="compteEmetteur", type="object",
     *                         @OA\Property(property="user", type="object",
     *                             @OA\Property(property="nom", type="string", example="Diop"),
     *                             @OA\Property(property="prenom", type="string", example="Amadou")
     *                         )
     *                     ),
     *                     @OA\Property(property="compteDestinataire", type="object",
     *                         @OA\Property(property="user", type="object",
     *                             @OA\Property(property="nom", type="string", example="Sarr"),
     *                             @OA\Property(property="prenom", type="string", example="Fatou")
     *                         )
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Erreur de traitement (solde insuffisant, montant invalide, etc.)"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Destinataire non trouvé"
     *     )
     * )
     */
    public function transfert(TransfertRequest $request): JsonResponse
    {
        try {
            $user = auth()->user();
            $compte = $this->compteRepository->findByUser($user);

            if (!$compte) {
                return $this->errorResponse('Aucun compte trouvé', 404);
            }

            $transaction = $this->transactionService->effectuerTransfert(
                $compte,
                $request->input('numero_destinataire'),
                $request->input('montant')
            );

            return $this->successResponse([
                'transaction' => new TransactionResource($transaction->load(['compteEmetteur.user', 'compteDestinataire.user']))
            ], 'Transfert effectué avec succès');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    /**
     * @OA\Get(
     *     path="/transactions",
     *     summary="Liste des transactions de l'utilisateur",
     *     description="Retourne la liste paginée des transactions de l'utilisateur connecté (triées par date décroissante)",
     *     operationId="getTransactions",
     *     tags={"Transactions"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         required=false,
     *         description="Numéro de la page",
     *         @OA\Schema(type="integer", default=1)
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         required=false,
     *         description="Nombre d'éléments par page",
     *         @OA\Schema(type="integer", default=15, minimum=1, maximum=100)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Liste des transactions paginée",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="transactions", type="array",
     *                     @OA\Items(type="object",
     *                         @OA\Property(property="reference", type="string", example="TXN-20251110-ABC123"),
     *                         @OA\Property(property="type", type="string", enum={"paiement", "transfert", "depot"}, example="transfert"),
     *                         @OA\Property(property="montant", type="number", format="float", example=5000.00),
     *                         @OA\Property(property="frais", type="number", format="float", example=50.00),
     *                         @OA\Property(property="statut", type="string", enum={"reussi", "echec"}, example="reussi"),
     *                         @OA\Property(property="created_at", type="string", format="date-time", example="2025-11-10T09:00:00Z")
     *                     )
     *                 ),
     *                 @OA\Property(property="pagination", type="object",
     *                     @OA\Property(property="current_page", type="integer", example=1),
     *                     @OA\Property(property="last_page", type="integer", example=5),
     *                     @OA\Property(property="per_page", type="integer", example=15),
     *                     @OA\Property(property="total", type="integer", example=67),
     *                     @OA\Property(property="from", type="integer", example=1),
     *                     @OA\Property(property="to", type="integer", example=15)
     *                 )
     *             )
     *         )
     *     )
     * )
     */
    public function index(): JsonResponse
    {
        try {
            $user = auth()->user();
            $perPage = request('per_page', 15);
            $transactions = $this->transactionRepository->getUserTransactions($user, $perPage);

            return $this->successResponse([
                'transactions' => TransactionResource::collection($transactions->items()),
                'pagination' => [
                    'current_page' => $transactions->currentPage(),
                    'last_page' => $transactions->lastPage(),
                    'per_page' => $transactions->perPage(),
                    'total' => $transactions->total(),
                    'from' => $transactions->firstItem(),
                    'to' => $transactions->lastItem(),
                ]
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse('Erreur lors de la récupération des transactions', 500, $e->getMessage());
        }
    }

    /**
     * @OA\Get(
     *     path="/transactions/{reference}",
     *     summary="Détail d'une transaction",
     *     description="Retourne les détails complets d'une transaction spécifique",
     *     operationId="getTransaction",
     *     tags={"Transactions"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="reference",
     *         in="path",
     *         required=true,
     *         description="Référence de la transaction",
     *         @OA\Schema(type="string", example="TXN-20251110-ABC123")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Détails de la transaction",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="transaction", type="object",
     *                     @OA\Property(property="reference", type="string", example="TXN-20251110-ABC123"),
     *                     @OA\Property(property="type", type="string", enum={"paiement", "transfert", "depot"}, example="transfert"),
     *                     @OA\Property(property="montant", type="number", format="float", example=5000.00),
     *                     @OA\Property(property="frais", type="number", format="float", example=50.00),
     *                     @OA\Property(property="statut", type="string", enum={"reussi", "echec"}, example="reussi"),
     *                     @OA\Property(property="description", type="string", example="Transfert vers OM-2025-EF56-GH78"),
     *                     @OA\Property(property="compteEmetteur", type="object",
     *                         @OA\Property(property="user", type="object",
     *                             @OA\Property(property="nom", type="string", example="Diop"),
     *                             @OA\Property(property="prenom", type="string", example="Amadou")
     *                         )
     *                     ),
     *                     @OA\Property(property="compteDestinataire", type="object",
     *                         @OA\Property(property="user", type="object",
     *                             @OA\Property(property="nom", type="string", example="Sarr"),
     *                             @OA\Property(property="prenom", type="string", example="Fatou")
     *                         )
     *                     ),
     *                     @OA\Property(property="marchand", type="object", nullable=true,
     *                         @OA\Property(property="raison_sociale", type="string", example="Boutique Express"),
     *                         @OA\Property(property="code_marchand", type="string", example="MARCHAND001")
     *                     ),
     *                     @OA\Property(property="created_at", type="string", format="date-time", example="2025-11-10T09:00:00Z")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Accès non autorisé à cette transaction"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Transaction non trouvée"
     *     )
     * )
     */
    public function show(string $reference): JsonResponse
    {
        try {
            $transaction = $this->transactionRepository->findByReference($reference);

            if (!$transaction) {
                return $this->errorResponse('Transaction non trouvée', 404);
            }

            // Vérifier que l'utilisateur a accès à cette transaction
            $user = auth()->user();
            $hasAccess = $transaction->compte_emetteur_id === $user->compte->id ||
                          $transaction->compte_destinataire_id === $user->compte->id;

            if (!$hasAccess) {
                return $this->errorResponse('Accès non autorisé', 403);
            }

            return $this->successResponse([
                'transaction' => new TransactionResource($transaction->load(['compteEmetteur.user', 'compteDestinataire.user', 'marchand']))
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse('Erreur lors de la récupération de la transaction', 500, $e->getMessage());
        }
    }
}