<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransactionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'reference' => $this->reference,
            'type' => $this->type,
            'montant' => $this->montant,
            'frais' => $this->frais,
            'statut' => $this->statut,
            'description' => $this->description,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'compte_emetteur' => $this->whenLoaded('compteEmetteur', function () {
                return [
                    'id' => $this->compteEmetteur->id,
                    'numero_compte' => $this->compteEmetteur->numero_compte,
                    'user' => $this->whenLoaded('compteEmetteur.user', function () {
                        return [
                            'id' => $this->compteEmetteur->user->id,
                            'role' => $this->compteEmetteur->user->type,
                        ];
                    }),
                ];
            }),
            'compte_destinataire' => $this->whenLoaded('compteDestinataire', function () {
                return [
                    'id' => $this->compteDestinataire->id,
                    'numero_compte' => $this->compteDestinataire->numero_compte,
                    'user' => $this->whenLoaded('compteDestinataire.user', function () {
                        return [
                            'id' => $this->compteDestinataire->user->id,
                            'role' => $this->compteDestinataire->user->type,
                        ];
                    }),
                ];
            }),
            'marchand' => $this->whenLoaded('marchand', function () {
                return [
                    'id' => $this->marchand->id,
                    'nom' => $this->marchand->nom,
                    'code_marchand' => $this->marchand->code_marchand,
                ];
            }),
        ];
    }
}
