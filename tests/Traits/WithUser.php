<?php

declare(strict_types=1);

namespace Tests\Traits;

use App\Models\User;
use Database\Factories\UserFactory;

trait WithUser
{
    public User $user;
    public string $token;

    public function withUser(): User
    {
        $this->user = $this->user ?? $this->createUser();

        return $this->user;
    }

    public function createUser(): User
    {
        return UserFactory::new()->create();
    }

    public function withToken(): string
    {
        $this->token = $this->token ?? $this->createToken();

        return $this->token;
    }

    public function createToken(): string
    {
        $token = $this->user->createToken('Public API Key', ['use-public-api']);

        return $token->plainTextToken;
    }
}
