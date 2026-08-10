<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Model;

/**
 * @phpstan-require-extends Model
 *
 * @property int $user_id
 */
trait HasOwner
{
    public function isOwner(string $userId): bool
    {
        return (string) $this->user_id === $userId;
    }
}
