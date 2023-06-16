<?php

namespace App\Http\Resources;

use App\Enums\PermissionNameEnum;
use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class FreelancerShiftResource extends JsonResource
{
    public static $wrap = null;

    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {

        if(\request('startDate') && \request('endDate')){

            $startDate = Carbon::create(\request('startDate'))->startOfDay();
            $endDate = Carbon::create(\request('endDate'))->endOfDay();

        }else{

            $startDate = Carbon::now()->startOfDay();
            $endDate = Carbon::now()->addWeeks()->endOfDay();

        }
        return [
            'resource' => class_basename($this),
            'id' => $this->id,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'profile_photo_url' => $this->profile_image,
            'shifts' => $this->getShiftsAttribute($startDate, $endDate),
        ];
    }
}
