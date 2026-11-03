<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * 本体の`count`・`has_tags`の値で、全行分のstatus行を作成する。
     * ゴミ箱内の行も対象にする。has_tagsは補正マイグレーション(2026_11_02_135800)で正しい値になっている。
     * 本体側のカラムは、読み書きをstatus側へ切り替えた後に別のマイグレーションで削除する。
     */
    public function up(): void
    {
        DB::table('article_statuses')->insertUsing(
            ['article_id', 'has_tags', 'count', 'created_at'],
            DB::table('articles')->select('id', 'has_tags', 'count', 'created_at'),
        );

        DB::table('book_mark_statuses')->insertUsing(
            ['book_mark_id', 'has_tags', 'count', 'created_at'],
            DB::table('book_marks')->select('id', 'has_tags', 'count', 'created_at'),
        );

        DB::table('tag_statuses')->insertUsing(
            ['tag_id', 'count', 'created_at'],
            DB::table('tags')->select('id', 'count', 'created_at'),
        );
    }

    /**
     * statusの値を本体へ書き戻してから、status行を削除する。
     * 本体側のカラムは、後続のマイグレーション(2026_11_03_043655)のdownで先に復元されている。
     */
    public function down(): void
    {
        DB::table('articles')->update([
            'count' => DB::raw('COALESCE((SELECT article_statuses.count FROM article_statuses WHERE article_statuses.article_id = articles.id), 0)'),
            'has_tags' => DB::raw('COALESCE((SELECT article_statuses.has_tags FROM article_statuses WHERE article_statuses.article_id = articles.id), 0)'),
        ]);

        DB::table('book_marks')->update([
            'count' => DB::raw('COALESCE((SELECT book_mark_statuses.count FROM book_mark_statuses WHERE book_mark_statuses.book_mark_id = book_marks.id), 0)'),
            'has_tags' => DB::raw('COALESCE((SELECT book_mark_statuses.has_tags FROM book_mark_statuses WHERE book_mark_statuses.book_mark_id = book_marks.id), 0)'),
        ]);

        DB::table('tags')->update([
            'count' => DB::raw('COALESCE((SELECT tag_statuses.count FROM tag_statuses WHERE tag_statuses.tag_id = tags.id), 0)'),
        ]);

        DB::table('tag_statuses')->delete();
        DB::table('book_mark_statuses')->delete();
        DB::table('article_statuses')->delete();
    }
};
