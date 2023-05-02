<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Customer;
use Exception;
use Illuminate\Support\Facades\Validator;
use Stripe\Customer as StripeCustomer;
use Stripe\Exception\ApiErrorException;

class CustomerController extends Controller
{

    public function update(Request $request)
    {
        $validaion = Validator::make($request->all(), [
            'name'          => 'required|string'
        ]);

        if ($validaion->fails())
            return error('Validation Error', $validaion->errors(), 'validaiton');

        try {

            $user = auth()->user();
            $customer = Customer::where('user_id', $user->id)->first();

            $user->update($request->only(['name']));

            $stripeCustomerId = $customer->stripe_id;

            $customer->update($request->only('name'));

            StripeCustomer::update($stripeCustomerId, [
                'name'  => $request->name
            ]);

            return ok('Customer Detail Updated', $customer);
        } catch (ApiErrorException $api) {
            return error('Stripe API Error', $api->getMessage());
        } catch (Exception $e) {
            return error('Error', $e->getMessage());
        }
    }

    public function delete()
    {
        try {
            $user = auth()->user();
            $customer = Customer::where('user_id', $user->id)->first();
            $stripeCustomerId = $customer->stripe_id;

            //Delete the customer 
            StripeCustomer::retrieve($stripeCustomerId)->delete();
            $user->delete();

            return ok('Customer Deleted');
        } catch (ApiErrorException $api) {
            return error('Stripe API Error', $api->getMessage());
        } catch (Exception $e) {
            return error('Error', $e->getMessage());
        }
    }
}
