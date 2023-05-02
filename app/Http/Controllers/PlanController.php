<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Models\Product;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Stripe\Exception\ApiErrorException;
use Stripe\Plan as StripePlan;

class PlanController extends Controller
{
    public function create(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'name'          => 'required|string|unique:plans,name',
            'amount'        => 'required|numeric|min:0',
            'interval_unit' => 'required|in:day,week,month,year',
            'interval'      => 'required|numeric|min:1',
            'product_id'    => 'required|exists:products,id',
            'description'   => 'required|string'
        ]);

        if ($validation->fails())
            return error('Validation Error', $validation->errors(), 'validation');

        try {

            $product = Product::find($request->product_id);

            $stripePlan = StripePlan::create([
                'nickname' => $request->name,
                'amount' => $request->amount,
                'currency' => 'usd',
                'interval' => $request->interval_unit,
                'interval_count' => $request->interval,
                'product' => $product->stripe_id
            ]);

            $plan = Plan::create($request->only(['name', 'description', 'product_id', 'interval', 'interval_unit']) + [
                'price'     => $request->amount / 100,
                'stripe_id' => $stripePlan->id
            ]);

            return ok('Plan Created', $plan);
        } catch (ApiErrorException $api) {
            return error('Stripe API Error', $api->getMessage());
        } catch (Exception $e) {
            return error('Error', $e->getMessage());
        }
    }
}
