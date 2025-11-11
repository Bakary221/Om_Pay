<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
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
            'nom' => $this->nom,
            'prenom' => $this->prenom,
            'telephone' => $this->telephone,
            'email' => $this->email,
            'type' => $this->type,
            'statut' => $this->statut,
            'is_verified' => $this->is_verified,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'compte' => $this->whenLoaded('compte', function () {
                return new CompteResource($this->compte);
            }),
        ];
    }
}
