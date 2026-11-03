<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * タグなし検索をstatus側のインデックスで絞り込めるよう、本体の`user_id`をstatusテーブルにも持たせる。
     *
     * 既存行の値は後続のマイグレーション(2026_11_03_064808)でコピーするため、ここではnullableで追加する。
     * 本体の写しなので外部キーは張らない。ユーザーが削除されたときは、本体のCASCADE経由でstatus行も削除される。
     * タグ検索はタグなしで絞り込まないため、`tag_statuses`には追加しない。
     */
    public function up(): void
    {
        $foreignKeys = [
            'article_statuses' => 'article_id',
            'book_mark_statuses' => 'book_mark_id',
        ];

        foreach ($foreignKeys as $tableName => $foreignKey) {
            Schema::table($tableName, function (Blueprint $table) use ($foreignKey) {
                $table->unsignedBigInteger('user_id')->nullable()->after($foreignKey);
            });
        }
    }

    /**
     * `user_id`を削除する。
     */
    public function down(): void
    {
        foreach (['article_statuses', 'book_mark_statuses'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropColumn('user_id');
            });
        }
    }
};
