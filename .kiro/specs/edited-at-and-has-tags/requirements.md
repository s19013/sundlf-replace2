# Requirements Document

## Project Description (Input)
メモ・ブックマークの updated_at の意味を「行の更新日時」(Laravel標準)に戻し、ユーザーが編集した日時を表す edited_at を新設する。並べ替え・期間絞り込み・楽観的排他制御・画面表示は edited_at を使う。あわせて has_tags を status テーブルから本体(articles / book_marks)へ戻し、本体の複合インデックス (user_id, has_tags, deleted_at, edited_at) でタグなし検索を行う。count は status テーブルに残す。背景: #127 で has_tags を status テーブルへ分離した結果、タグなし検索を速くするために status テーブルへ user_id の写しを持たせる必要が出た(#131、マージせず破棄)。一方、has_tags を本体に置くと、タグ削除で has_tags が変わったときに updated_at を更新しない特別扱いが必要になり、実際は行を更新しているのに updated_at が変わらないという違和感がある。日時の意味を分けることで両方を解消したい。API の updated_at という名前(sort=updated_at、updated_at_range_* を含む)を残すか edited_at に改名するかは未決。関連 issue: #118、#122、#127、#131、#134

## Requirements
<!-- Will be generated in /kiro:spec-requirements phase -->
