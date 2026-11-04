<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 行の更新日時(updated_at)とは別に、ユーザーが内容を編集した日時(edited_at)を追加する。
     * あわせて、タグなし検索を本体のインデックスだけで行うため、has_tagsを本体へ戻す。
     *
     * 既存行の値は後続のマイグレーションで写すため、edited_atはnullableで追加する。
     * この時点では、アプリは引き続きstatusテーブルのhas_tagsを使う。
     */
    public function up(): void
    {
        // has_tagsはstatusテーブルへ移す前の位置(本文・URLの後ろ)に戻す
        $hasTagsAfterColumns = [
            'articles' => 'body',
            'book_marks' => 'url',
        ];

        foreach ($hasTagsAfterColumns as $tableName => $hasTagsAfter) {
            Schema::table($tableName, function (Blueprint $table) use ($hasTagsAfter) {
                $table->boolean('has_tags')->default(false)->after($hasTagsAfter);
                $table->timestamp('edited_at')->nullable()->after('updated_at');
            });
        }

        Schema::table('tags', function (Blueprint $table) {
            $table->timestamp('edited_at')->nullable()->after('updated_at');
        });
    }

    /**
     * 追加したカラムを削除する。
     */
    public function down(): void
    {
        Schema::table('tags', function (Blueprint $table) {
            $table->dropColumn('edited_at');
        });

        foreach (['articles', 'book_marks'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropColumn(['has_tags', 'edited_at']);
            });
        }
    }
};
