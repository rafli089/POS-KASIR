<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingController extends Controller
{
    public function index(): View
    {
        return view('settings.index', [
            'serviceChargePct' => (int) Setting::value(Setting::KEY_SERVICE_CHARGE_PERCENT, 0),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'service_charge_percent' => ['required', 'integer', 'min:0', 'max:100'],
        ]);

        Setting::set(Setting::KEY_SERVICE_CHARGE_PERCENT, (string) $validated['service_charge_percent']);

        return redirect()->route('settings.index')->with('success', 'Pengaturan disimpan.');
    }
}