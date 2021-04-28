<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Stripe\Stripe;
use Stripe\Charge;

class PaymentController extends Controller
{
    //
    public function pay(Request $request){
        Stripe::setApiKey(env('STRIPE_SECRET'));//シークレットキー
    
          $charge = Charge::create(array(
               'amount' => Session::get('cart')->totalPrice,
               'currency' => 'jpy',
               'source'=> request()->stripeToken,
           ));
         return back();
        }
}
