@extends('layout')

@section('title', $example['title'].' — Determly gallery')

@section('content')
    <div class="card" data-refunds>
        @include('partials.refunds', ['refunds' => $refunds])
    </div>
@endsection

@push('scripts')
    <script>
        window.addEventListener('persona:chat-ready', function (event) {
            var handle = event.detail;
            if (!handle || typeof handle.on !== 'function') return;
            function refresh() {
                fetch('/examples/approval/refunds', { headers: { 'Accept': 'text/html' } })
                    .then(function (response) { return response.text(); })
                    .then(function (html) {
                        var node = document.querySelector('[data-refunds]');
                        if (node) node.innerHTML = html;
                    });
            }
            handle.on('approval:resolved', refresh);
            handle.on('assistant:complete', refresh);
        });
    </script>
@endpush
