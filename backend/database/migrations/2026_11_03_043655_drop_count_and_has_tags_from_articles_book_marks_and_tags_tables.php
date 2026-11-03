<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * statusテーブルへ移した`count`・`has_tags`を本体から削除する。
     *
     * has_tagsを含む複合インデックスは使えなくなるため、has_tagsを除いた複合インデックスに作り直す。
     * 外部キーにはuser_idを先頭に持つインデックスが必須なため、新しいインデックスを先に作ってから古いものを削除する。
     * 並び順の第2キーのidは、InnoDBがセカンダリインデックスの末尾に主キーとして持つため含めない。
     */
    public function up(): void
    {
        foreach (['articles', 'book_marks'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->index(['user_id', 'deleted_at', 'updated_at']);
            });

            Schema::table($tableName, function (Blueprint $table) {
                $table->dropIndex(['user_id', 'has_tags', 'deleted_at', 'updated_at']);
            });

            Schema::table($tableName, function (Blueprint $table) {
                $table->dropColumn(['count', 'has_tags']);
            });
        }

        Schema::table('tags', function (Blueprint $table) {
            $table->dropColumn('count');
        });
    }

    /**
     * カラムとインデックスを元に戻す。値はstatusテーブルからコピーするマイグレーション(2026_11_03_042851)のdownで書き戻す。
     */
    public function down(): void
    {
        Schema::table('tags', function (Blueprint $table) {
            $table->unsignedBigInteger('count')->default(0)->after('user_id');
        });

        // has_tagsは元の位置(本文・URLの後ろ)に戻す
        $hasTagsAfterColumns = [
            'articles' => 'body',
            'book_marks' => 'url',
        ];

        foreach ($hasTagsAfterColumns as $tableName => $hasTagsAfter) {
            Schema::table($tableName, function (Blueprint $table) use ($hasTagsAfter) {
                $table->unsignedBigInteger('count')->default(0)->after('user_id');
                $table->boolean('has_tags')->default(false)->after($hasTagsAfter);
            });

            Schema::table($tableName, function (Blueprint $table) {
                $table->index(['user_id', 'has_tags', 'deleted_at', 'updated_at']);
            });

            Schema::table($tableName, function (Blueprint $table) {
                $table->dropIndex(['user_id', 'deleted_at', 'updated_at']);
            });
        }
    }
};
