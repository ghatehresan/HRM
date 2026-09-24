<?php

namespace App\Http\Resources;

use DateTimeInterface;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Laravel\Sanctum\PersonalAccessToken;

/** @mixin PersonalAccessToken */
class ApiTokenResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'abilities' => $this->abilities,
            'last_used_at' => $this->iso($this->last_used_at),
            'expires_at' => $this->iso($this->expires_at),
            'created_at' => $this->iso($this->created_at),
        ];
    }

    private function iso(mixed $value): ?string
    {
        return $value instanceof DateTimeInterface ? $value->format('c') : null;
    }
}
