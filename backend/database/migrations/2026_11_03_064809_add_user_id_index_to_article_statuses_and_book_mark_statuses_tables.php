<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 値をコピーし終えた`user_id`をNOT NULLにし、タグなし検索用の複合インデックスを作成する。
     *
     * `(user_id, has_tags)`で、ユーザーのタグなしの行を直接引ける。
     * 本体のidは、InnoDBがセカンダリインデックスの末尾に主キーとして持つため含めない。
     */
    public function up(): void
    {
        foreach (['article_statuses', 'book_mark_statuses'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->unsignedBigInteger('user_id')->nullable(false)->change();
                $table->index(['user_id', 'has_tags']);
            });
        }
    }

    /**
     * インデックスを削除し、`user_id`をnullableに戻す。
     */
    public function down(): void
    {
        foreach (['article_statuses', 'book_mark_statuses'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropIndex(['user_id', 'has_tags']);
                $table->unsignedBigInteger('user_id')->nullable()->change();
            });
        }
    }
};
