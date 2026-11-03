<?php

namespace App\Usecases\Tag;

use App\Exceptions\DuplicationException;
use App\Facades\Authenticated;
use App\Http\Requests\Tag\CreateTagRequest;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class CreateTagUsecase
{
    public function __invoke(CreateTagRequest $request): JsonResponse
    {
        $user = Authenticated::user();

        $name = $request->string('name')->toString();

        $this->ensureNotExists($user, $name);

        try {
            // createdイベントでstatus行も作成するため、同じトランザクションにまとめる
            DB::transaction(fn () => $user->tags()->create(['name' => $name]));
        } catch (UniqueConstraintViolationException) {
            throw new DuplicationException($name);
        }

        return response()->json([
            'messages' => ["{$name}を登録しました。"],
        ]);
    }

    private function ensureNotExists(User $user, string $name): void
    {
        $duplicated = $user->tags()
            ->where('name', $name)
            ->exists();

        if ($duplicated) {
            throw new DuplicationException($name);
        }
    }
}
