<?php

declare(strict_types=1);

namespace Tests\Traits;

use App\Models\User;
use Database\Factories\UserFactory;

trait WithUser
{
    public User $user;

    public function withUser(): User
    {
        $this->user = $this->user ?? $this->createUser();

        return $this->user;
    }

    public function createUser(): User
    {
        return UserFactory::new()->create();
    }
}
