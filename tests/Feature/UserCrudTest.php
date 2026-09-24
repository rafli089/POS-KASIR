<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserCrudTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $manager;
    private User $cashier;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin   = User::create(['name' => 'Admin',  'pin_hash' => '123456', 'role' => 'ADMIN']);
        $this->manager = User::create(['name' => 'Manager','pin_hash' => '123456', 'role' => 'MANAGER']);
        $this->cashier = User::create(['name' => 'Rina',   'pin_hash' => '123450', 'role' => 'CASHIER']);
    }

    private function loginAs(User $user, string $pin): void
    {
        $this->post('/login', ['user_id' => $user->id, 'pin' => $pin]);
    }

    public function test_manager_cannot_access_users(): void
    {
        $this->loginAs($this->manager, '123456');
        $this->get(route('users.index'))->assertRedirect(route('pos.index'));
    }

    public function test_admin_can_list_users(): void
    {
        $this->loginAs($this->admin, '123456');
        $this->get(route('users.index'))->assertOk()->assertSee('Admin');
    }

    public function test_admin_can_create_user(): void
    {
        $this->loginAs($this->admin, '123456');

        $this->post(route('users.store'), [
            'name' => 'Yanto',
            'pin'  => '9999',
            'role' => 'CASHIER',
        ])->assertRedirect(route('users.index'));

        $this->assertDatabaseHas('users', ['name' => 'Yanto', 'role' => 'CASHIER']);
    }

    public function test_admin_can_edit_user(): void
    {
        $this->loginAs($this->admin, '123456');

        $this->put(route('users.update', $this->cashier), [
            'name'   => 'Rina Updated',
            'pin'    => '',
            'role'   => 'CASHIER',
            'status' => 'ACTIVE',
        ])->assertRedirect(route('users.index'));

        $this->assertDatabaseHas('users', ['id' => $this->cashier->id, 'name' => 'Rina Updated']);
    }

    public function test_edit_updates_pin_only_when_provided(): void
    {
        $this->loginAs($this->admin, '123456');
        $oldHash = $this->cashier->fresh()->pin_hash;

        $this->put(route('users.update', $this->cashier), [
            'name' => 'Rina',
            'pin'  => '',
            'role' => 'CASHIER',
            'status' => 'ACTIVE',
        ]);

        $this->assertSame($oldHash, $this->cashier->fresh()->pin_hash);
    }

    public function test_cannot_delete_self(): void
    {
        $this->loginAs($this->admin, '123456');

        $this->delete(route('users.destroy', $this->admin))
            ->assertSessionHasErrors('user');

        $this->assertDatabaseHas('users', ['id' => $this->admin->id]);
    }

    public function test_cannot_delete_user_with_shift_history(): void
    {
        $this->loginAs($this->admin, '123456');

        $this->cashier->shifts()->create([
            'shift_number' => 'SHIFT-001',
            'opened_at'    => now(),
            'opening_cash' => 500000,
            'status'       => 'OPEN',
        ]);

        $this->delete(route('users.destroy', $this->cashier))
            ->assertSessionHasErrors('user');

        $this->assertDatabaseHas('users', ['id' => $this->cashier->id]);
    }

    public function test_create_rejects_duplicate_pin_length(): void
    {
        $this->loginAs($this->admin, '123456');

        $this->post(route('users.store'), [
            'name' => 'X',
            'pin'  => '12',
            'role' => 'CASHIER',
        ])->assertSessionHasErrors('pin');
    }

    public function test_cannot_delete_user_without_shifts_or_transactions(): void
    {
        $this->loginAs($this->admin, '123456');

        $this->delete(route('users.destroy', $this->cashier))
            ->assertRedirect(route('users.index'));

        $this->assertDatabaseMissing('users', ['id' => $this->cashier->id]);
    }
}