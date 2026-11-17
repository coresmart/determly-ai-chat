@extends('layout')

@section('title', $example['title'].' — Determly gallery')

@section('content')
    <div class="card">
        @if ($model['ready'])
            <p>Your sentence is sent to {{ $model['library'] }} ({{ $model['provider'] }} / {{ $model['model'] }}). The model chooses <code>lookup_order</code> or <code>query_orders</code>. The runner executes that tool, then the model writes the answer.</p>
        @else
            <p>{{ $model['missing'] }}</p>
        @endif
        <p class="hint">The scripted Agent Loop page does not do this. These starters name a customer, not an order id, so a number search cannot answer them.</p>
    </div>
    @include('partials.orders', ['orders' => $orders, 'status' => 'all'])
@endsection
