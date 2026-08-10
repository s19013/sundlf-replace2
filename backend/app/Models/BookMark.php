<?php

namespace App\Models;

use App\Models\Concerns\Trashable;
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
 * @property string $url
 * @property bool $has_tags
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read bool $is_deadline_approaching
 * @property-read bool $is_in_trash
 * @property-read \App\Models\BookMarkTag|null $pivot
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Tag> $tags
 * @property-read int|null $tags_count
 */
class BookMark extends Model
{
    /** @use HasFactory<\Database\Factories\BookMarkFactory> */
    use HasFactory, SoftDeletes, Trashable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'title',
        'url',
        'star',
    ];

    /**
     * @return BelongsToMany<Tag, $this, BookMarkTag>
     */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'book_mark_tags', 'book_mark_id', 'tag_id')
            ->using(BookMarkTag::class)
            ->withTimestamps();
    }
}
