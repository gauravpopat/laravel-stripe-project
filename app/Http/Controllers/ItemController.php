<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\Price;
use App\Models\Product;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Stripe\Exception\ApiErrorException;
use Stripe\Price as StripePrice;

class ItemController extends Controller
{
    public function create(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'name'          => 'required|string',
            'description'   => 'string',
            'quantity'      => 'required|integer|min:1',
            'product_id'    => 'required|integer|exists:products,id',
            'price_id'      => 'required|exists:prices,id',
        ]);

        if ($validation->fails()) {
            return error('Validation Error', $validation->errors());
        }

        try {

            $stripePriceId = Price::find($request->price_id)->stripe_id;

            // Retrieve the price from Stripe
            $price = StripePrice::retrieve($stripePriceId);

            // Create the item in the database
            $item = Item::create([
                'stripe_id'     => $price->id,
                'product_id'    => $request->product_id,
                'name'          => $request->name,
                'description'   => $request->description,
                'quantity'      => $request->quantity,
                'active'        => true,
            ]);

            return ok('Item created successfully', $item);
        }catch(ApiErrorException $api){
            return error('Stripe API Error',$api->getMessage());
        } catch (Exception $e) {
            return error('Error', $e->getMessage());
        }
    }
}
