<?php

namespace App\Http\Controllers;

use App\Models\Price;
use App\Models\Product;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use PDO;
use Stripe\Exception\ApiErrorException;
use Stripe\Price as StripePrice;

class PriceController extends Controller
{
    public function create(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'product_id'    => 'required|exists:products,id',
            'plan_id'       => 'required|exists:plans,id',
            'amount'        => 'required|integer|min:1',
            'currency'      => 'nullable|string',
            'interval'      => 'required|string|in:day,week,month,year'
        ]);

        if ($validation->fails())
            return error('Validation Error', $validation->errors(), 'validation');

        try {
            $product = Product::find($request->product_id);

            $stripePrice = StripePrice::create([
                'product'       => $product->stripe_id,
                'unit_amount'   => $request->amount,
                'currency'      => $request->currency ?? 'usd',
                'recurring'     => [
                    'interval'  => $request->interval,
                ]
            ]);

            $price = Price::create([
                'product_id' => $product->id,
                'plan_id' => $request->plan_id,
                'amount' => $request->amount,
                'currency' => $request->currency ?? 'usd',
                'stripe_id' => $stripePrice->id,
            ]);
            return ok('Price Created', $price);
        } catch (ApiErrorException $api) {
            return error('Stripe API Error', $api->getMessage());
        } catch (Exception $e) {
            return error('Error', $e->getMessage());
        }
    }
}
