<?php

namespace App\Usecases\Concerns;

use App\Exceptions\DuplicationException;
use App\Models\User;

trait EnsureNotExists
{
    private function ensureTagNotExists(User $user, string $newName, int $id): void
    {
        $duplicated = $user->tags()
            ->where('name', $newName)
            ->where('id', '!=', $id)
            ->exists();

        if ($duplicated) {
            throw new DuplicationException($newName);
        }
    }
}
