<?php

namespace Tests\Feature;

use App\Models\ShiftActivity;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CashMovementTest extends TestCase
{
    use RefreshDatabase;

    private User $cashier;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cashier = User::create(['name' => 'Rina', 'pin_hash' => '123450', 'role' => 'CASHIER']);
        $this->post('/login', ['user_id' => $this->cashier->id, 'pin' => '123450']);
        $this->post('/shift/open', ['opening_cash' => 500000]);
    }

    public function test_cash_in_updates_expected_and_logs_activity(): void
    {
        $this->post('/shift/cash-in', ['amount' => 100000])
            ->assertRedirect();

        $this->assertDatabaseHas('shift_activities', [
            'activity_type' => 'CASH_IN',
            'reference_amount' => 100000,
        ]);

        $this->get('/shift/current')
            ->assertOk()
            ->assertSee('Kas Masuk')
            ->assertSee('100.000');
    }

    public function test_cash_out_updates_expected_and_logs_activity(): void
    {
        $this->post('/shift/cash-out', ['amount' => 50000])
            ->assertRedirect();

        $this->assertDatabaseHas('shift_activities', [
            'activity_type' => 'CASH_OUT',
            'reference_amount' => 50000,
        ]);
    }

    public function test_requires_positive_amount(): void
    {
        $this->post('/shift/cash-in', ['amount' => 0])
            ->assertSessionHasErrors('amount');

        $this->assertDatabaseMissing('shift_activities', ['activity_type' => 'CASH_IN']);
    }

    public function test_needs_active_shift(): void
    {
        $this->post('/logout');
        $this->session(['user_id' => $this->cashier->id]);
        $this->post('/shift/cash-out', ['amount' => 1000]);

        $this->get('/shift/current')->assertOk();
    }
}