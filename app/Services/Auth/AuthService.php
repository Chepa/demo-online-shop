<?php

namespace App\Services\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\PersonalAccessToken;

class AuthService
{
    public function registerUser(array $data): User
    {
        return User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);
    }

    public function loginUser(string $email, string $password): ?User
    {
        $user = User::where('email', $email)->first();

        if (!$user || !Hash::check($password, $user->password)) {
            return null;
        }

        Auth::login($user);

        return $user;
    }

    public function logoutUser(): void
    {
        Auth::guard('web')->logout();
        session()->invalidate();
        session()->regenerateToken();
    }

    public function getAuthenticatedUser(?string $bearerToken): ?User
    {
        $user = Auth::guard('web')->user();

        if (!$user && $bearerToken) {
            $token = PersonalAccessToken::findToken($bearerToken);

            if ($token) {
                $user = $token->tokenable;
            }
        }

        return $user;
    }
}
