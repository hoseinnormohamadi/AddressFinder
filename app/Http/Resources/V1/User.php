<?php

namespace App\Http\Resources\v1;

use Illuminate\Http\Resources\Json\JsonResource;

class User extends JsonResource
{
    public $token;
    public function __construct($resource , $token)
    {
        $this->token = $token;
        parent::__construct($resource);
    }

    public function toArray($request)
    {
        return [
            'name' => $this->name,
            'email' => $this->email,
            'api_token' => $this->token
        ];
    }
    public function with($request)
    {
        return [
            'Status' => 'Success'
        ];
    }
}
