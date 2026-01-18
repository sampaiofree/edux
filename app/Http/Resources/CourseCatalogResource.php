<?php

namespace App\Http\Resources;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CourseCatalogResource extends JsonResource
{
    public function jsonOptions(): int
    {
        return JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;
    }

    public function withResponse(Request $request, JsonResponse $response): void
    {
        $response->header('Content-Type', 'application/json; charset=utf-8');
    }

    public function toArray(Request $request): array
    {
        return [
            'title' => $this->title,
            'slug' => $this->slug,
            'summary' => $this->summary,
            'description' => $this->description,
            'cover_image_path' => $this->coverImageUrl(),
            'promo_video_url' => $this->promo_video_url,
            'status' => $this->status,
            'duration_minutes' => $this->duration_minutes,
        ];
    }
}
