@extends('layout')

@section('title', $example['title'].' — Determly gallery')

@section('content')
    <div data-orders>
        @include('partials.orders', ['orders' => $orders, 'status' => 'all'])
    </div>
@endsection

@push('scripts')
    <script>
        window.galleryRegister(function (modelContext) {
            modelContext.registerTool({
                name: 'filter_orders',
                description: 'Filter the orders table on this page.',
                inputSchema: {
                    type: 'object',
                    properties: {
                        status: { type: 'string', description: 'shipped, open, paid, or all' }
                    },
                    required: ['status']
                },
                annotations: { readOnlyHint: true },
                execute: async function (args) {
                    var status = (args && args.status) || 'shipped';
                    var response = await fetch('/examples/data/table?status=' + encodeURIComponent(status), {
                        headers: { 'Accept': 'text/html' }
                    });
                    var html = await response.text();
                    var node = document.querySelector('[data-orders]');
                    if (node) node.innerHTML = html;
                    return { content: [{ type: 'text', text: 'Orders table filtered to ' + status + '.' }] };
                }
            });
        });
    </script>
@endpush
