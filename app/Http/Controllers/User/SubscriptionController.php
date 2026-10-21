<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Stripe\Stripe;
use Stripe\Customer;
use Stripe\Subscription;
use Stripe\PaymentMethod;
use Stripe\Checkout\Session;

class SubscriptionController extends Controller
{
    // public function index()
    // {
    //     $plans = Plan::all();
    //     return view('user.subscription.index', [
    //         'title' => "Subscriptions",
    //         'breadcrumbs' => [
    //             'Subscriptions' => route('user.subscription.index'),
    //         ],
    //         'plans' => $plans
    //     ]);
    // }

    public function index()
    {
        $user = auth()->user();

        $plans = Plan::all();

        $activeSubscription = Transaction::with('plan')
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->latest()
            ->first();

        return view('user.subscription.index', [
            'title' => "Subscriptions",
            'breadcrumbs' => [
                'Subscriptions' => route('user.subscription.index'),
            ],
            'plans' => $plans,
            'activeSubscription' => $activeSubscription,
        ]);
    }

    public function process(Request $request)
    {
        $request->validate([
            'plan_id' => 'required',
            'payment_method' => 'required'
        ]);

        $user = auth()->user();
        $plan = Plan::findOrFail($request->plan_id);

        Stripe::setApiKey(env('STRIPE_SECRET'));

        // 1️⃣ Create or retrieve Stripe customer
        if (!$user->stripe_customer_id) {
            $customer = Customer::create([
                'email' => $user->email,
                'name' => $user->name,
            ]);
            $user->stripe_customer_id = $customer->id;
            $user->save();
        } else {
            $customer = Customer::retrieve($user->stripe_customer_id);
        }

        $paymentMethod = PaymentMethod::retrieve($request->payment_method);
        $paymentMethod->attach(['customer' => $customer->id]);

        $customer->invoice_settings = [
            'default_payment_method' => $paymentMethod->id,
        ];
        $customer->save();

        $subscription = Subscription::create([
            'customer' => $customer->id,
            'items' => [
                ['price' => $plan->stripe_price_id],
            ],
            'expand' => ['latest_invoice.payment_intent'],
            'default_payment_method' => $paymentMethod->id,
        ]);

        Transaction::create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'stripe_subscription_id' => $subscription->id,
            'status' => 'active',
            'amount' => $plan->price,
            'currency' => 'EUR',
            'description' => 'Subscription to ' . $plan->name,
            'type' => 'card',
            'payment_status' => $subscription->status,
            'stripe_payment_intent_id' => $subscription->latest_invoice->id ?? null,
            'stripe_customer_id' => $customer->id,
            'payment_date' => now(),
            'next_billing_date' => $plan->type == 'monthly' ? now()->addMonth() : now()->addYear(),
        ]);

        return response()->json(['status' => 'success']);
    }

}