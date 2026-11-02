<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * タグなし検索をhas_tagsで行うようになる前は、アプリ本体がhas_tagsを更新していなかったため、
     * 既存行のhas_tagsを中間テーブルの紐付けから再計算する。
     * ゴミ箱内の行も対象にし、エントリーの編集ではないためupdated_atは変えない。
     */
    public function up(): void
    {
        $pivotTables = [
            'articles' => ['article_tags', 'article_id'],
            'book_marks' => ['book_mark_tags', 'book_mark_id'],
        ];

        foreach ($pivotTables as $tableName => [$pivotTableName, $foreignKey]) {
            DB::table($tableName)->update([
                'has_tags' => DB::raw("EXISTS (SELECT 1 FROM {$pivotTableName} WHERE {$pivotTableName}.{$foreignKey} = {$tableName}.id)"),
            ]);
        }
    }

    /**
     * 補正前の誤った値には戻せないため、何もしない。
     */
    public function down(): void
    {
        //
    }
};
