<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * ユーザーが直接変更しない`count`・`has_tags`を管理するstatusテーブルを作成する。
     * 本体1件につき1行(1対1)なので、本体のidを主キーにする。
     */
    public function up(): void
    {
        Schema::create('article_statuses', function (Blueprint $table) {
            $table->foreignId('article_id')->primary()->constrained()->cascadeOnDelete();
            $table->boolean('has_tags')->default(false);
            $table->unsignedBigInteger('count')->default(0);
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('book_mark_statuses', function (Blueprint $table) {
            $table->foreignId('book_mark_id')->primary()->constrained()->cascadeOnDelete();
            $table->boolean('has_tags')->default(false);
            $table->unsignedBigInteger('count')->default(0);
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('tag_statuses', function (Blueprint $table) {
            $table->foreignId('tag_id')->primary()->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('count')->default(0);
            $table->timestamp('created_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tag_statuses');
        Schema::dropIfExists('book_mark_statuses');
        Schema::dropIfExists('article_statuses');
    }
};
