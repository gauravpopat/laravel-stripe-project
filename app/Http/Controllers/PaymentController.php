<?php

namespace App\Http\Controllers;

use App\Models\CardDetail;
use App\Models\CheckoutSession;
use App\Models\Customer;
use App\Models\PaymentIntent;
use App\Models\Price;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Stripe\Checkout\Session;
use Stripe\Customer as StripeCustomer;
use Stripe\Exception\ApiErrorException;
use Stripe\Exception\CardException;
use Stripe\Price as StripePrice;
use Stripe\PaymentIntent as StripePaymentIntent;
use Stripe\PaymentMethod as StripePaymentMethod;

class PaymentController extends Controller
{
    public function addCard(Request $request)
    {

        $validation = Validator::make($request->all(), [
            'card_number'   => 'required|numeric',
            'exp_month'     => 'required|digits:2|numeric',
            'exp_year'      => 'required|digits:4|numeric',
            'cvc'           => 'required|digits_between:3,4|numeric'
        ]);

        if ($validation->fails()) {
            return error('Validation Error', $validation->errors(), 'validation');
        }

        try {

            $customer = Customer::where('user_id', auth()->user()->id)->first();

            $stripeCustomer = StripeCustomer::retrieve($customer->stripe_id);

            $stripePaymentMethod = StripePaymentMethod::create([
                'type'  => 'card',
                'card'  =>  [
                    'number'    => $request->card_number,
                    'exp_month' => $request->exp_month,
                    'exp_year'  => $request->exp_year,
                    'cvc'       => $request->cvc
                ]
            ]);

            $stripePaymentMethod->attach(['customer'  => $stripeCustomer->id]);

            $stripeCustomer->default_payment_method = $stripePaymentMethod->id;

            StripeCustomer::update($stripeCustomer->id, [
                'invoice_settings' => [
                    'default_payment_method' => $stripePaymentMethod->id
                ]
            ]);

            $card = CardDetail::create($request->only(['customer_id']) + [
                'stripe_id'         => $stripePaymentMethod->id,
                'card_brand'        => $stripePaymentMethod->card->brand,
                'card_last_four'    => $stripePaymentMethod->card->last4,
                'primary'           => true
            ]);

            return ok('Card Added.', $card);
        } catch (ApiErrorException $e) {
            return error('Stripe API Error', $e->getMessage());
        } catch (CardException $card) {
            return error('Stripe Card Error', $card->getMessage());
        } catch (Exception $e) {
            return error('Unknown Error', $e->getMessage());
        }
    }

    public function createIntent(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'customer_id'   => 'required|exists:customers,id',
            'price_id'      => 'required|exists:prices,id',
            'amount'        => 'required|numeric',
            'currency'      => 'required'
        ]);

        if ($validation->fails())
            return error('Validation Error', $validation->errors(), 'validation');

        try {
            $customer = Customer::find($request->customer_id);
            $stripeCustomer = StripeCustomer::retrieve($customer->stripe_id);

            $stripePaymentIntent = StripePaymentIntent::create([
                'amount'        => $request->amount,
                'currency'      => $request->currency,
                'customer'      => $stripeCustomer->id,
                'description'   => 'Testing Intent',
            ]);

            $paymentIntent = PaymentIntent::create([
                'customer_id'   => $request->customer_id,
                'stripe_id'     => $stripePaymentIntent->id,
                'succeeded'     => $stripePaymentIntent->status === 'succeeded' ? 1 : 0,
                'price_id'      => $request->price_id
            ]);

            return ok('Payment Intent Created Successful', $paymentIntent);
        } catch (Exception $e) {
            return error('Error', $e->getMessage());
        } catch (ApiErrorException $ae) {
            return error('Stripe API Error', $ae->getMessage());
        }
    }

    public function createCheckoutSession(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'customer_id'   => 'required|exists:customers,id',
            'price_id'      => 'required|exists:prices,id',
            'success_url'   => 'required|url',
            'cancel_url'    => 'required|url',
            'quantity'      => 'required|integer|min:1',
        ]);

        if ($validation->fails()) {
            return error('Validation Error', $validation->errors(), 'validation');
        }

        try {
            $customer   =   Customer::find($request->customer_id);
            $price      =   Price::find($request->price_id);

            $stripeCustomer = StripeCustomer::retrieve($customer->stripe_id);
            $stripePrice = StripePrice::retrieve($price->stripe_id);

            if ($stripePrice->type !== 'one_time') {
                throw new Exception("Price not valid");
            }

            $stripeSession = Session::create([
                'customer'              => $stripeCustomer->id,
                'payment_method_types'  => ['card'],
                'line_items'            => [
                    [
                        'price' => $stripePrice->id,
                        'quantity' => $request->quantity ?? 1,
                    ],
                ],
                'mode'                  => 'payment',
                'success_url'           => $request->success_url,
                'cancel_url'            => $request->cancel_url,
            ]);

            $session = CheckoutSession::create($request->only(['customer_id']) + [
                'stripe_id'     => $stripeSession->id,
                'mode'          => 'payment',
                'success_url'   => $request->success_url,
                'cancel_url'    => $request->cancel_url
            ]);

            return ok('Session Created Successfully', $session);
        } catch (ApiErrorException $e) {
            return error('Stripe API Error', $e->getMessage());
        } catch (\Exception $e) {
            return error('Error', $e->getMessage());
        }
    }
}
