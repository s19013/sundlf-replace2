<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * edited_atの新設とhas_tagsの本体への戻しの仕上げ。
     *
     * - 既存行のedited_atは先行のマイグレーション(2026_11_04_093018)で埋めているため、NOT NULLにする。
     * - 一覧・タグなし検索の並び順(edited_at降順)をインデックスで処理できるよう、updated_atの複合インデックスをedited_atに作り直す。
     *   外部キーにはuser_idを先頭に持つインデックスが必須なため、新しいインデックスを先に作ってから古いものを削除する。
     *   並び順の第2キーのidは、InnoDBがセカンダリインデックスの末尾に主キーとして持つため含めない。
     * - タグ一覧の既定の並び順(edited_at降順)用に、tagsにも(user_id, edited_at)のインデックスを作る。
     * - アプリは本体のhas_tagsを読み書きするようになったため、statusテーブルのhas_tagsを削除する。
     */
    public function up(): void
    {
        foreach (['articles', 'book_marks', 'tags'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->timestamp('edited_at')->nullable(false)->change();
            });
        }

        foreach (['articles', 'book_marks'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->index(['user_id', 'deleted_at', 'edited_at']);
                $table->index(['user_id', 'has_tags', 'deleted_at', 'edited_at']);
            });

            Schema::table($tableName, function (Blueprint $table) {
                $table->dropIndex(['user_id', 'deleted_at', 'updated_at']);
            });
        }

        Schema::table('tags', function (Blueprint $table) {
            $table->index(['user_id', 'edited_at']);
        });

        foreach (['article_statuses', 'book_mark_statuses'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropColumn('has_tags');
            });
        }
    }

    /**
     * statusテーブルのhas_tags・旧インデックス・nullableのedited_atに戻す。
     * statusテーブルのhas_tagsの値は、先行のマイグレーション(2026_11_04_093018)のdownで本体から書き戻す。
     */
    public function down(): void
    {
        // has_tagsは元の位置(本体のidの後ろ)に戻す
        $hasTagsAfterColumns = [
            'article_statuses' => 'article_id',
            'book_mark_statuses' => 'book_mark_id',
        ];

        foreach ($hasTagsAfterColumns as $tableName => $hasTagsAfter) {
            Schema::table($tableName, function (Blueprint $table) use ($hasTagsAfter) {
                $table->boolean('has_tags')->default(false)->after($hasTagsAfter);
            });
        }

        foreach (['articles', 'book_marks'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->index(['user_id', 'deleted_at', 'updated_at']);
            });

            Schema::table($tableName, function (Blueprint $table) {
                $table->dropIndex(['user_id', 'has_tags', 'deleted_at', 'edited_at']);
                $table->dropIndex(['user_id', 'deleted_at', 'edited_at']);
            });
        }

        Schema::table('tags', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'edited_at']);
        });

        foreach (['articles', 'book_marks', 'tags'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->timestamp('edited_at')->nullable()->change();
            });
        }
    }
};
