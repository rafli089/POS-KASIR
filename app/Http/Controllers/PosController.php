<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\PaymentMethod;
use App\Models\Setting;
use App\Models\Shift;
use App\Models\ShiftActivity;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PosController extends Controller
{
    public function index(Request $request): View
    {
        $userId = session('user_id');
        $shift = Shift::currentForUser($userId);

        $categories = Category::where('status', 'ACTIVE')->orderBy('sort_order')->get();
        $categoryId = $request->input('category');
        $query = Product::where('status', 'ACTIVE')->with('category:id,name');

        if ($categoryId) {
            $query->where('category_id', $categoryId);
        }

        $products = $query->orderBy('name')->get();
        $paymentMethods = PaymentMethod::where('status', 'ACTIVE')->get();
        $cashId = PaymentMethod::where('code', 'CASH')->value('id');
        $serviceChargePct = (int) (Setting::value(Setting::KEY_SERVICE_CHARGE_PERCENT, 0) ?? 0);

        return view('pos.index', compact('shift', 'categories', 'products', 'categoryId', 'paymentMethods', 'cashId', 'serviceChargePct'));
    }
}