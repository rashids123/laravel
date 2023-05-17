<?php

namespace App\Http\Resources;

use App\Http\Controllers\ApiController;

use App\Models\AlertPathway;
use App\Http\Resources\AlertPathwayEventResource as EventResource;

use App\Models\ProgramPathway;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;

class AlertPathwayResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     * @param  \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request)
    {
        $alertableType = ProgramPathway::ALERTABLE_TYPES[$this->alertable_type];

        $data = [
            'id'                    => (int) $this->id,
            'alertable'             => $this->alertable,
            'alertable_type'        => $alertableType,
            'alertable_name'        => ProgramPathway::pathwayName($alertableType),
            'alertable_description' => ProgramPathway::pathwayDescription($alertableType),
            'user'           => $this->user,
            'events'         => EventResource::collection($this->alertPathwayEvents),
            'started_at'     => $this->started_at,
            'created_at'     => $this->created_at->format(ApiController::TIMESTAMP_FORMAT),
            'updated_at'     => $this->updated_at->format(ApiController::TIMESTAMP_FORMAT),
            'deleted_at'     => null,
            'resolution_time_minutes' => null
        ];

        if ($this->deleted_at) {
            $data['deleted_at'] = $this->deleted_at->format(ApiController::TIMESTAMP_FORMAT);

            $to = Carbon::createFromFormat('Y-m-d H:i:s', $this->created_at);
            $from = Carbon::createFromFormat('Y-m-d H:i:s', $this->deleted_at);

            $data['resolution_time_minutes'] = $to->diffInMinutes($from);
        }

        return $data;
    }
}
