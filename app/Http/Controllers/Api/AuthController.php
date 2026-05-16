<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\UpdateProfileRequest;
use App\Services\AuthService;

class AuthController extends Controller
{
    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }
    public function login(LoginRequest $request)
    {
        return $this->authService->login($request);
    }

    public function logout()
    {
        return $this->authService->logout();
    }

    public function me()
    {
        return $this->authService->me();
    }

    public function update(UpdateProfileRequest $request)
    {
        return $this->authService->update($request);
    }


}
