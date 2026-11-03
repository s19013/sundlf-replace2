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
     * コピーしたstatus行を削除する。
     */
    public function down(): void
    {
        DB::table('tag_statuses')->delete();
        DB::table('book_mark_statuses')->delete();
        DB::table('article_statuses')->delete();
    }
};
