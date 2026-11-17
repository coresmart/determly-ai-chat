@extends('layout')

@section('title', $example['title'].' — Determly gallery')

@section('content')
    <div class="card">
        <p>Allow the confirm in the chat. The trail shoe is added here only after that page tool runs.</p>
        <h2>Cart</h2>
        <ul data-cart>
            <li data-empty>Cart is empty.</li>
        </ul>
    </div>
@endsection

@push('scripts')
    <script>
        window.galleryRegister(function (modelContext) {
            modelContext.registerTool({
                name: 'add_to_cart',
                description: 'Add a product to the cart on this page.',
                inputSchema: {
                    type: 'object',
                    properties: {
                        sku: { type: 'string' },
                        title: { type: 'string' }
                    },
                    required: ['sku', 'title']
                },
                execute: async function (args) {
                    if (typeof args === 'string') {
                        try { args = JSON.parse(args); } catch (error) { args = {}; }
                    }
                    var title = (args && args.title) || 'Item';
                    var sku = (args && args.sku) || '';
                    var list = document.querySelector('[data-cart]');
                    var empty = list && list.querySelector('[data-empty]');
                    if (empty) empty.remove();
                    if (list) {
                        var item = document.createElement('li');
                        item.textContent = title + ' (' + sku + ')';
                        list.appendChild(item);
                    }
                    return { content: [{ type: 'text', text: title + ' added to the cart.' }] };
                }
            });
        });
    </script>
@endpush
