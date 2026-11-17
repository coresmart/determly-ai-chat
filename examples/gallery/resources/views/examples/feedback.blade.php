@extends('layout')

@section('title', $example['title'].' — Determly gallery')

@section('content')
    <div class="card" data-activity>
        @include('partials.activity', ['activity' => $activity])
    </div>
@endsection

@push('scripts')
    <script>
        function galleryPost(url, body) {
            return fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'text/html',
                    'X-CSRF-TOKEN': window.galleryToken(),
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify(body || {})
            }).then(function (response) { return response.text(); }).then(function (html) {
                var node = document.querySelector('[data-activity]');
                if (node) node.innerHTML = html;
            });
        }
        window.addEventListener('persona:chat-ready', function (event) {
            var handle = event.detail;
            if (!handle || typeof handle.on !== 'function') return;
            handle.on('message:feedback', function (feedback) {
                galleryPost('/examples/feedback/feedback', {
                    type: feedback && feedback.type,
                    messageId: feedback && feedback.messageId
                });
            });
            handle.on('message:copy', function (message) {
                galleryPost('/examples/feedback/copied', {
                    id: message && message.id
                });
            });
        });
    </script>
@endpush
