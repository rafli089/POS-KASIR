<?php

namespace Tests\Feature;

use App\Models\Shift;
use App\Models\ShiftActivity;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShiftClosePerShiftTest extends TestCase
{
    use RefreshDatabase;

    private User $cashier;
    private User $manager;
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cashier = User::create(['name' => 'Rina', 'pin_hash' => '123450', 'role' => 'CASHIER']);
        $this->manager = User::create(['name' => 'Manajer', 'pin_hash' => '123456', 'role' => 'MANAGER']);
        $this->admin = User::create(['name' => 'Admin', 'pin_hash' => '123456', 'role' => 'ADMIN']);
    }

    private function openShiftFor(User $user, int $cash = 500000): Shift
    {
        $this->post('/login', ['user_id' => $user->id, 'pin' => '123450']);

        $this->post('/shift/open', ['opening_cash' => $cash]);

        return Shift::where('user_id', $user->id)->firstOrFail();
    }

    public function test_manager_can_list_all_shifts(): void
    {
        $shift = $this->openShiftFor($this->cashier);

        $this->post('/login', ['user_id' => $this->manager->id, 'pin' => '123456']);

        $this->get('/shifts')
            ->assertOk()
            ->assertSee($shift->shift_number)
            ->assertSee('Rina');
    }

    public function test_cashier_cannot_access_shifts_list(): void
    {
        $this->post('/login', ['user_id' => $this->cashier->id, 'pin' => '123450']);

        $this->get('/shifts')->assertRedirect();
    }

    public function test_manager_can_close_cashier_shift_with_form(): void
    {
        $shift = $this->openShiftFor($this->cashier);

        $this->post('/login', ['user_id' => $this->manager->id, 'pin' => '123456']);

        $this->get(route('shifts.close', $shift))
            ->assertOk()
            ->assertSee($shift->shift_number);

        $this->post(route('shifts.close.store', $shift), ['actual_cash' => 500000]);

        $shift->refresh();
        $this->assertSame(Shift::STATUS_CLOSED, $shift->status);
        $this->assertNotNull($shift->closed_at);
        $this->assertSame(500000, $shift->expected_cash);
        $this->assertSame(500000, $shift->actual_cash);
        $this->assertSame(0, $shift->cash_difference);

        $this->assertDatabaseHas('shift_activities', [
            'shift_id' => $shift->id,
            'activity_type' => ShiftActivity::TYPE_CLOSE_SHIFT,
        ]);
    }

    public function test_manager_can_quick_close_cashier_shift(): void
    {
        $shift = $this->openShiftFor($this->cashier);

        $this->post('/login', ['user_id' => $this->admin->id, 'pin' => '123456']);

        $this->post(route('shifts.close.quick', $shift))
            ->assertRedirect(route('shift.report', ['shift' => $shift->id]));

        $shift->refresh();
        $this->assertSame(Shift::STATUS_CLOSED, $shift->status);
        $this->assertSame(0, $shift->cash_difference);
    }

    public function test_cannot_close_already_closed_shift(): void
    {
        $shift = $this->openShiftFor($this->cashier);
        $shift->update(['status' => Shift::STATUS_CLOSED, 'closed_at' => now()]);

        $this->post('/login', ['user_id' => $this->manager->id, 'pin' => '123456']);

        $this->post(route('shifts.close.quick', $shift))->assertSessionHasErrors('close');
    }
}