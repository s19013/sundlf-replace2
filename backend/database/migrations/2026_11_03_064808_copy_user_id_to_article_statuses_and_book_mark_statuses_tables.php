<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * statusテーブルの`user_id`に本体の`user_id`をコピーする。ゴミ箱内の行も対象にする。
     */
    public function up(): void
    {
        DB::table('article_statuses')->update([
            'user_id' => DB::raw('(SELECT articles.user_id FROM articles WHERE articles.id = article_statuses.article_id)'),
        ]);

        DB::table('book_mark_statuses')->update([
            'user_id' => DB::raw('(SELECT book_marks.user_id FROM book_marks WHERE book_marks.id = book_mark_statuses.book_mark_id)'),
        ]);
    }

    /**
     * 何もしない。`user_id`は前のマイグレーション(2026_11_03_064738)のdownでカラムごと削除される。
     */
    public function down(): void
    {
        //
    }
};
