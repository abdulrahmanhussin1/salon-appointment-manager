<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AppointmentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->id ,

            'title' => $this->customer->name . " (".$this->provider->name .")",
            'start' => Carbon::createFromFormat('Y-m-d H:i:s', $this->start_date )
            ->setTimezone('UTC')
            ->toIso8601String() ,
            'end' => Carbon::createFromFormat('Y-m-d H:i:s', $this->end_date )
            ->setTimezone('UTC')
            ->toIso8601String() ,

            'customer_id' => $this->customer_id ,
            'provider_id' => $this->provider_id ,
            'service_id' => $this->service_id ,

            'customer' => $this->customer->name ,
            'provider' => $this->provider->name ,
            'service' => $this->service->name ,

            'start_date' => Carbon::parse($this->start_date)->format('Y-m-d\TH:i')  ,
            'end_date' => Carbon::parse($this->end_date)->format('Y-m-d\TH:i')  ,


        ];
    }
}
