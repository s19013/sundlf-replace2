<?php

namespace Database\Factories;

use App\Models\Tag;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Tag>
 */
class TagFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => fake()->unique()->word(),
        ];
    }

    /**
     * 使用回数(`tag_statuses.count`)を設定する。status行はタグ作成時のイベントで作られるため、作成後に更新する。
     */
    public function usageCount(int $count): static
    {
        return $this->afterCreating(function (Tag $tag) use ($count): void {
            $tag->status()->update(['count' => $count]);
        });
    }
}
