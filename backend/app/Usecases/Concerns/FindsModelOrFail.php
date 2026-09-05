<?php

namespace App\Usecases\Concerns;

use App\Exceptions\NotFoundException;
use Illuminate\Database\Eloquent\Model;

trait FindsModelOrFail
{
    /**
     * @template TModel of Model
     *
     * @param  class-string<TModel>  $modelClass
     * @return TModel
     */
    private function findOrFail(string $modelClass, int $id, ?string $message = null): Model
    {
        /** @var TModel|null $model */
        $model = $modelClass::find($id);

        if ($model === null) {
            throw new NotFoundException($message);
        }

        return $model;
    }
}
