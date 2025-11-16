# Guide d'utilisation de l'API avec Postman

Ce guide explique comment utiliser l'API Laravel avec Postman pour tester les endpoints disponibles.

## Prérequis

- **Postman** installé sur votre machine.
- L'application Laravel doit être en cours d'exécution (par exemple, via `php artisan serve` ou Docker).
- Une base de données configurée avec des données de test (utilisez les seeders si nécessaire).

## Configuration de base

- **URL de base** : `https://om-pay-latest.onrender.com/api/v1` (serveur de production).
- **Version de l'API** : v1 (préfixe `/v1`).

## Authentification

L'API utilise Laravel Passport pour l'authentification OAuth2. Pour accéder aux endpoints protégés, vous devez obtenir un token d'accès.

### Étape 1 : Créer un client OAuth (si nécessaire)

Si vous n'avez pas encore de client OAuth, créez-en un via Artisan :

```bash
php artisan passport:client --personal
```

Notez l'ID du client et le secret.

### Étape 2 : Obtenir un token d'accès

1. Ouvrez Postman et créez une nouvelle requête.
2. Méthode : `POST`
3. URL : `http://127.0.0.1:8001/api/oauth/token`
4. Headers :
   - `Content-Type: application/json`
   - `Accept: application/json`
5. Body (raw JSON) :
   ```json
   {
     "grant_type": "password",
     "client_id": "votre_client_id",
     "client_secret": "votre_client_secret",
     "username": "email_utilisateur",
     "password": "mot_de_passe_utilisateur",
     "scope": "*"
   }
   ```
6. Envoyez la requête. Vous recevrez un JSON avec `access_token`.

### Étape 3 : Utiliser le token dans les requêtes

Pour les endpoints authentifiés, ajoutez le header :
- `Authorization: Bearer votre_access_token`

## Endpoints disponibles

### 1. Inscription utilisateur (POST /api/v1/auth/register)

- **Méthode** : POST
- **URL** : `https://om-pay-latest.onrender.com/api/v1/auth/register`
- **Authentification** : Non requise
- **Headers** :
  - `Content-Type: application/json`
- **Corps de la requête** :
  ```json
  {
    "nom": "Diop",
    "prenom": "Amadou",
    "telephone": "771234567",
    "email": "amadou.diop@example.com"
  }
  ```
- **Exemple de réponse** :
  ```json
  {
    "success": true,
    "message": "Utilisateur créé avec succès. Vérifiez votre email pour le code OTP.",
    "data": {
      "user": {
        "id": "uuid-string",
        "nom": "Diop",
        "prenom": "Amadou",
        "telephone": "771234567",
        "email": "amadou.diop@example.com",
        "is_verified": false
      }
    }
  }
  ```

### 2. Vérification OTP (POST /api/v1/auth/verify-otp)

- **Méthode** : POST
- **URL** : `https://om-pay-latest.onrender.com/api/v1/auth/verify-otp`
- **Authentification** : Non requise
- **Headers** :
  - `Content-Type: application/json`
- **Corps de la requête** :
  ```json
  {
    "telephone": "771234567",
    "otp": "123456"
  }
  ```
- **Exemple de réponse** :
  ```json
  {
    "success": true,
    "message": "Vérification réussie. Votre PIN temporaire est 0000. Veuillez le changer immédiatement.",
    "data": {
      "token": "bearer-token-string",
      "refresh_token": "refresh-token-string",
      "token_type": "Bearer",
      "temporary_pin": "0000",
      "requires_pin_change": true
    }
  }
  ```

### 3. Connexion utilisateur (POST /api/v1/auth/login)

- **Méthode** : POST
- **URL** : `https://om-pay-latest.onrender.com/api/v1/auth/login`
- **Authentification** : Non requise
- **Headers** :
  - `Content-Type: application/json`
- **Corps de la requête** :
  ```json
  {
    "telephone": "771234567",
    "code_pin": "1234"
  }
  ```
- **Exemple de réponse** :
  ```json
  {
    "success": true,
    "message": "Connexion réussie",
    "data": {
      "token": "bearer-token-string",
      "refresh_token": "refresh-token-string",
      "token_type": "Bearer"
    }
  }
  ```

### 4. Définir PIN définitif (POST /api/v1/auth/set-pin)

- **Méthode** : POST
- **URL** : `https://om-pay-latest.onrender.com/api/v1/auth/set-pin`
- **Authentification** : Requise (Bearer Token)
- **Headers** :
  - `Content-Type: application/json`
  - `Authorization: Bearer votre_access_token`
- **Corps de la requête** :
  ```json
  {
    "code_pin": "1234"
  }
  ```
- **Exemple de réponse** :
  ```json
  {
    "success": true,
    "message": "PIN définitif défini avec succès. Vous pouvez maintenant utiliser votre compte normalement."
  }
  ```

### 5. Déconnexion utilisateur (POST /api/v1/auth/logout)

- **Méthode** : POST
- **URL** : `https://om-pay-latest.onrender.com/api/v1/auth/logout`
- **Authentification** : Requise (Bearer Token)
- **Headers** :
  - `Authorization: Bearer votre_access_token`
- **Corps de la requête** : Aucun
- **Exemple de réponse** :
  ```json
  {
    "success": true,
    "message": "Déconnexion réussie"
  }
  ```

### 6. Changer PIN (POST /api/v1/auth/change-pin)

- **Méthode** : POST
- **URL** : `https://om-pay-latest.onrender.com/api/v1/auth/change-pin`
- **Authentification** : Requise (Bearer Token)
- **Headers** :
  - `Content-Type: application/json`
  - `Authorization: Bearer votre_access_token`
- **Corps de la requête** :
  ```json
  {
    "old_pin": "1234",
    "new_pin": "5678"
  }
  ```
- **Exemple de réponse** :
  ```json
  {
    "success": true,
    "message": "Code PIN modifié avec succès"
  }
  ```

### 7. Informations utilisateur (GET /api/v1/auth/me)

- **Méthode** : GET
- **URL** : `https://om-pay-latest.onrender.com/api/v1/auth/me`
- **Authentification** : Requise (Bearer Token)
- **Headers** :
  - `Authorization: Bearer votre_access_token`
- **Paramètres de requête** : Aucun
- **Exemple de réponse** :
  ```json
  {
    "success": true,
    "data": {
      "user": {
        "id": "uuid-string",
        "nom": "Diop",
        "prenom": "Amadou",
        "telephone": "771234567",
        "email": "amadou.diop@example.com",
        "type": "client",
        "statut": "actif",
        "is_verified": true,
        "created_at": "2025-11-10T09:00:00Z",
        "updated_at": "2025-11-10T09:00:00Z"
      },
      "compte": {
        "numero_compte": "OM-2025-AB12-CD34",
        "solde": 15000.50
      },
      "solde": 15000.50,
      "qr_code": "QR code data",
      "transactions": {
        "data": [],
        "pagination": {
          "current_page": 1,
          "last_page": 1,
          "per_page": 5,
          "total": 0,
          "from": null,
          "to": null
        }
      }
    }
  }
  ```

### 8. Détails du compte (GET /api/v1/compte/{compte})

- **Méthode** : GET
- **URL** : `https://om-pay-latest.onrender.com/api/v1/compte/{compte_id}`
- **Authentification** : Requise (Bearer Token)
- **Headers** :
  - `Authorization: Bearer votre_access_token`
- **Paramètres** :
  - `compte` (path) : ID du compte (UUID)
- **Exemple de réponse** :
  ```json
  {
    "success": true,
    "data": {
      "compte": {
        "id": "uuid-string",
        "numero_compte": "OM-2025-AB12-CD34",
        "solde": 15000.50,
        "qr_code_data": "QR code data",
        "created_at": "2025-11-10T09:00:00Z",
        "updated_at": "2025-11-10T09:00:00Z"
      }
    }
  }
  ```

### 9. Consulter le solde (GET /api/v1/compte/{compte}/solde)

- **Méthode** : GET
- **URL** : `https://om-pay-latest.onrender.com/api/v1/compte/{compte_id}/solde`
- **Authentification** : Requise (Bearer Token)
- **Headers** :
  - `Authorization: Bearer votre_access_token`
- **Paramètres** :
  - `compte` (path) : ID du compte (UUID)
- **Exemple de réponse** :
  ```json
  {
    "success": true,
    "data": {
      "solde": 15000.50,
      "numero_compte": "OM-2025-AB12-CD34",
      "devise": "FCFA"
    },
    "message": "Opération réussie"
  }
  ```

### 10. Récupérer QR code (GET /api/v1/compte/{compte}/qrcode)

- **Méthode** : GET
- **URL** : `https://om-pay-latest.onrender.com/api/v1/compte/{compte_id}/qrcode`
- **Authentification** : Requise (Bearer Token)
- **Headers** :
  - `Authorization: Bearer votre_access_token`
- **Paramètres** :
  - `compte` (path) : ID du compte (UUID)
- **Exemple de réponse** :
  ```json
  {
    "success": true,
    "data": {
      "qr_code_url": "https://om-pay-latest.onrender.com/storage/qrcodes/qr_OM-2025-AB12-CD34_abc123.png",
      "numero_compte": "OM-2025-AB12-CD34"
    },
    "message": "QR code récupéré avec succès"
  }
  ```

### 11. Effectuer un dépôt (POST /api/v1/compte/{compte}/depot)

- **Méthode** : POST
- **URL** : `https://om-pay-latest.onrender.com/api/v1/compte/{compte_id}/depot`
- **Authentification** : Requise (Bearer Token)
- **Headers** :
  - `Content-Type: application/json`
  - `Authorization: Bearer votre_access_token`
- **Paramètres** :
  - `compte` (path) : ID du compte (UUID)
- **Corps de la requête** :
  ```json
  {
    "montant": 5000.00
  }
  ```
- **Exemple de réponse** :
  ```json
  {
    "success": true,
    "message": "Dépôt effectué avec succès",
    "data": {
      "transaction": {
        "reference": "TXN-20251110-ABC123",
        "type": "depot",
        "montant": 5000.00,
        "statut": "reussi"
      },
      "nouveau_solde": 20000.50
    }
  }
  ```

### 12. Effectuer un paiement (POST /api/v1/transactions/paiement)

- **Méthode** : POST
- **URL** : `https://om-pay-latest.onrender.com/api/v1/transactions/paiement`
- **Authentification** : Requise (Bearer Token)
- **Headers** :
  - `Content-Type: application/json`
  - `Authorization: Bearer votre_access_token`
- **Corps de la requête** :
  ```json
  {
    "destinataire": "MARCHAND001",
    "montant": 2500.00
  }
  ```
- **Exemple de réponse** :
  ```json
  {
    "success": true,
    "message": "Paiement effectué avec succès",
    "data": {
      "transaction": {
        "reference": "TXN-20251110-ABC123",
        "type": "paiement",
        "montant": 2500.00,
        "frais": 0,
        "statut": "reussi",
        "marchand": {
          "raison_sociale": "Boutique Express",
          "code_marchand": "MARCHAND001"
        }
      }
    }
  }
  ```

### 13. Effectuer un transfert (POST /api/v1/transactions/transfert)

- **Méthode** : POST
- **URL** : `https://om-pay-latest.onrender.com/api/v1/transactions/transfert`
- **Authentification** : Requise (Bearer Token)
- **Headers** :
  - `Content-Type: application/json`
  - `Authorization: Bearer votre_access_token`
- **Corps de la requête** :
  ```json
  {
    "numero_destinataire": "781562041",
    "montant": 10000.00
  }
  ```
- **Exemple de réponse** :
  ```json
  {
    "success": true,
    "message": "Transfert effectué avec succès",
    "data": {
      "transaction": {
        "reference": "TXN-20251110-DEF456",
        "type": "transfert",
        "montant": 10000.00,
        "frais": 100.00,
        "statut": "reussi",
        "compte_destinataire": {
          "user": {
            "nom": "Sarr",
            "prenom": "Fatou"
          }
        }
      }
    }
  }
  ```

### 14. Liste des transactions (GET /api/v1/transactions)

- **Méthode** : GET
- **URL** : `https://om-pay-latest.onrender.com/api/v1/transactions`
- **Authentification** : Requise (Bearer Token)
- **Headers** :
  - `Authorization: Bearer votre_access_token`
- **Paramètres de requête** :
  - `page` (optionnel) : Numéro de la page (défaut : 1)
  - `per_page` (optionnel) : Nombre d'éléments par page (défaut : 15, max : 100)
- **Exemple de réponse** :
  ```json
  {
    "success": true,
    "data": {
      "transactions": [
        {
          "reference": "TXN-20251110-ABC123",
          "type": "transfert",
          "montant": 5000.00,
          "frais": 50.00,
          "statut": "reussi",
          "created_at": "2025-11-10T09:00:00Z"
        }
      ],
      "pagination": {
        "current_page": 1,
        "last_page": 1,
        "per_page": 15,
        "total": 1,
        "from": 1,
        "to": 1
      }
    }
  }
  ```

### 15. Détail d'une transaction (GET /api/v1/transactions/{reference})

- **Méthode** : GET
- **URL** : `https://om-pay-latest.onrender.com/api/v1/transactions/{reference}`
- **Authentification** : Requise (Bearer Token)
- **Headers** :
  - `Authorization: Bearer votre_access_token`
- **Paramètres** :
  - `reference` (path) : Référence de la transaction
- **Exemple de réponse** :
  ```json
  {
    "success": true,
    "data": {
      "transaction": {
        "reference": "TXN-20251110-ABC123",
        "type": "transfert",
        "montant": 5000.00,
        "frais": 50.00,
        "statut": "reussi",
        "description": "Transfert vers OM-2025-EF56-GH78",
        "compte_emetteur": {
          "numero_compte": "OM-2025-AB12-CD34"
        },
        "compte_destinataire": {
          "numero_compte": "OM-2025-EF56-GH78"
        },
        "created_at": "2025-11-10T09:00:00Z"
      }
    }
  }
  ```

## Gestion des erreurs

- **401 Unauthorized** : Token manquant ou invalide. Vérifiez l'authentification.
- **403 Forbidden** : Accès non autorisé à la ressource.
- **404 Not Found** : Endpoint ou ressource non trouvée.
- **422 Unprocessable Entity** : Données invalides. Vérifiez les champs requis.
- **500 Internal Server Error** : Erreur serveur. Vérifiez les logs Laravel.

## Conseils supplémentaires

- Utilisez des variables d'environnement dans Postman pour stocker l'URL de base et le token.
- Testez d'abord l'endpoint `/api/v1/auth/me` pour vérifier l'authentification.
- Pour les requêtes POST/PUT, assurez-vous d'inclure le header `Content-Type: application/json`.
- Les montants sont en FCFA, devise par défaut.

Si vous rencontrez des problèmes, vérifiez la configuration CORS dans `config/cors.php` et assurez-vous que le serveur est accessible.