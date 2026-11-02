<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * タグなし検索(has_tags = false)をデフォルトの並び順(updated_at降順)のままインデックスで絞り込み・ソートするための複合インデックス。
     */
    public function up(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            $table->index(['user_id', 'has_tags', 'updated_at']);
        });

        Schema::table('book_marks', function (Blueprint $table) {
            $table->index(['user_id', 'has_tags', 'updated_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'has_tags', 'updated_at']);
        });

        Schema::table('book_marks', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'has_tags', 'updated_at']);
        });
    }
};
