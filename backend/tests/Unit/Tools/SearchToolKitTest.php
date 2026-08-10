<?php

namespace Tests\Unit\Tools;

use App\Tools\SearchToolKit;
use PHPUnit\Framework\TestCase;

class SearchToolKitTest extends TestCase
{
    public function test_splits_words_by_half_width_space_into_and_words(): void
    {
        $result = SearchToolKit::parseSearchQuery('フルーツ 酸っぱい');

        $this->assertSame(['and' => ['フルーツ', '酸っぱい'], 'not' => []], $result);
    }

    public function test_word_prefixed_with_half_width_hyphen_becomes_not_word(): void
    {
        $result = SearchToolKit::parseSearchQuery('フルーツ 酸っぱい -フルーツポンチ');

        $this->assertSame(['and' => ['フルーツ', '酸っぱい'], 'not' => ['フルーツポンチ']], $result);
    }

    public function test_word_prefixed_with_full_width_hyphen_becomes_not_word(): void
    {
        $result = SearchToolKit::parseSearchQuery('－マイナス');

        $this->assertSame(['and' => [], 'not' => ['マイナス']], $result);
    }

    public function test_single_hyphen_is_treated_as_and_word(): void
    {
        $result = SearchToolKit::parseSearchQuery('-');

        $this->assertSame(['and' => ['-'], 'not' => []], $result);
    }

    public function test_full_width_spaces_and_commas_are_treated_as_delimiters(): void
    {
        $result = SearchToolKit::parseSearchQuery('　全角　スペース　');

        $this->assertSame(['and' => ['全角', 'スペース'], 'not' => []], $result);

        $result = SearchToolKit::parseSearchQuery('a,b,c');

        $this->assertSame(['and' => ['a', 'b', 'c'], 'not' => []], $result);
    }

    public function test_blank_or_empty_keyword_returns_empty_lists(): void
    {
        $this->assertSame(['and' => [], 'not' => []], SearchToolKit::parseSearchQuery('  '));
        $this->assertSame(['and' => [], 'not' => []], SearchToolKit::parseSearchQuery(''));
    }

    public function test_percent_and_underscore_are_escaped_for_like_search(): void
    {
        $result = SearchToolKit::parseSearchQuery('A_B 50%OFF');

        $this->assertSame(['and' => ['A\_B', '50\%OFF'], 'not' => []], $result);
    }

    public function test_backslash_is_escaped_before_percent_and_underscore(): void
    {
        // バックスラッシュを先にエスケープしないと、_ や % のエスケープと衝突してLIKE検索の意味が変わってしまう
        $result = SearchToolKit::parseSearchQuery('back\\slash_x');

        $this->assertSame(['and' => ['back\\\\slash\_x'], 'not' => []], $result);
    }
}
