<?php

namespace App\Usecases\Concerns;

use App\Exceptions\ForbiddenException;
use App\Models\Entry;
use App\Models\Tag;

trait AssertOwner
{
    private function assertOwner(Entry|Tag $model, int $userId, string $message): void
    {
        if (! $model->isOwner((string) $userId)) {
            throw new ForbiddenException($message);
        }
    }
}
