# Rapport API OM Pay

## Vue d'ensemble

L'API OM Pay est une API RESTful Laravel pour un système de paiement mobile avec authentification OTP, gestion de comptes et transactions.

**Base URL :** `https://om-pay-latest.onrender.com/api`

**Version :** 1.0.0

**Authentification :** Bearer Token (Laravel Passport)

---

## 1. Endpoints d'Authentification

### 1.1 Inscription Utilisateur
**Endpoint :** `POST /auth/register`

**Statut :** ✅ Fonctionnel

**Description :** Crée un nouveau compte utilisateur avec génération automatique de compte et QR code.

**Requête :**
```json
{
  "nom": "Diop",
  "prenom": "Amadou",
  "telephone": "771234567",
  "email": "amadou.diop@example.com"
}
```

**Réponse (201) :**
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

### 1.2 Vérification OTP
**Endpoint :** `POST /auth/verify-otp`

**Statut :** ✅ Fonctionnel

**Description :** Valide le code OTP et définit un PIN temporaire.

**Requête :**
```json
{
  "telephone": "771234567",
  "otp": "123456"
}
```

**Réponse (200) :**
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

### 1.3 Connexion
**Endpoint :** `POST /auth/login`

**Statut :** ✅ Fonctionnel

**Description :** Authentifie un utilisateur avec numéro de téléphone et PIN.

**Requête :**
```json
{
  "telephone": "771234567",
  "code_pin": "1234"
}
```

**Réponse (200) :**
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

### 1.4 Définition PIN Définitif
**Endpoint :** `POST /auth/set-pin`

**Statut :** ✅ Fonctionnel

**Authentification :** Requise (Bearer Token)

**Description :** Définit le PIN définitif après première connexion.

**Requête :**
```json
{
  "code_pin": "1234"
}
```

**Réponse (200) :**
```json
{
  "success": true,
  "message": "PIN définitif défini avec succès. Vous pouvez maintenant utiliser votre compte normalement."
}
```

### 1.5 Changement PIN
**Endpoint :** `POST /auth/change-pin`

**Statut :** ✅ Fonctionnel

**Authentification :** Requise

**Description :** Change le code PIN de l'utilisateur.

**Requête :**
```json
{
  "old_pin": "1234",
  "new_pin": "5678"
}
```

**Réponse (200) :**
```json
{
  "success": true,
  "message": "Code PIN modifié avec succès"
}
```

### 1.6 Déconnexion
**Endpoint :** `POST /auth/logout`

**Statut :** ✅ Fonctionnel

**Authentification :** Requise

**Description :** Invalide le token d'accès actuel.

**Réponse (200) :**
```json
{
  "success": true,
  "message": "Déconnexion réussie"
}
```

### 1.7 Informations Utilisateur
**Endpoint :** `GET /auth/me`

**Statut :** ✅ Fonctionnel

**Authentification :** Requise

**Description :** Retourne les informations complètes de l'utilisateur connecté.

**Réponse (200) :**
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
      "data": [...],
      "pagination": {...}
    }
  }
}
```

---

## 2. Endpoints de Comptes

### 2.1 Informations du Compte
**Endpoint :** `GET /compte/{compte}`

**Statut :** ✅ Fonctionnel

**Authentification :** Requise

**Description :** Retourne les informations détaillées d'un compte.

**Paramètres :**
- `compte` (path) : ID du compte (UUID)

**Réponse (200) :**
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
      "updated_at": "2025-11-10T09:00:00Z",
      "user": {
        "id": "uuid-string",
        "role": "client"
      }
    }
  }
}
```

### 2.2 Consultation du Solde
**Endpoint :** `GET /compte/{compte}/solde`

**Statut :** ✅ Fonctionnel

**Authentification :** Requise

**Description :** Retourne le solde actuel du compte.

**Réponse (200) :**
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

### 2.3 QR Code du Compte
**Endpoint :** `GET /compte/{compte}/qrcode`

**Statut :** ✅ Fonctionnel

**Authentification :** Requise

**Description :** Retourne l'URL du QR code du compte.

**Réponse (200) :**
```json
{
  "success": true,
  "data": {
    "qr_code_url": "https://ompay.onrender.com/storage/qrcodes/qr_OM-2025-AB12-CD34_abc123.png",
    "numero_compte": "OM-2025-AB12-CD34"
  },
  "message": "QR code récupéré avec succès"
}
```

### 2.4 Dépôt d'Argent
**Endpoint :** `POST /compte/{compte}/depot`

**Statut :** ✅ Fonctionnel

**Authentification :** Requise

**Description :** Effectue un dépôt d'argent sur le compte.

**Requête :**
```json
{
  "montant": 5000.00
}
```

**Réponse (200) :**
```json
{
  "success": true,
  "data": {
    "transaction": {
      "reference": "TRX-20251112-ABC123",
      "type": "depot",
      "montant": 5000.00,
      "frais": 0,
      "statut": "reussi"
    },
    "nouveau_solde": 20000.50
  },
  "message": "Dépôt effectué avec succès"
}
```

---

## 3. Endpoints de Transactions

### 3.1 Effectuer un Paiement
**Endpoint :** `POST /transactions/paiement`

**Statut :** ✅ Fonctionnel

**Authentification :** Requise

**Description :** Effectue un paiement vers un marchand ou un client.

**Requête :**
```json
{
  "destinataire": "MARCHAND001",
  "montant": 2500.00
}
```

**Réponse (200) :**
```json
{
  "success": true,
  "data": {
    "transaction": {
      "reference": "TRX-20251112-ABC123",
      "type": "paiement",
      "montant": 2500.00,
      "frais": 0,
      "statut": "reussi",
      "compteEmetteur": {
        "numero_compte": "OM-2025-AB12-CD34"
      },
      "marchand": {
        "raison_sociale": "Boutique Express",
        "code_marchand": "MARCHAND001"
      }
    }
  },
  "message": "Paiement effectué avec succès"
}
```

### 3.2 Effectuer un Transfert
**Endpoint :** `POST /transactions/transfert`

**Statut :** ✅ Fonctionnel

**Authentification :** Requise

**Description :** Effectue un transfert P2P vers un autre utilisateur.

**Requête :**
```json
{
  "numero_destinataire": "781234567",
  "montant": 10000.00
}
```

**Réponse (200) :**
```json
{
  "success": true,
  "data": {
    "transaction": {
      "reference": "TRX-20251112-DEF456",
      "type": "transfert",
      "montant": 10000.00,
      "frais": 100.00,
      "statut": "reussi",
      "compteEmetteur": {
        "numero_compte": "OM-2025-AB12-CD34",
        "user": {
          "id": "uuid-string",
          "role": "client"
        }
      },
      "compteDestinataire": {
        "numero_compte": "OM-2025-EF56-GH78",
        "user": {
          "id": "uuid-string",
          "role": "client"
        }
      }
    }
  },
  "message": "Transfert effectué avec succès"
}
```

### 3.3 Liste des Transactions
**Endpoint :** `GET /transactions`

**Statut :** ✅ Fonctionnel

**Authentification :** Requise

**Description :** Retourne la liste paginée des transactions de l'utilisateur.

**Paramètres de requête :**
- `page` (optionnel) : Numéro de page (défaut: 1)
- `per_page` (optionnel) : Nombre d'éléments par page (défaut: 15, max: 100)

**Réponse (200) :**
```json
{
  "success": true,
  "data": {
    "transactions": [
      {
        "reference": "TRX-20251112-ABC123",
        "type": "transfert",
        "montant": 10000.00,
        "frais": 100.00,
        "statut": "reussi",
        "created_at": "2025-11-12T10:00:00Z"
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

### 3.4 Détail d'une Transaction
**Endpoint :** `GET /transactions/{reference}`

**Statut :** ✅ Fonctionnel

**Authentification :** Requise

**Description :** Retourne les détails complets d'une transaction spécifique.

**Paramètres :**
- `reference` (path) : Référence de la transaction

**Réponse (200) :**
```json
{
  "success": true,
  "data": {
    "transaction": {
      "reference": "TRX-20251112-ABC123",
      "type": "transfert",
      "montant": 10000.00,
      "frais": 100.00,
      "statut": "reussi",
      "description": "Transfert vers OM-2025-EF56-GH78",
      "compteEmetteur": {
        "numero_compte": "OM-2025-AB12-CD34",
        "user": {
          "id": "uuid-string",
          "role": "client"
        }
      },
      "compteDestinataire": {
        "numero_compte": "OM-2025-EF56-GH78",
        "user": {
          "id": "uuid-string",
          "role": "client"
        }
      },
      "created_at": "2025-11-12T10:00:00Z"
    }
  }
}
```

---

## 4. Codes d'Erreur

### Erreurs d'Authentification
- `400` : Code OTP invalide ou expiré
- `401` : Téléphone ou code PIN incorrect
- `403` : Compte non vérifié

### Erreurs de Transactions
- `400` : Solde insuffisant, montant invalide, destinataire non trouvé
- `403` : Accès non autorisé au compte/transaction
- `404` : Compte ou transaction non trouvé

### Erreurs Générales
- `500` : Erreur interne du serveur

---

## 5. Limites et Règles Métier

### Limites de Montant
- **Paiement** : 100 - 500,000 FCFA
- **Transfert** : 100 - 100,000 FCFA
- **Dépôt** : 100 - 500,000 FCFA

### Frais de Transaction
- **Paiement marchand** : 0 FCFA
- **Transfert** :
  - 0 - 5,000 FCFA : 0 FCFA
  - 5,001 - 50,000 FCFA : 100 FCFA
  - 50,001 - 100,000 FCFA : 200 FCFA

### Règles de Sécurité
- Auto-transfert interdit
- Vérification de solde avant débit
- Transactions atomiques (rollback en cas d'échec)
- Authentification obligatoire pour toutes les opérations sensibles

---

## 6. Architecture et Sécurité

### Authentification
- **Type** : Bearer Token (Laravel Passport)
- **Durée** : Tokens persistants (pas d'expiration automatique)
- **Refresh Token** : Fourni avec chaque connexion

### Base de Données
- **Type** : PostgreSQL (Neon)
- **Architecture** : Transactions atomiques
- **Calcul du solde** : Dynamique à partir des transactions

### Sécurité
- **Validation stricte** : Toutes les entrées validées
- **Audit trail** : Toutes les transactions tracées
- **Gestion d'erreurs** : Messages d'erreur sécurisés
- **Rate limiting** : Protection contre les abus

---

## 7. Statut Global des Endpoints

| Catégorie | Endpoint | Méthode | Statut | Authentification |
|-----------|----------|---------|--------|------------------|
| Authentification | `/auth/register` | POST | ✅ Fonctionnel | Non requise |
| Authentification | `/auth/verify-otp` | POST | ✅ Fonctionnel | Non requise |
| Authentification | `/auth/login` | POST | ✅ Fonctionnel | Non requise |
| Authentification | `/auth/set-pin` | POST | ✅ Fonctionnel | Requise |
| Authentification | `/auth/change-pin` | POST | ✅ Fonctionnel | Requise |
| Authentification | `/auth/logout` | POST | ✅ Fonctionnel | Requise |
| Authentification | `/auth/me` | GET | ✅ Fonctionnel | Requise |
| Comptes | `/compte/{compte}` | GET | ✅ Fonctionnel | Requise |
| Comptes | `/compte/{compte}/solde` | GET | ✅ Fonctionnel | Requise |
| Comptes | `/compte/{compte}/qrcode` | GET | ✅ Fonctionnel | Requise |
| Comptes | `/compte/{compte}/depot` | POST | ✅ Fonctionnel | Requise |
| Transactions | `/transactions/paiement` | POST | ✅ Fonctionnel | Requise |
| Transactions | `/transactions/transfert` | POST | ✅ Fonctionnel | Requise |
| Transactions | `/transactions` | GET | ✅ Fonctionnel | Requise |
| Transactions | `/transactions/{reference}` | GET | ✅ Fonctionnel | Requise |

---

## 8. Recommandations d'Utilisation

### Flux d'Inscription
1. `POST /auth/register` - Créer le compte
2. Vérifier l'email pour le code OTP
3. `POST /auth/verify-otp` - Valider OTP et obtenir tokens
4. `POST /auth/set-pin` - Définir PIN définitif

### Flux de Transaction
1. `POST /auth/login` - Se connecter
2. `GET /auth/me` - Vérifier le profil
3. Effectuer des transactions selon les besoins
4. `POST /auth/logout` - Se déconnecter

### Gestion d'Erreurs
- Toujours vérifier le champ `success`
- Gérer les codes d'erreur HTTP appropriés
- Utiliser les messages d'erreur pour l'UX

---

**Documentation générée le :** 12 novembre 2025
**Version API :** 1.0.0
**Environnement :** Production (Render)
**Base de données :** PostgreSQL (Neon)