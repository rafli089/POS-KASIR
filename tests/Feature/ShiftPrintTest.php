<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShiftPrintTest extends TestCase
{
    use RefreshDatabase;

    private User $cashier;
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cashier = User::create(['name' => 'Rina', 'pin_hash' => '123450', 'role' => 'CASHIER']);
        $this->admin = User::create(['name' => 'Admin', 'pin_hash' => '123456', 'role' => 'ADMIN']);

        $this->post('/login', ['user_id' => $this->cashier->id, 'pin' => '123450']);
        $this->post('/shift/open', ['opening_cash' => 500000]);
    }

    public function test_cashier_can_print_own_shift_report(): void
    {
        $this->post('/shift/cash-in', ['amount' => 100000]);

        $this->get('/shift/print')
            ->assertOk()
            ->assertSee('Laporan Shift')
            ->assertSee('100.000');
    }

    public function test_close_shows_print_button_on_report(): void
    {
        $shift = \App\Models\Shift::first();
        $this->post('/shift/close', ['actual_cash' => 600000])
            ->assertRedirect(route('shift.report', ['shift' => $shift->id]));

        $this->get(route('shift.report', ['shift' => $shift->id]))
            ->assertOk()
            ->assertSee('shift/print');
    }

    public function test_admin_can_print_anyone_shift(): void
    {
        $this->post('/login', ['user_id' => $this->admin->id, 'pin' => '123456']);

        $shift = \App\Models\Shift::first();

        $this->get(route('shift.print', ['shift' => $shift->id]))
            ->assertOk()
            ->assertSee('Laporan Shift');
    }
}