<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * 既存行のedited_atに、そのときの行の更新日時(updated_at)を写す。
     * あわせて、statusテーブルのhas_tagsを本体へ写す。status行が無い本体は、タグなし検索での扱いに合わせてfalseにする。
     * ゴミ箱内の行も対象にし、エントリーの編集ではないためupdated_atは変えない。
     * statusテーブル側のカラムは、読み書きを本体へ切り替えた後に別のマイグレーションで削除する。
     */
    public function up(): void
    {
        DB::table('articles')->update([
            'edited_at' => DB::raw('updated_at'),
            'has_tags' => DB::raw('COALESCE((SELECT article_statuses.has_tags FROM article_statuses WHERE article_statuses.article_id = articles.id), 0)'),
        ]);

        DB::table('book_marks')->update([
            'edited_at' => DB::raw('updated_at'),
            'has_tags' => DB::raw('COALESCE((SELECT book_mark_statuses.has_tags FROM book_mark_statuses WHERE book_mark_statuses.book_mark_id = book_marks.id), 0)'),
        ]);

        DB::table('tags')->update([
            'edited_at' => DB::raw('updated_at'),
        ]);
    }

    /**
     * 本体のhas_tagsをstatusテーブルへ書き戻す。
     * statusテーブルのカラムは、後続のマイグレーションのdownで先に復元されている。
     * 本体のカラムは、先行のマイグレーション(2026_11_04_092911)のdownで削除する。
     */
    public function down(): void
    {
        DB::table('article_statuses')->update([
            'has_tags' => DB::raw('(SELECT articles.has_tags FROM articles WHERE articles.id = article_statuses.article_id)'),
        ]);

        DB::table('book_mark_statuses')->update([
            'has_tags' => DB::raw('(SELECT book_marks.has_tags FROM book_marks WHERE book_marks.id = book_mark_statuses.book_mark_id)'),
        ]);
    }
};
