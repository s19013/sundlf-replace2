<?php

namespace App\Usecases\Concerns;

use App\Exceptions\NotFoundException;
use App\Models\Entry;
use App\Models\Tag;

trait AssertOwner
{
    private function assertOwner(Entry|Tag $model, int $userId, string $message): void
    {
        if (! $model->isOwner((string) $userId)) {
            throw new NotFoundException($message);
        }
    }
}
