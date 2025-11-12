# Logique des Transactions OM Pay

## Vue d'ensemble

Le système OM Pay gère trois types principaux de transactions :
- **Dépôt** : Crédit d'argent sur un compte utilisateur
- **Paiement** : Transfert d'argent vers un marchand ou un autre client
- **Transfert** : Transfert P2P (Peer-to-Peer) entre comptes utilisateurs

Toutes les transactions sont traitées de manière atomique avec des transactions de base de données pour garantir la cohérence des données.

## Architecture des Transactions

### Modèle Transaction

Chaque transaction contient les informations suivantes :

```php
[
    'reference' => 'TRX-20251112-ABC123', // Référence unique générée
    'type' => 'depot|paiement|transfert', // Type de transaction
    'statut' => 'reussi|echec', // Statut de la transaction
    'compte_emetteur_id' => UUID, // Compte source (nullable pour dépôt)
    'compte_destinataire_id' => UUID, // Compte destination (nullable pour paiement marchand)
    'marchand_id' => UUID, // Marchand destinataire (nullable)
    'montant' => float, // Montant de la transaction
    'frais' => float, // Frais de transaction
    'description' => string, // Description de la transaction
]
```

### Gestion du Solde

Le solde des comptes est **calculé dynamiquement** à partir des transactions réussies :

```php
$solde = (somme des dépôts reçus) - (somme des retraits émis + frais)
```

Cette approche garantit l'intégrité des données et évite les incohérences.

## Types de Transactions

### 1. Dépôt d'Argent

**Processus :**
1. Vérification des limites de dépôt (100 - 500,000 FCFA)
2. Création de la transaction avec statut "reussi"
3. Crédit du compte destinataire (pas de débit nécessaire)

**Caractéristiques :**
- Frais : 0 FCFA
- Type : "depot"
- Pas de compte émetteur

### 2. Paiement

**Processus :**
1. Vérification des limites de paiement (100 - 500,000 FCFA)
2. Recherche du destinataire :
   - D'abord comme code marchand
   - Ensuite comme numéro de téléphone
3. Vérification du solde suffisant
4. Création de la transaction
5. Débit du compte émetteur et crédit du destinataire (si paiement client)

**Types de paiement :**
- **Paiement marchand** : Vers un code marchand (ex: MCH-AUCH1)
  - Type : "paiement"
  - Frais : 0 FCFA
  - Pas de crédit (l'argent sort du système)
- **Paiement client** : Vers un numéro de téléphone
  - Type : "paiement_client"
  - Frais : 0 FCFA
  - Crédit du compte destinataire

### 3. Transfert P2P

**Processus :**
1. Vérification des limites de transfert (100 - 100,000 FCFA)
2. Recherche du compte destinataire par numéro de téléphone
3. Calcul des frais selon le montant
4. Vérification du solde suffisant (montant + frais)
5. Création de la transaction
6. Débit du compte émetteur (montant + frais)
7. Crédit du compte destinataire (montant uniquement)

**Frais de transfert :**
- 0 - 5,000 FCFA : 0 FCFA
- 5,001 - 50,000 FCFA : 100 FCFA
- 50,001 - 100,000 FCFA : 200 FCFA

## Contrôles et Validations

### Limites de Montant

```php
'limites' => [
    'paiement' => ['min' => 100, 'max' => 500000],
    'transfert' => ['min' => 100, 'max' => 100000],
    'depot' => ['min' => 100, 'max' => 500000],
]
```

### Vérifications de Sécurité

1. **Solde suffisant** : Avant chaque débit
2. **Auto-transfert interdit** : Impossible de payer/transférer vers soi-même
3. **Destinataire valide** : Vérification de l'existence du marchand/compte
4. **Transaction atomique** : Utilisation de `DB::transaction()` pour garantir l'intégrité

### Gestion d'Erreurs

Toutes les erreurs sont capturées et transformées en exceptions avec des messages explicites :
- "Solde insuffisant"
- "Destinataire non trouvé"
- "Montant minimum/maximum dépassé"
- "Impossible de payer vers son propre compte"

## Flux de Transaction

### Exemple : Transfert de 10,000 FCFA

1. **Validation** :
   - Vérifier limites (100 ≤ 10,000 ≤ 100,000) ✓
   - Calculer frais : 100 FCFA (plage 5,001-50,000)
   - Vérifier solde ≥ 10,100 FCFA ✓

2. **Création** :
   ```php
   Transaction::create([
       'reference' => 'TRX-20251112-ABC123',
       'type' => 'transfert',
       'statut' => 'reussi',
       'compte_emetteur_id' => $emetteur->id,
       'compte_destinataire_id' => $destinataire->id,
       'montant' => 10000,
       'frais' => 100,
       'description' => 'Transfert vers OM-2025-XXXX-YYYY'
   ]);
   ```

3. **Mise à jour soldes** :
   - Émetteur : solde - 10,100 FCFA
   - Destinataire : solde + 10,000 FCFA

## API Endpoints

### Paiement
```
POST /api/transactions/paiement
{
    "destinataire": "MARCHAND001", // ou numéro téléphone
    "montant": 2500.00
}
```

### Transfert
```
POST /api/transactions/transfert
{
    "numero_destinataire": "781234567",
    "montant": 10000.00
}
```

### Liste des Transactions
```
GET /api/transactions?page=1&per_page=15
```

### Détail d'une Transaction
```
GET /api/transactions/{reference}
```

## Sécurité et Intégrité

- **Transactions atomiques** : Utilisation de `DB::transaction()`
- **Références uniques** : Génération automatique avec timestamp
- **Audit trail** : Toutes les transactions tracées avec métadonnées
- **Validation stricte** : Contrôles avant exécution
- **Gestion d'erreurs** : Rollback automatique en cas d'échec

## Points d'Extension

Le système est conçu pour être facilement extensible :

- **Nouveaux types de frais** : Configuration dans `config/om_pay.php`
- **Nouveaux types de transaction** : Ajout de méthodes dans `TransactionService`
- **Intégrations externes** : Support pour APIs de paiement externes
- **Notifications** : Système d'événements pour alertes en temps réel