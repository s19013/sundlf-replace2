<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;
use Throwable;

class OriginalException extends Exception
{
    // message → 人間向けメッセージ
    // statusCode → HTTPステータス
    // previous → 元になったException
    // errorCode → フロント側などが判定するアプリ独自コード

    public function __construct(
        string $message = 'エラーが発生しました。',
        private readonly int $statusCode = 400,
        ?Throwable $previous = null,
        int $errorCode = 0,
    ) {
        parent::__construct($message, $errorCode, $previous);
    }

    public function render(): JsonResponse
    {
        return response()->json([
            'messages' => [$this->getMessage()],
        ], $this->statusCode);
    }
}
