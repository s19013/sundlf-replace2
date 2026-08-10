<?php

namespace App\Tools;

class SearchToolKit
{
    /**
     * @return array{and: array<string>, not: array<string>}
     */
    public static function parseSearchQuery(string $keyword): array
    {
        $escaped = self::sqlEscape($keyword);
        $terms = self::splitBySpaces($escaped);

        return self::classifyTerms($terms);
    }

    /**sqlでlike検索する前にするエスケープ処理 */
    private static function sqlEscape(string $keyword): string
    {
        // エスケープ文字自体（\）を先にエスケープ
        $escaped = preg_replace(
            '/\\\\/',
            '\\\\\\\\',
            $keyword) ?? $keyword;

        // %をエスケープ
        $escaped = preg_replace(
            '/%/',
            '\%',
            $escaped) ?? $escaped;

        // _をエスケープ
        $escaped = preg_replace(
            '/_/',
            '\_',
            $escaped) ?? $escaped;

        return $escaped;
    }

    /**
     * and検索できるように空白で区切って､配列にする
     *
     * @return array<string>
     */
    private static function splitBySpaces(string $escaped): array
    {
        // 前後の余分な空白を取り除き、単語間にある半角/全角混在の連続空白を1つの半角スペースに統一する
        $trimmed = trim($escaped);
        $normalized = preg_replace('/[\s　]+/u', ' ', $trimmed) ?? $trimmed;

        // 単語を半角スペースで区切り、配列にする
        return preg_split('/[\s,]+/', $normalized, -1, PREG_SPLIT_NO_EMPTY) ?: [];
    }

    /**
     * 検索語の配列を「アンド検索用」「マイナス検索用」の配列に分割する
     *
     * @param  array<string>  $terms  例: ["フルーツ", "酸っぱい", "-フルーツポンチ"]
     * @return array{and: array<string>, not: array<string>}
     */
    private static function classifyTerms(array $terms): array
    {

        $andWords = [];
        $notWords = [];

        foreach ($terms as $term) {
            // 「-」または全角マイナス「－」で始まる場合はマイナス検索
            if (preg_match('/^[\-－](.+)$/u', $term, $matches)) {
                $notWords[] = $matches[1];
            } else {
                $andWords[] = $term;
            }
        }

        return [
            'and' => $andWords,
            'not' => $notWords,
        ];
    }
}
