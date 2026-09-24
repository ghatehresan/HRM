<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;

class UserController extends Controller
{
    public function me(): UserResource
    {
        return new UserResource($this->authedUser());
    }
}
