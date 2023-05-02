<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Product;
use Exception;
use Stripe\Exception\ApiErrorException;
use Stripe\Product as StripeProduct;

class ProductController extends Controller
{
    public function create(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'name'          => 'required|string',
            'description'   => 'required|string',
            'active'        => 'in:1,0',
            'image'         => 'required|url'
        ]);

        if ($validation->fails()) {
            return error('Validation Error', $validation->errors(), 'validation');
        }

        try {

            //Stripe
            $stripeProduct = StripeProduct::create([
                'name'          => $request->name,
                'description'   => $request->description,
                'images'        => [$request->image]
            ]);

            //Database
            $product = Product::create([
                'name'              => $request->name,
                'description'       => $request->description,
                'stripe_id'         => $stripeProduct->id,
                'active'            => $request->active ?? 1
            ]);

            return ok('Product Created', $product);
        } catch (ApiErrorException $api) {
            return error('Stripe API Error', $api->getMessage());
        } catch (Exception $e) {
            return error('Error', $e->getMessage());
        }
    }
}
