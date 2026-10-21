@extends('layouts.admin.app')

@section('head_css')
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">

    <style>
        .container {
            background: url('https://www.transparenttextures.com/patterns/watercolor-texture.png'), rgba(255, 255, 255, 0.8);
            background-blend-mode: overlay;
            border-radius: 15px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            padding: 20px;
            width: 100%;
            text-align: center;
            margin: 0 auto;
        }

        .logo {
            font-size: 24px;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 10px;
        }

        .tagline {
            font-size: 14px;
            color: #7f8c8d;
            margin-bottom: 20px;
        }

        .nav-tabs {
            justify-content: center;
            border-bottom: none;
            margin-bottom: 20px;
        }

        .nav-tabs .nav-link {
            background: #e0e0e0;
            color: #333;
            border: none;
            border-radius: 20px 20px 0 0;
            margin: 0 5px;
            padding: 8px 20px;
            transition: background 0.3s, color 0.3s;
        }

        .nav-tabs .nav-link.active {
            background: #007bff;
            /* Blue color for active tab */
            color: white;
        }

        .nav-tabs .nav-link:hover {
            background: #0056b3;
            color: white;
        }

        .card {
            background: rgba(255, 255, 255, 0.9);
            border-radius: 10px;
            margin: 15px 0;
            padding: 15px;
            border: 1px solid #ddd;
            position: relative;
        }

        .card.recommended::before {
            content: "Recommended";
            position: absolute;
            top: -10px;
            left: 10px;
            background: #f1c40f;
            color: white;
            padding: 2px 10px;
            border-radius: 5px;
            font-size: 12px;
        }

        .card.premium::before {
            content: "Premium";
            position: absolute;
            top: -10px;
            left: 10px;
            background: #f39c12;
            color: white;
            padding: 2px 10px;
            border-radius: 5px;
            font-size: 12px;
        }

        .price {
            font-size: 20px;
            color: #0A0D3F;
            margin: 10px 0;
            font-weight: 600;
        }

        .original-price {
            font-size: 14px;
            color: #0A0D3F;
            text-decoration: line-through;
            margin-top: -5px;
            margin-bottom: 10px;
        }

        .feature {
            font-size: 14px;
            margin: 5px 0;
            color: #2c3e50;
        }

        .feature.completed {
            color: #28a745;
        }

        .feature.incomplete {
            color: #dc3545;
        }

        .subscribe-btn {
            background: #007bff;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 25px;
            cursor: pointer;
            font-size: 16px;
            margin-top: 10px;
            transition: background 0.3s;
            width: 100%;
        }

        .subscribe-btn:hover {
            background: #0056b3;
        }

        .tab-content {
            padding: 20px;
        }

        .subscription-status-card {
            max-width: 450px;
            background-color: #f9f9ff;
            border: 2px dashed #cdd4ff;
            border-radius: 12px;
            padding: 16px 20px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
            transition: all 0.3s ease;
        }

        .subscription-status-card:hover {
            border-color: #0056b3;
        }

        .subscription-status-card .icon-wrap {
            width: 42px;
            height: 42px;
            background-color: white;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .subscription-status-card .icon-wrap i {
            color: white;
            font-size: 20px;
        }
    </style>
@endsection

@section('content')
    <div class="page-content">
        <div class="container-fluid">
            @include('layouts.admin.breadcrumb')

            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <div class="container">

                                @if ($activeSubscription)
                                    <div class="subscription-status-card mx-auto my-4">
                                        <div class="d-flex align-items-center">
                                            <div class="icon-wrap me-3">
                                                {{-- <i class="bi bi-patch-check-fill"></i> --}}
                                                <svg width="30" height="30" viewBox="0 0 30 30" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                    <path d="M26.5623 23.0876L24.4998 23.5751C24.0373 23.6876 23.6748 24.0376 23.5748 24.5001L23.1373 26.3376C22.8998 27.3376 21.6248 27.6501 20.9623 26.8626L17.2248 22.5626C16.9248 22.2126 17.0873 21.6626 17.5373 21.5501C19.7498 21.0126 21.7373 19.7751 23.1998 18.0126C23.4373 17.7251 23.8623 17.6876 24.1248 17.9501L26.8998 20.7251C27.8498 21.6751 27.5123 22.8626 26.5623 23.0876Z" fill="#2F41FF"/>
                                                    <path d="M3.37594 23.0876L5.43844 23.5751C5.90094 23.6876 6.26344 24.0376 6.36344 24.5001L6.80094 26.3376C7.03844 27.3376 8.31344 27.6501 8.97594 26.8626L12.7134 22.5626C13.0134 22.2126 12.8509 21.6626 12.4009 21.5501C10.1884 21.0126 8.20094 19.7751 6.73844 18.0126C6.50094 17.7251 6.07594 17.6876 5.81344 17.9501L3.03844 20.7251C2.08844 21.6751 2.42594 22.8626 3.37594 23.0876Z" fill="#2F41FF"/>
                                                    <path d="M15 2.5C10.1625 2.5 6.25 6.4125 6.25 11.25C6.25 13.0625 6.7875 14.725 7.7125 16.1125C9.0625 18.1125 11.2 19.525 13.6875 19.8875C14.1125 19.9625 14.55 20 15 20C15.45 20 15.8875 19.9625 16.3125 19.8875C18.8 19.525 20.9375 18.1125 22.2875 16.1125C23.2125 14.725 23.75 13.0625 23.75 11.25C23.75 6.4125 19.8375 2.5 15 2.5ZM18.825 10.975L17.7875 12.0125C17.6125 12.1875 17.5125 12.525 17.575 12.775L17.875 14.0625C18.1125 15.075 17.575 15.475 16.675 14.9375L15.425 14.2C15.2 14.0625 14.825 14.0625 14.6 14.2L13.35 14.9375C12.45 15.4625 11.9125 15.075 12.15 14.0625L12.45 12.775C12.5 12.5375 12.4125 12.1875 12.2375 12.0125L11.175 10.975C10.5625 10.3625 10.7625 9.75 11.6125 9.6125L12.95 9.3875C13.175 9.35 13.4375 9.15 13.5375 8.95L14.275 7.475C14.675 6.675 15.325 6.675 15.725 7.475L16.4625 8.95C16.5625 9.15 16.825 9.35 17.0625 9.3875L18.4 9.6125C19.2375 9.75 19.4375 10.3625 18.825 10.975Z" fill="#2F41FF"/>
                                                </svg>

                                            </div>
                                            <div>
                                                <h5 class="mb-1 fw-bold">{{ $activeSubscription->plan->name }} Member</h5>
                                                <small class="text-muted">
                                                    Renews on {{ \Carbon\Carbon::parse($activeSubscription->next_billing_date)->format('d M Y') }}
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                @endif

                                {{-- Tabs --}}
                                <ul class="nav nav-tabs" id="planTabs" role="tablist">
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link active" id="monthly-tab" data-bs-toggle="tab"
                                            data-bs-target="#monthly" type="button" role="tab">Monthly</button>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link" id="yearly-tab" data-bs-toggle="tab"
                                            data-bs-target="#yearly" type="button" role="tab">Yearly</button>
                                    </li>
                                </ul>

                                {{-- Plan Cards --}}
                                <div class="tab-content mt-4" id="planTabContent">
                                    <div class="tab-pane fade show active" id="monthly" role="tabpanel">
                                        <div class="row">
                                            @forelse ($plans->where('type', 'monthly') as $plan)
                                                <div class="col-md-4 mb-4">
                                                    <div class="card plan-card text-center shadow-sm h-100 selectable-plan"
                                                        data-plan-id="{{ $plan->id }}"
                                                        data-plan-name="{{ $plan->name }}"
                                                        data-plan-price="{{ $plan->price }}" data-plan-type="monthly">
                                                        <div class="card-body">
                                                            <h4 class="card-title mb-3">{{ $plan->name }}</h4>
                                                            <h5 class="mb-3">
                                                                {{ $plan->currency_symbol }}{{ $plan->price }} / month</h5>
                                                            @if (!empty($plan->features))
                                                                <ul class="list-unstyled mb-0">
                                                                    @foreach ($plan->features as $feature)
                                                                        <li>{{ $feature }}</li>
                                                                    @endforeach
                                                                </ul>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </div>
                                            @empty
                                                <div class="col-12 text-center text-muted">No monthly plans available.</div>
                                            @endforelse
                                        </div>
                                    </div>

                                    <div class="tab-pane fade" id="yearly" role="tabpanel">
                                        <div class="row">
                                            @forelse ($plans->where('type', 'yearly') as $plan)
                                                <div class="col-md-4 mb-4">
                                                    <div class="card plan-card text-center shadow-sm h-100 selectable-plan"
                                                        data-plan-id="{{ $plan->id }}"
                                                        data-plan-name="{{ $plan->name }}"
                                                        data-plan-price="{{ $plan->price }}" data-plan-type="yearly">
                                                        <div class="card-body">
                                                            <h4 class="card-title mb-3">{{ $plan->name }}</h4>
                                                            <h5 class="mb-3">
                                                                {{ $plan->currency_symbol }}{{ $plan->price }} / year
                                                            </h5>
                                                            @if (!empty($plan->features))
                                                                <ul class="list-unstyled mb-0">
                                                                    @foreach ($plan->features as $feature)
                                                                        <li>{{ $feature }}</li>
                                                                    @endforeach
                                                                </ul>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </div>
                                            @empty
                                                <div class="col-12 text-center text-muted">No yearly plans available.</div>
                                            @endforelse
                                        </div>
                                    </div>
                                </div>

                                {{-- Subscribe Now Button --}}
                                <div class="text-center mt-4">
                                    <button class="btn btn-primary px-4 py-2" id="subscribeNow" disabled>
                                        Subscribe Now
                                    </button>
                                </div>

                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Stripe Modal --}}
    <div class="modal fade" id="stripeModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Subscribe with Stripe</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p id="planDetails" class="fw-semibold mb-3"></p>
                    <form id="payment-form">
                        @csrf
                        <input type="hidden" id="plan_id" name="plan_id">
                        <div id="card-element" class="form-control mb-3"></div>
                        <div id="payment-message" class="mb-3 text-danger"></div>
                        {{-- <button id="submitBtn" class="btn btn-success w-100" type="submit">Pay Now</button> --}}
                        <button type="submit" id="submitBtn" class="btn btn-success w-100">
                            <span id="btnText">Pay Now</span>
                            <span id="btnSpinner" class="spinner-border spinner-border-sm ms-2 d-none" role="status"></span>
                        </button>
                    </form>
                    
                </div>
            </div>
        </div>
    </div>
@endsection

@section('page_script')
    {{-- Stripe JS --}}
    <script src="https://js.stripe.com/v3/"></script>
    <script>
        const stripe = Stripe("{{ env('STRIPE_KEY') }}");
        const elements = stripe.elements();
        const card = elements.create('card');
        card.mount('#card-element');

        let selectedPlan = null;

        // Select a plan
        document.querySelectorAll('.selectable-plan').forEach(cardEl => {
            cardEl.addEventListener('click', function() {
                document.querySelectorAll('.selectable-plan').forEach(c => c.classList.remove(
                    'border-primary'));
                this.classList.add('border-primary');
                selectedPlan = {
                    id: this.dataset.planId,
                    name: this.dataset.planName,
                    price: this.dataset.planPrice,
                    type: this.dataset.planType
                };
                document.getElementById('subscribeNow').disabled = false;
            });
        });

        // Open modal on Subscribe Now click
        document.getElementById('subscribeNow').addEventListener('click', function() {
            if (selectedPlan) {
                document.getElementById('plan_id').value = selectedPlan.id;
                document.getElementById('planDetails').innerText =
                    `${selectedPlan.name} - €${selectedPlan.price} / ${selectedPlan.type}`;
                const modal = new bootstrap.Modal(document.getElementById('stripeModal'));
                modal.show();
            }
        });

        // Handle Stripe payment
        const form = document.getElementById('payment-form');
        form.addEventListener('submit', async (e) => {
            e.preventDefault();

            const submitBtn = document.getElementById('submitBtn');
            const btnText = document.getElementById('btnText');
            const btnSpinner = document.getElementById('btnSpinner');
            const paymentMsg = document.getElementById('payment-message');

            submitBtn.disabled = true;
            btnSpinner.classList.remove('d-none');
            btnText.textContent = 'Processing...';
            paymentMsg.innerText = '';

            const { paymentMethod, error } = await stripe.createPaymentMethod({
                type: 'card',
                card: card
            });

            if (error) {
                paymentMsg.innerText = error.message;
                submitBtn.disabled = false;
                btnSpinner.classList.add('d-none');
                btnText.textContent = 'Pay Now';
                return;
            }

            const response = await fetch("{{ route('user.subscribe.process') }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('input[name=_token]').value
                },
                body: JSON.stringify({
                    plan_id: document.getElementById('plan_id').value,
                    payment_method: paymentMethod.id
                })
            });

            const result = await response.json();

            if (result.status === 'success') {
                paymentMsg.classList.remove('text-danger');
                paymentMsg.classList.add('text-success');
                paymentMsg.innerText = "Subscription successful!";
                btnSpinner.classList.add('d-none');
                btnText.textContent = 'Success ✅';
                setTimeout(() => window.location.href = "{{ route('user.subscription.index') }}", 1500);
            } else {
                paymentMsg.innerText = result.message || "Something went wrong!";
                submitBtn.disabled = false;
                btnSpinner.classList.add('d-none');
                btnText.textContent = 'Pay Now';
            }
        });
    </script>

    <style>
        .plan-card {
            cursor: pointer;
            border: 2px solid #e0e0e0;
            transition: all 0.3s ease;
        }

        .plan-card.selected {
            border-color: #007bff;
            box-shadow: 0 0 10px rgba(0, 123, 255, 0.4);
        }
    </style>
@endsection
