#!/usr/bin/env bash

# bashで実行

# | オプション         | 意味                 |
# | ------------- | ------------------ |
# | `-e`          | コマンドが1つでも失敗したら即終了  |
# | `-u`          | 未定義変数を使ったらエラー      |
# | `-o pipefail` | パイプの途中で失敗してもエラーにする |

set -euo pipefail

# lefthook から渡された staged_files が $@ に入る
# 引数でファイルが渡されなかったら即終了
if [[ $# -eq 0 ]]; then
  exit 0
fi

# /backend/パスと渡されるので ./パス となるように編集
# 配列 files に格納
files=()
for f in "$@"; do
files+="${f/#backend\//./}"
done

echo $files

# ${files[@]} Bash 配列を 安全に展開
mise exec:laravel "composer run pint ${files[@]}"
