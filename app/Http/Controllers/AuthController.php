<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Stripe\Customer as StripeCustomer;
use App\Models\Customer;
use App\Notifications\WelcomeNotification;
use Exception;
use Stripe\Exception\ApiErrorException;

class AuthController extends Controller
{
    public function Register(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'name'                  => 'required|max:50',
            'email'                 => 'required|email|max:50|unique:users,email',
            'password'              => 'required|min:8|max:15|confirmed',
            'password_confirmation' => 'required',
        ]);

        if ($validation->fails())
            return error('Validation Error', $validation->errors(), 'validation');

        try {

            // User Create

            $user = User::create($request->only(['name', 'email']) + [
                'password'                  => Hash::make($request->password),
                'role'                      => 'customer',
                'email_verification_code'   => Str::random(18)
            ]);

            // Stripe Customer Create

            $stripeCustomer = StripeCustomer::create([
                'email' => $request->email,
                'name'  => $request->name
            ]);

            // Update The Stripe ID

            $user->update([
                'stripe_id' => $stripeCustomer->id
            ]);

            //Insert Data in Customer Table

            Customer::create($request->only(['email', 'name']) + [
                'stripe_id' => $stripeCustomer->id,
                'user_id'   => $user->id
            ]);

            $user->notify(new WelcomeNotification($user));

            return ok('User Created Successfully', $user);
        } catch (ApiErrorException $api) {
            return error('Stripe API Error', $api->getMessage());
        } catch (Exception $e) {
            return error('Error', $e->getMessage());
        }
    }

    public function verifyEmail($email_verification_code)
    {
        $user = User::where('email_verification_code', $email_verification_code)->firstOrFail();
        $user->update([
            'is_verified'               => true,
            'email_verification_code'   => null
        ]);
        return ok('Email Verification Successfull.');
    }

    public function login(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'email'     => 'required|email|exists:users,email',
            'password'  => 'required'
        ]);

        if ($validation->fails())
            return error('Validation Error', $validation->errors(), 'validation');

        $user = User::where('email', $request->email)->first();

        if ($user->is_verified == true) {
            if (Auth::attempt(['email'   =>  $request->email, 'password'   =>  $request->password])) {
                $token = $user->createToken("API Login Token")->plainTextToken; // generated token (sanctum)
                return ok('Login Successfully', $token);
            } else {
                return error('Incorrect Password!');
            }
        } else {
            return error('Email Not Verified');
        }
    }
}
