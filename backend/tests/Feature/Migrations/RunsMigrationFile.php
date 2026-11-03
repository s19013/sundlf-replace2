<?php

namespace Tests\Feature\Migrations;

use Illuminate\Database\Migrations\Migration;

/**
 * マイグレーションファイルを読み込み、`up()`・`down()`を直接呼び出す。
 */
trait RunsMigrationFile
{
    /**
     * @param  string  $name  マイグレーションのファイル名(拡張子なし)
     * @param  'up'|'down'  $method
     */
    private function runMigration(string $name, string $method): void
    {
        $migration = require database_path("migrations/{$name}.php");
        assert($migration instanceof Migration && method_exists($migration, $method));
        $migration->{$method}();
    }
}
