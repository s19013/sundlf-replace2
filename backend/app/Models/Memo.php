<?php

namespace App\Models;

use App\Models\Concerns\HasOwner;
use App\Models\Concerns\HasTrashDeadline;
use App\Models\Concerns\HasViewCount;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int $user_id
 * @property int $count
 * @property int $star
 * @property string $title
 * @property string $body
 * @property bool $has_tags
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read bool $is_deadline_approaching
 * @property-read bool $is_in_trash
 * @property-read \App\Models\MemoTag|null $pivot
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Tag> $tags
 * @property-read int|null $tags_count
 */
class Memo extends Model
{
    /** @use HasFactory<\Database\Factories\MemoFactory> */
    use HasFactory, HasOwner, HasTrashDeadline, HasViewCount, SoftDeletes;

    /**
     * 旧システムでは`article`という名前だった名残でテーブル名がクラス名の規約と一致しない。
     */
    protected $table = 'articles';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'title',
        'body',
        'star',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'count' => 'integer',
            'star' => 'integer',
            'has_tags' => 'boolean',
        ];
    }

    /**
     * @return BelongsToMany<Tag, $this, MemoTag>
     */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'article_tags', 'article_id', 'tag_id')
            ->using(MemoTag::class)
            ->withTimestamps();
    }
}
