@extends('layout')

@section('title', $example['title'].' — Determly gallery')

@section('content')
    <div class="card">
        <p>The product card is inside this host's shadow root. A normal query of the light DOM does not see it.</p>
        <div id="shadow-host"></div>
    </div>
@endsection

@push('scripts')
    <script>
        (function () {
            var host = document.getElementById('shadow-host');
            var root = host.attachShadow({ mode: 'open' });
            root.innerHTML = '<article data-product style="font:16px/1.4 sans-serif"><h2 data-title style="margin:0 0 4px">Trail shoe</h2><p data-price style="margin:0">$129</p><p data-sku style="margin:0;color:#475569">SHOE-014</p></article>';
            window.galleryRegister(function (modelContext) {
                modelContext.registerTool({
                    name: 'read_product',
                    description: 'Read the product card inside the shadow root on this page.',
                    inputSchema: { type: 'object', properties: {} },
                    annotations: { readOnlyHint: true },
                    execute: async function () {
                        var card = host.shadowRoot && host.shadowRoot.querySelector('[data-product]');
                        if (!card) {
                            return { content: [{ type: 'text', text: 'No product in the shadow root.' }] };
                        }
                        var title = card.querySelector('[data-title]').textContent;
                        var price = card.querySelector('[data-price]').textContent;
                        var sku = card.querySelector('[data-sku]').textContent;
                        return { content: [{ type: 'text', text: title + ', ' + price + ', sku ' + sku }] };
                    }
                });
            });
        })();
    </script>
@endpush
