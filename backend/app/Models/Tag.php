<?php

namespace App\Models;

use App\Models\Concerns\HasOwner;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property int $id
 * @property string $name
 * @property int $user_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\TagStatus|null $status
 */
class Tag extends Model
{
    /** @use HasFactory<\Database\Factories\TagFactory> */
    use HasFactory, HasOwner;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'name',
    ];

    /**
     * 作成時に、同じトランザクション内でstatus行も作成する。
     */
    protected static function booted(): void
    {
        static::created(function (Tag $tag): void {
            $tag->status()->create();
        });
    }

    /**
     * ユーザーが直接変更しない項目(使用回数)を管理するstatus行。
     *
     * @return HasOne<TagStatus, $this>
     */
    public function status(): HasOne
    {
        return $this->hasOne(TagStatus::class);
    }
}
