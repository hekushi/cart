<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Slider;
use App\Product;
use App\Category;
use App\Cart;
use Stripe\Charge;
use Stripe\Stripe;
use Session;
use App\Order;
use App\Client;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use App\Mail\SendMail;

class ClientController extends Controller
{
    //
    public function home(){

        $products = Product::where('status', 1)->get(); 
        $sliders = Slider::where('status', 1)->get();

        return view('client.home')->with('sliders', $sliders)->with('products', $products);
    }

    public function view_by_cat($name){
        $categories = Category::get();
        $products = Product::where('product_category', $name)->get();

        return view('client.shop')->with('products', $products)->with('categories', $categories);


    }

    public function shop(){
        $categories = Category::get();
        $products = Product::where('status', 1)->get(); 
        return view('client.shop')->with('products', $products)->with('categories', $categories);

    }

public function cart(){
    if(!Session::has('cart')){
        return view('client.cart');
    }

    $oldCart = Session::has('cart')? Session::get('cart'):null;
    $cart = new Cart($oldCart);
    return view('client.cart', ['products'=> $cart->items]);

}

public function addToCart($id){

    $product = Product::find($id);

    //print_r($product);
    $oldCart = Session::has('cart')? Session::get('cart'):null;
    $cart = new Cart($oldCart);
    $cart->add($product, $id);
    Session::put('cart', $cart);

    //dd(Session::get('cart'));
    return redirect('/shop');


}




public function updateqty(Request $request){
        //print('the product id is '.$request->id.' And the product qty is '.$request->quantity);
        $oldCart = Session::has('cart')? Session::get('cart'):null;
        $cart = new Cart($oldCart);
        $cart->updateQty($request->id, $request->quantity);
        Session::put('cart', $cart);

        //dd(Session::get('cart'));
        return redirect('/cart');
    

}
public function removeitem($id){
    $oldCart = Session::has('cart')? Session::get('cart'):null;
    $cart = new Cart($oldCart);

    $cart->removeItem($id);
   
    if(count($cart->items) > 0){
        Session::put('cart', $cart);
    }
    else{
        Session::forget('cart');
    }

    //dd(Session::get('cart'));
    return redirect('/cart');


}

public function checkout(){
    if(!Session::has('client')){
        return redirect('/client_login');
    }
    if(!Session::has('cart')){
        return redirect('/cart');
    }
    return view('client.checkout');
}

public function postcheckout(Request $request){
    if(!Session::has('cart')){
        return redirect('/cart'); 
        // , ['Products' => null]           
    }
    $oldCart = Session::has('cart')? Session::get('cart'):null;
    $cart = new Cart($oldCart);

    Stripe::setApiKey('sk_test_51If3WCAd7mtRa36PHwo0eXKvMFeS4RmmTwsRQ7PsjzdW4vhxc6uNwIzBTTXi37bl5Af6Yd5Izrt9y2NwLrHKQsR200eEdv34lI');
    try{

        $charge = Charge::create(array(
            "amount" => $cart->totalPrice * 100,
            "currency" => "usd",
            "source" => $request->input('stripeToken'), // obtained with Stripe.js
            "description" => "Test Charge"
        ));

        $order = new Order();

        $order->name = $request->input('name');
        $order->address = $request->input('address');
        $order->cart = serialize($cart);
        $order->payment_id = $charge->id;

        $order->save();

        $orders = Order::where('payment_id', $charge->id)->get();

        $orders->transform(function($order, $key){
            $order->cart = unserialize($order->cart);

            return $order;

        });

        $email = Session::get('client')->email;

        Mail::to($email)->send(new SendMail($orders));


    } catch(\Exception $e){
        Session::put('error', $e->getMessage());
        return redirect('/checkout');
    }

    Session::forget('cart');
    return redirect('/cart')->with('success', 'Purchase accomplished successfully !');


}

public function login(){
    return view('client.login');
}

public function signup(){
    return view('client.signup');
}

public function createaccount(Request $request){

        $this->validate($request, ['email' => 'email|required|unique:clients', 
                                'password' => 'required|min:4']);

        $client = new Client();
        $client->email = $request->input('email');
        $client->password = bcrypt($request->input('password'));

        $client->save();

        return back()->with('status', 'Your account has been created successfully');
    }

    public function accessaccount(Request $request){

        $this->validate($request, ['email' => 'email|required', 
                                'password' => 'required']);

        $client = Client::where('email', $request->input('email'))->first();

        if ($client) {
            # code...
            if (Hash::check($request->input('password'), $client->password)) {
                # code...
                Session::put('client', $client);

                return redirect('/shop');
                //return back()->with('status', 'Your email is '.Session::get('client')->id);
            } else {
                # code...
                return back()->with('error', 'Wrong password or email');
            }
            
        } else {
            # code...
            return back()->with('error', 'You do not have an account');
        }
        

    }

    public function logout(){

        Session::forget('client');
        return back();
    }

}
