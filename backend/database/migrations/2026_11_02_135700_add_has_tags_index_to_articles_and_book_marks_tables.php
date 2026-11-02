<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * タグなし検索(has_tags = false)をデフォルトの並び順(updated_at降順)のままインデックスで絞り込み・ソートするための複合インデックス。
     * ゴミ箱以外の一覧は`deleted_at IS NULL`で絞り込むため、updated_atの前にdeleted_atを含める。
     * 並び順の第2キーのidは、InnoDBがセカンダリインデックスの末尾に主キーとして持つため含めない。
     *
     * 先頭列がuser_idなので外部キーにも使え、user_id単体のインデックスは不要になるため削除する。
     * 環境によってはMySQLが外部キー用に自動作成したインデックスしか無く、それは複合インデックスの追加時にMySQLが自動で削除する。
     */
    public function up(): void
    {
        foreach (['articles', 'book_marks'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->index(['user_id', 'has_tags', 'deleted_at', 'updated_at']);
            });

            if (Schema::hasIndex($tableName, "{$tableName}_user_id_index")) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->dropIndex(['user_id']);
                });
            }
        }
    }

    /**
     * 複合インデックスを削除する。
     * 外部キーにはuser_idのインデックスが必須なため、先にuser_id単体のインデックスを作り直す。
     */
    public function down(): void
    {
        foreach (['articles', 'book_marks'] as $tableName) {
            if (! Schema::hasIndex($tableName, "{$tableName}_user_id_index")) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->index(['user_id']);
                });
            }

            Schema::table($tableName, function (Blueprint $table) {
                $table->dropIndex(['user_id', 'has_tags', 'deleted_at', 'updated_at']);
            });
        }
    }
};
