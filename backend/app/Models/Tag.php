<?php

namespace App\Models;

use App\Models\Concerns\HasOwner;
use App\Models\Concerns\HasStatusRow;
use App\Models\Concerns\TracksEditedAt;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property int $id
 * @property string $name
 * @property int $user_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $edited_at
 * @property-read \App\Models\TagStatus|null $status
 */
class Tag extends Model
{
    /** @use HasFactory<\Database\Factories\TagFactory> */
    use HasFactory, HasOwner, HasStatusRow, TracksEditedAt;

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
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'edited_at' => 'datetime',
        ];
    }

    protected function editableAttributes(): array
    {
        return ['name'];
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
