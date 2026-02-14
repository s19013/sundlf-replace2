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

files=()
for f in "$@"; do
  files+=("${f/#frontend\//./}")
done

# 配列のまま引数として渡す
mise exec:vue "pnpm run format  $(printf '%q ' "${files[@]}")"

# フォーマットで変わった分を確実にステージ
git add -- "$@"
