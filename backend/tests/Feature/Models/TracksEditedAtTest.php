<?php

namespace Tests\Feature\Models;

use App\Models\Concerns\TracksEditedAt;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TracksEditedAtTest extends TestCase
{
    use RefreshDatabase;

    private function createFixture(): TracksEditedAtFixture
    {
        $user = User::factory()->create();

        return TracksEditedAtFixture::create(['user_id' => $user->id, 'name' => '作成時の名前']);
    }

    private function fetchEditedAt(TracksEditedAtFixture $fixture): ?string
    {
        return $fixture->fresh()?->edited_at?->toIso8601String();
    }

    public function test_作成時はedited_atがcreated_atと同じ時刻になること(): void
    {
        $fixture = $this->createFixture();

        $fresh = $fixture->fresh();
        $this->assertNotNull($fresh?->edited_at);
        $this->assertSame($fresh->created_at?->toIso8601String(), $fresh->edited_at->toIso8601String());
    }

    public function test_作成時にedited_atを明示した場合はその値が使われること(): void
    {
        $user = User::factory()->create();
        $editedAt = Carbon::parse('2020-01-01 00:00:00');

        $fixture = TracksEditedAtFixture::create([
            'user_id' => $user->id,
            'name' => '名前',
            'edited_at' => $editedAt,
        ]);

        $this->assertSame($editedAt->toIso8601String(), $this->fetchEditedAt($fixture));
    }

    public function test_編集対象の属性が変わるとedited_atがupdated_atと同じ時刻になること(): void
    {
        $fixture = $this->createFixture();
        $beforeEditedAt = $this->fetchEditedAt($fixture);

        $this->travel(1)->minutes();
        $fixture->update(['name' => '新しい名前']);

        $fresh = $fixture->fresh();
        $this->assertNotSame($beforeEditedAt, $fresh?->edited_at?->toIso8601String());
        $this->assertSame($fresh?->updated_at?->toIso8601String(), $fresh?->edited_at?->toIso8601String());
    }

    public function test_編集対象外の属性だけが変わってもedited_atは変わらないこと(): void
    {
        $fixture = $this->createFixture();
        $beforeEditedAt = $this->fetchEditedAt($fixture);
        $beforeUpdatedAt = $fixture->updated_at?->toIso8601String();
        $otherUser = User::factory()->create();

        $this->travel(1)->minutes();
        $fixture->user_id = $otherUser->id;
        $fixture->save();

        $fresh = $fixture->fresh();
        $this->assertNotSame($beforeUpdatedAt, $fresh?->updated_at?->toIso8601String());
        $this->assertSame($beforeEditedAt, $fresh?->edited_at?->toIso8601String());
    }

    public function test_変更のない保存では何も書かないこと(): void
    {
        $fixture = $this->createFixture();
        $beforeEditedAt = $this->fetchEditedAt($fixture);
        $beforeUpdatedAt = $fixture->updated_at?->toIso8601String();

        $this->travel(1)->minutes();
        DB::enableQueryLog();
        $fixture->update(['name' => '作成時の名前']);
        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        $this->assertSame([], $queries);
        $fresh = $fixture->fresh();
        $this->assertSame($beforeUpdatedAt, $fresh?->updated_at?->toIso8601String());
        $this->assertSame($beforeEditedAt, $fresh?->edited_at?->toIso8601String());
    }

    public function test_mark_as_editedだけでもedited_atがupdated_atと同じ時刻になること(): void
    {
        $fixture = $this->createFixture();
        $beforeEditedAt = $this->fetchEditedAt($fixture);

        $this->travel(1)->minutes();
        $fixture->markAsEdited()->save();

        $fresh = $fixture->fresh();
        $this->assertNotSame($beforeEditedAt, $fresh?->edited_at?->toIso8601String());
        $this->assertSame($fresh?->updated_at?->toIso8601String(), $fresh?->edited_at?->toIso8601String());
    }

    public function test_mark_as_editedの印は保存後に消えること(): void
    {
        $fixture = $this->createFixture();
        $otherUser = User::factory()->create();

        $this->travel(1)->minutes();
        $fixture->markAsEdited()->save();
        $editedAtAfterMark = $this->fetchEditedAt($fixture);

        $this->travel(1)->minutes();
        $fixture->user_id = $otherUser->id;
        $fixture->save();

        $this->assertSame($editedAtAfterMark, $this->fetchEditedAt($fixture));
    }
}

/**
 * TracksEditedAtを単体で検証するためのモデル。tagsテーブルの`name`を編集対象とする。
 *
 * @property int $user_id
 * @property string $name
 * @property \Illuminate\Support\Carbon|null $edited_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class TracksEditedAtFixture extends Model
{
    use TracksEditedAt;

    protected $table = 'tags';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'name',
        'edited_at',
    ];

    /**
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
}
