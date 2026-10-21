<?php

namespace App\Http\Controllers\Api\V1;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Api\ResponseController;
use App\Mail\UpdateEmailSendOtpMail;
use App\Models\Plan;
use App\Models\Transaction;
use App\Services\ValidationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Stripe\Customer;
use Stripe\Invoice;
use Stripe\PaymentIntent;
use Stripe\PaymentMethod;
use Stripe\Stripe;
use Stripe\Subscription;

class SubscriptionController extends ResponseController
{
    public function getPlans(Request $request)
    {
        $request->validate([
            'type' => 'required|in:monthly,yearly',
        ]);

        $plans = Plan::where('type', $request->type)
            ->get();

        return ResponseHelper::send(200, 'Plans retrieved successfully', $plans);

    }


    // public function process(Request $request)
    // {
    //     $user = $request->user();

    //     $request->validate([
    //         'plan_id' => 'required|exists:plans,id',
    //         'price_id' => 'required|string',
    //         'payment_method_id' => 'required|string',
    //     ]);

    //     Stripe::setApiKey(env('STRIPE_SECRET'));

    //     try {
    //         if (!$user->stripe_customer_id) {
    //             $customer = Customer::create([
    //                 'email' => $user->email,
    //                 'name'  => $user->name,
    //             ]);
    //             $user->update(['stripe_customer_id' => $customer->id]);
    //         } else {
    //             $customer = Customer::retrieve($user->stripe_customer_id);
    //         }

    //         $paymentMethod = PaymentMethod::retrieve($request->payment_method_id);

    //         if (empty($paymentMethod->customer)) {
    //             $paymentMethod->attach(['customer' => $customer->id]);
    //         }

    //         Customer::update($customer->id, [
    //             'invoice_settings' => [
    //                 'default_payment_method' => $request->payment_method_id,
    //             ],
    //         ]);

    //         $subscription = Subscription::create([
    //             'customer' => $customer->id,
    //             'items' => [[ 'price' => $request->price_id ]],
    //             'expand' => ['latest_invoice.payment_intent'],
    //             'default_payment_method' => $request->payment_method_id,
    //         ]);

    //         $paymentIntent = $subscription->latest_invoice->payment_intent;

    //          Log::info('Stripe process====>>>>>>>>>>>>>', [
    //             'subscription_id' => $subscription->id,
    //             'payment_intent' => $paymentIntent ? $paymentIntent->toArray() : null
    //         ]);

    //         Transaction::where('user_id', $user->id)->update(['status' => 'inactive']);
    //         $transaction = Transaction::create([
    //             'user_id' => $user->id,
    //             'plan_id' => $request->plan_id,
    //             'amount' => $paymentIntent->amount / 100,
    //             'currency' => strtoupper($paymentIntent->currency),
    //             'type' => 'subscription',
    //             'payment_status' => $paymentIntent->status,
    //             'stripe_payment_intent_id' => $paymentIntent->id,
    //             'stripe_subscription_id' => $subscription->id,
    //             'stripe_customer_id' => $customer->id,
    //             'status' => 'active',
    //             'payment_date' => now(),
    //             'next_billing_date' => date('Y-m-d H:i:s', $subscription->current_period_end),
    //         ]);

    //         return response()->json([
    //             'status' => 200,
    //             'message' => 'Subscription created successfully',
    //             'data' => [
    //                 'subscription_id' => $subscription->id,
    //                 'client_secret' => $paymentIntent->client_secret,
    //                 'status' => $paymentIntent->status,
    //             ],
    //         ]);
    //     } catch (\Exception $e) {
    //         Log::error('Subscription process failed', ['error' => $e->getMessage()]);
    //         return response()->json([
    //             'status' => 500,
    //             'message' => $e->getMessage(),
    //         ]);
    //     }
    // }


    public function createSubscription(Request $request)
    {
        $request->validate([
            'customer_id' => 'required|string',
            'price_id' => 'required|string',
            'payment_method_id' => 'required|string',
        ]);

        Stripe::setApiKey(env('STRIPE_SECRET'));

        try {
            // 1️⃣ Create the subscription in Stripe
            $subscription = Subscription::create([
                'customer' => $request->customer_id,
                'items' => [['price' => $request->price_id]],
                'default_payment_method' => $request->payment_method_id,
                'expand' => ['latest_invoice.payment_intent'],
            ]);

            $paymentIntent = $subscription->latest_invoice->payment_intent ?? null;

            // 2️⃣ Return data back to app
            return response()->json([
                'status' => 200,
                'message' => 'Subscription created successfully',
                'data' => [
                    'subscription_id' => $subscription->id,
                    'client_secret' => $paymentIntent ? $paymentIntent->client_secret : null,
                    'payment_status' => $paymentIntent ? $paymentIntent->status : $subscription->status,
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Stripe subscription creation failed', ['error' => $e->getMessage()]);
            return response()->json([
                'status' => 500,
                'message' => $e->getMessage(),
            ]);
        }
    }

    public function process(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'subscription_id' => 'required|string',
            'plan_id' => 'required|exists:plans,id',
        ]);
        $plan = Plan::findOrFail($request->plan_id);

        Stripe::setApiKey(env('STRIPE_SECRET'));

        try {
            $subscription = Subscription::retrieve($request->subscription_id);
            $invoice = $subscription->latest_invoice ? Invoice::retrieve($subscription->latest_invoice) : null;
            $paymentIntent = $invoice?->payment_intent ? PaymentIntent::retrieve($invoice->payment_intent) : null;

            Log::info('PaymentIntent Data:', $invoice ? $invoice->toArray() : []);
            // $amount = $paymentIntent ? $paymentIntent->amount / 100 : 0;
            // $currency = $paymentIntent ? strtoupper($paymentIntent->currency) : 'EUR';
            // $status = $paymentIntent ? $paymentIntent->status : $subscription->status;
            $charges = \Stripe\Charge::all([
                'customer' => $subscription->customer,
                'limit' => 1,
            ]);

            $latestCharge = $charges->data[0] ?? null;

            Log::info("Latest Charge:", $latestCharge ? $latestCharge->toArray() : []);
            $amount = $latestCharge ? $latestCharge->amount / 100 : 0;
            $currency = $latestCharge ? strtoupper($latestCharge->currency) : 'EUR';
            $status = $latestCharge ? $latestCharge->status : $subscription->status;

            Transaction::where('user_id', $user->id)->update(['status' => 'inactive']);

            Transaction::create([
                'user_id' => $user->id,
                'plan_id' => $request->plan_id,
                'amount' => $amount,
                'currency' => $currency,
                'type' => 'card',
                'payment_status' => $subscription->status,
                'stripe_payment_intent_id' => $paymentIntent?->id,
                'stripe_subscription_id' => $subscription->id,
                'stripe_customer_id' => $subscription->customer,
                'description' => 'Subscription to ' . $plan->name,
                'status' => 'active',
                'payment_date' => now(),
                'next_billing_date' => $plan->type == 'monthly' ? now()->addMonth() : now()->addYear(),
            ]);

            return response()->json([
                'status' => 200,
                'message' => 'Subscription processed successfully',
                'data' => [
                    'subscription_id' => $subscription->id,
                    'status' => $status,
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Subscription process failed', ['error' => $e->getMessage()]);
            return response()->json([
                'status' => 500,
                'message' => $e->getMessage(),
            ]);
        }
    }
    public function webhook(Request $request)
    {
        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');
        $endpointSecret = env('STRIPE_WEBHOOK_SECRET');

        try {
            $event = \Stripe\Webhook::constructEvent(
                $payload,
                $sigHeader,
                $endpointSecret
            );
        } catch (\UnexpectedValueException $e) {
            return response('Invalid payload', 400);
        } catch (\Stripe\Exception\SignatureVerificationException $e) {
            return response('Invalid signature', 400);
        }

        $type = $event->type;
        $object = $event->data->object;
        Log::info('Stripe Webhook Received', ['type' => $type, 'object' => $object]);
        Log::info("Stripe Webhook Received: {$event->type}");

        switch ($type) {
            case 'invoice.payment_succeeded':
                $subscriptionId = $object->subscription ?? null;

                if ($subscriptionId) {
                    $transaction = Transaction::where('stripe_subscription_id', $subscriptionId)->first();
                    if ($transaction) {
                        $transaction->update([
                            'status' => 'active',
                            'payment_status' => 'succeeded',
                            'payment_date' => now(),
                            'next_billing_date' => now()->addMonth(),
                        ]);
                    }
                }
                break;

            case 'invoice.payment_failed':
                $subscriptionId = $object->subscription ?? null;
                if ($subscriptionId) {
                    $transaction = Transaction::where('stripe_subscription_id', $subscriptionId)->first();
                    if ($transaction) {
                        $transaction->update(['status' => 'failed']);
                    }
                }
                break;

            case 'customer.subscription.deleted':
                $subscriptionId = $object->id;
                $transaction = Transaction::where('stripe_subscription_id', $subscriptionId)->first();
                if ($transaction) {
                    $transaction->update(['status' => 'cancelled']);
                }
                break;

            default:
                Log::info("Unhandled event type: " . $event->type);
        }

        return response('Webhook handled', 200);
    }

    public function cancelSubscription(Request $request)
    {
        $user = auth()->user();

        $transaction = Transaction::where('user_id', $user->id)
            ->whereNotNull('stripe_subscription_id')
            ->where('status', 'active')
            ->latest()
            ->first();

        if (!$transaction) {
            return response()->json([
                'status' => 404,
                'message' => 'No active subscription found',
            ]);
        }

        Stripe::setApiKey(env('STRIPE_SECRET'));

        // Cancel at period end
        Subscription::update(
            $transaction->stripe_subscription_id,
            ['cancel_at_period_end' => true]
        );

        // Update DB
        $transaction->update([
            'status' => 'cancelled',
            'payment_status' => 'cancelled'
        ]);

        return response()->json([
            'status' => 200,
            'message' => 'Subscription will be cancelled at period end',
        ]);
    }

}