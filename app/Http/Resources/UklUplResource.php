<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class UklUplResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return [
            'name' => $this->user->name,
            'date' => $this->created_at,
            'comment' => $this->comment
        ];
    }
}
