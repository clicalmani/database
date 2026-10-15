<?php

declare(strict_types=1);

namespace Database\Test\Feature\Relations;

use Database\Test\Feature\DatabaseTestCase;
use Database\Test\Feature\Fixtures\Models\Image;
use Database\Test\Feature\Fixtures\Models\User;

final class MorphOneTest extends DatabaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->seedDatabase();
        $this->seedAdvancedDatabase();
    }

    public function test_morph_one_returns_single_image(): void
    {
        $user = User::find(1);
        $avatar = $user->avatar;

        // User 1 a 2 images, mais morphOne retourne la 1ère
        $this->assertInstanceOf(Image::class, $avatar);
        $this->assertSame((int) $user->id, (int) $avatar->imageable_id);
    }

    public function test_morph_one_filters_by_type(): void
    {
        $user = User::find(1);
        $avatar = $user->avatar;

        $this->assertSame(User::class, $avatar->imageable_type);
    }

    public function test_morph_one_returns_null_when_no_match(): void
    {
        // User 5 n'a pas d'image
        $user = User::find(5);
        $avatar = $user->avatar;

        $this->assertTrue($avatar === null || $avatar->id === null);
    }
}