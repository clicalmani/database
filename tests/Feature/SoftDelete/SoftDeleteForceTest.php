<?php

declare(strict_types=1);

namespace Database\Test\Feature\SoftDelete;

use Database\Test\Feature\DatabaseTestCase;
use Database\Test\Feature\Fixtures\Models\SoftUser;

final class SoftDeleteForceTest extends DatabaseTestCase
{
    public function test_force_delete_removes_row_permanently(): void
    {
        $this->seedDatabase();

        $user = SoftUser::find(1);
        $user->forceDelete();

        // La ligne n'existe plus, même avec trashed
        $this->assertSame(4, $this->countRows('soft_users'));

        $found = SoftUser::withTrashed()->find(1);
        $this->assertNull($found);
    }

    public function test_force_delete_on_already_trashed(): void
    {
        $this->seedDatabase();

        $user = SoftUser::find(1);
        $user->delete();

        $trashed = SoftUser::withTrashed()->find(1);
        $trashed->forceDelete();

        $this->assertSame(4, $this->countRows('soft_users'));
    }

    public function test_force_delete_without_where_throws(): void
    {
        $this->seedDatabase();

        $this->expectException(\Clicalmani\Core\Exceptions\ModelException::class);

        SoftUser::forceDelete();
    }

    public function test_force_delete_with_where(): void
    {
        $this->seedDatabase();

        $success = SoftUser::where('id = ?', [1])->forceDelete();

        $this->assertTrue($success);
        $this->assertSame(4, $this->countRows('soft_users'));
    }
}