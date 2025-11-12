<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AuthService;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\ChangePinRequest;
use App\Http\Requests\VerifyOtpRequest;
use App\Http\Requests\SetPinRequest;
use App\Traits\ApiResponseTrait;
use App\Http\Resources\UserResource;
use App\Http\Resources\CompteResource;
use App\Http\Resources\TransactionResource;
use Illuminate\Http\JsonResponse;

/**
 * @OA\Info(
 *     title="OM PAY API",
 *     version="1.0.0",
 *     description="API de paiement mobile OM PAY avec authentification OTP et gestion de comptes"
 * )
 *
 * @OA\Server(
 *     url="http://localhost:8000/api",
 *     description="Serveur de développement"
 * )
 *
 * @OA\Server(
 *     url="https://ompay-isuf.onrender.com/api",
 *     description="Serveur de production"
 * )
 *
 * @OA\SecurityScheme(
 *     securityScheme="bearerAuth",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT"
 * )
 */
class AuthController extends Controller
{
    use ApiResponseTrait;

    private AuthService $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    /**
     * @OA\Post(
      *     path="/auth/register",
      *     summary="Inscription d'un nouvel utilisateur",
      *     description="Crée un compte utilisateur, génère un QR code et envoie un OTP par email",
      *     operationId="register",
      *     tags={"Authentification"},
      *     @OA\RequestBody(
      *         required=true,
      *         @OA\JsonContent(
      *             required={"nom", "prenom", "telephone", "email"},
      *             @OA\Property(property="nom", type="string", example="Diop", description="Nom de l'utilisateur"),
      *             @OA\Property(property="prenom", type="string", example="Amadou", description="Prénom de l'utilisateur"),
      *             @OA\Property(property="telephone", type="string", example="771234567", description="Numéro de téléphone (9 chiffres)"),
      *             @OA\Property(property="email", type="string", example="amadou.diop@example.com", description="Adresse email de l'utilisateur")
      *         )
      *     ),
      *     @OA\Response(
      *         response=201,
      *         description="Utilisateur créé avec succès",
      *         @OA\JsonContent(
      *             @OA\Property(property="success", type="boolean", example=true),
      *             @OA\Property(property="message", type="string", example="Utilisateur créé avec succès. Vérifiez votre email pour le code OTP."),
      *             @OA\Property(property="data", type="object",
      *                 @OA\Property(property="user", type="object",
      *                     @OA\Property(property="id", type="string", example="uuid-string"),
      *                     @OA\Property(property="nom", type="string", example="Diop"),
      *                     @OA\Property(property="prenom", type="string", example="Amadou"),
      *                     @OA\Property(property="telephone", type="string", example="771234567"),
      *                     @OA\Property(property="email", type="string", example="amadou.diop@example.com"),
      *                     @OA\Property(property="is_verified", type="boolean", example=false)
      *                 )
      *             )
      *         )
      *     ),
      *     @OA\Response(
      *         response=500,
      *         description="Erreur lors de l'inscription"
      *     )
      * )
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        try {
            $user = $this->authService->register($request->validated());

            return $this->successResponse(
                ['user' => new UserResource($user)],
                'Utilisateur créé avec succès. Vérifiez votre email pour le code OTP.',
                201
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Erreur lors de l\'inscription', 500, $e->getMessage());
        }
    }

    /**
     * @OA\Post(
      *     path="/auth/verify-otp",
      *     summary="Vérification du code OTP",
      *     description="Valide le code OTP reçu par email et définit un PIN temporaire (0000)",
      *     operationId="verifyOtp",
      *     tags={"Authentification"},
      *     @OA\RequestBody(
      *         required=true,
      *         @OA\JsonContent(
      *             required={"telephone", "otp"},
      *             @OA\Property(property="telephone", type="string", example="771234567", description="Numéro de téléphone"),
      *             @OA\Property(property="otp", type="string", example="123456", description="Code OTP à 6 chiffres")
      *         )
      *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Vérification réussie",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Vérification réussie. Votre PIN temporaire est 0000. Veuillez le changer immédiatement."),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="token", type="string", example="bearer-token-string"),
     *                 @OA\Property(property="refresh_token", type="string", example="refresh-token-string"),
     *                 @OA\Property(property="token_type", type="string", example="Bearer"),
     *                 @OA\Property(property="temporary_pin", type="string", example="0000"),
     *                 @OA\Property(property="requires_pin_change", type="boolean", example=true)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Code OTP invalide ou expiré"
     *     )
     * )
     */
    public function verifyOtp(VerifyOtpRequest $request): JsonResponse
    {
        try {
            $user = $this->authService->verifyOtp(
                $request->input('telephone'),
                $request->input('otp')
            );

            if (!$user) {
                return $this->errorResponse('Code OTP invalide ou expiré', 400);
            }

            return $this->successResponse([
                'token' => $user->access_token,
                'refresh_token' => $user->refresh_token,
                'token_type' => 'Bearer',
                'temporary_pin' => '0000',
                'requires_pin_change' => true,
            ], 'Vérification réussie. Votre PIN temporaire est 0000. Veuillez le changer immédiatement.');
        } catch (\Exception $e) {
            return $this->errorResponse('Erreur lors de la vérification OTP', 500, $e->getMessage());
        }
    }

    /**
     * @OA\Post(
     *     path="/auth/set-pin",
     *     summary="Définition du PIN définitif",
     *     description="Permet à l'utilisateur de définir son PIN définitif après la première connexion avec le PIN temporaire",
     *     operationId="setPin",
     *     tags={"Authentification"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"code_pin"},
     *             @OA\Property(property="code_pin", type="string", example="1234", description="Nouveau code PIN définitif à 4 chiffres")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="PIN définitif défini avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="PIN définitif défini avec succès. Vous pouvez maintenant utiliser votre compte normalement.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="PIN déjà défini ou invalide"
     *     )
     * )
     */
    public function setPin(SetPinRequest $request): JsonResponse
    {
        try {
            $user = auth()->user();
            $success = $this->authService->setDefinitivePin($user, $request->input('code_pin'));

            if (!$success) {
                return $this->errorResponse('Vous avez déjà défini votre PIN définitif', 400);
            }

            return $this->successResponse(null, 'PIN définitif défini avec succès. Vous pouvez maintenant utiliser votre compte normalement.');
        } catch (\Exception $e) {
            return $this->errorResponse('Erreur lors de la définition du PIN', 500, $e->getMessage());
        }
    }

    /**
     * @OA\Post(
     *     path="/auth/login",
     *     summary="Connexion utilisateur",
     *     description="Authentifie un utilisateur vérifié avec son numéro de téléphone et code PIN",
     *     operationId="login",
     *     tags={"Authentification"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"telephone", "code_pin"},
     *             @OA\Property(property="telephone", type="string", example="771234567", description="Numéro de téléphone"),
     *             @OA\Property(property="code_pin", type="string", example="1234", description="Code PIN à 4 chiffres")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Connexion réussie",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Connexion réussie"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="token", type="string", example="bearer-token-string"),
     *                 @OA\Property(property="refresh_token", type="string", example="refresh-token-string"),
     *                 @OA\Property(property="token_type", type="string", example="Bearer")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Téléphone ou code PIN incorrect"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Compte non vérifié"
     *     )
     * )
     */
    public function login(LoginRequest $request): JsonResponse
    {
        try {
            $user = $this->authService->login(
                $request->input('telephone'),
                $request->input('code_pin')
            );

            if (!$user) {
                return $this->errorResponse('Téléphone ou code PIN incorrect', 401);
            }

            return $this->successResponse([
                'token' => $user->access_token,
                'refresh_token' => $user->refresh_token,
                'token_type' => 'Bearer',
            ], 'Connexion réussie');
        } catch (\Exception $e) {
            return $this->errorResponse('Erreur lors de la connexion', 500, $e->getMessage());
        }
    }

    /**
     * @OA\Post(
     *     path="/auth/logout",
     *     summary="Déconnexion utilisateur",
     *     description="Invalide le token d'accès actuel",
     *     operationId="logout",
     *     tags={"Authentification"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Déconnexion réussie",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Déconnexion réussie")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Token invalide"
     *     )
     * )
     */
    public function logout(): JsonResponse
    {
        try {
            $this->authService->logout(auth()->user());

            return $this->successResponse(null, 'Déconnexion réussie');
        } catch (\Exception $e) {
            return $this->errorResponse('Erreur lors de la déconnexion', 500, $e->getMessage());
        }
    }

    /**
     * @OA\Post(
     *     path="/auth/change-pin",
     *     summary="Changement du code PIN",
     *     description="Permet à l'utilisateur de changer son code PIN",
     *     operationId="changePin",
     *     tags={"Authentification"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"old_pin", "new_pin"},
     *             @OA\Property(property="old_pin", type="string", example="1234", description="Ancien code PIN"),
     *             @OA\Property(property="new_pin", type="string", example="5678", description="Nouveau code PIN à 4 chiffres")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Code PIN modifié avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Code PIN modifié avec succès")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Ancien code PIN incorrect"
     *     )
     * )
     */
    public function changePin(ChangePinRequest $request): JsonResponse
    {
        try {
            $success = $this->authService->changePin(
                auth()->user(),
                $request->input('old_pin'),
                $request->input('new_pin')
            );

            if (!$success) {
                return $this->errorResponse('Ancien code PIN incorrect', 400);
            }

            return $this->successResponse(null, 'Code PIN modifié avec succès');
        } catch (\Exception $e) {
            return $this->errorResponse('Erreur lors du changement de PIN', 500, $e->getMessage());
        }
    }

    /**
     * @OA\Get(
     *     path="/auth/me",
     *     summary="Informations de l'utilisateur connecté",
     *     description="Retourne les informations de l'utilisateur actuellement authentifié",
     *     operationId="me",
     *     tags={"Authentification"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Informations utilisateur",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="user", type="object",
     *                     @OA\Property(property="id", type="string", example="uuid-string"),
     *                     @OA\Property(property="nom", type="string", example="Diop"),
     *                     @OA\Property(property="prenom", type="string", example="Amadou"),
     *                     @OA\Property(property="telephone", type="string", example="771234567"),
     *                     @OA\Property(property="email", type="string", example="amadou.diop@example.com"),
     *                     @OA\Property(property="type", type="string", example="client"),
     *                     @OA\Property(property="statut", type="string", example="actif"),
     *                     @OA\Property(property="is_verified", type="boolean", example=true),
     *                     @OA\Property(property="created_at", type="string", format="date-time"),
     *                     @OA\Property(property="updated_at", type="string", format="date-time")
     *                 ),
     *                 @OA\Property(property="compte", type="object",
     *                     @OA\Property(property="numero_compte", type="string", example="OM-2025-AB12-CD34"),
     *                     @OA\Property(property="solde", type="number", format="float", example=15000.50)
     *                 ),
     *                 @OA\Property(property="solde", type="number", format="float", example=15000.50),
     *                 @OA\Property(property="qr_code", type="string", example="QR code data"),
     *                 @OA\Property(property="transactions", type="object",
     *                     @OA\Property(property="data", type="array", @OA\Items(type="object")),
     *                     @OA\Property(property="pagination", type="object")
     *                 )
     *             )
     *         )
     *     )
     * )
     */
    public function me(): JsonResponse
    {
        try {
            $user = $this->authService->getAuthenticatedUser();

            // Charger les relations nécessaires
            $user->load(['compte']);

            // Récupérer les transactions paginées (5 par page)
            $transactions = $user->transactionsEmises()
                ->with(['compteDestinataire.user', 'marchand'])
                ->orderBy('created_at', 'desc')
                ->paginate(5);

            return $this->successResponse([
                'user' => new UserResource($user),
                'compte' => $user->compte ? new CompteResource($user->compte) : null,
                'solde' => $user->compte ? $user->compte->solde : 0,
                'qr_code' => $user->compte ? $user->compte->qr_code_data : null,
                'transactions' => [
                    'data' => TransactionResource::collection($transactions->items()),
                    'pagination' => [
                        'current_page' => $transactions->currentPage(),
                        'last_page' => $transactions->lastPage(),
                        'per_page' => $transactions->perPage(),
                        'total' => $transactions->total(),
                        'from' => $transactions->firstItem(),
                        'to' => $transactions->lastItem(),
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse('Erreur lors de la récupération des données utilisateur', 500, $e->getMessage());
        }
    }
}