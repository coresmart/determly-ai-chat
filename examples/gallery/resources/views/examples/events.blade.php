@extends('layout')

@section('title', $example['title'].' — Determly gallery')

@section('content')
    <div class="card">
        <ol id="event-log"></ol>
    </div>
@endsection

@push('scripts')
    <script>
        window.addEventListener('persona:chat-ready', function (event) {
            var handle = event.detail;
            var log = document.getElementById('event-log');
            if (!handle || typeof handle.on !== 'function' || !log) return;
            var names = [
                'messages:changed', 'run:started', 'run:finished', 'user:message', 'assistant:message',
                'assistant:complete', 'widget:opened', 'widget:closed', 'widget:state',
                'message:feedback', 'message:copy', 'approval:requested', 'approval:resolved',
                'stream:paused', 'stream:resuming', 'stream:resumed'
            ];
            names.forEach(function (name) {
                handle.on(name, function (payload) {
                    var item = document.createElement('li');
                    var detail = '';
                    if (name !== 'messages:changed' && payload != null) {
                        try {
                            detail = JSON.stringify(payload);
                        } catch (error) {
                            detail = '';
                        }
                        if (detail.length > 180) detail = detail.slice(0, 180) + '…';
                    }
                    item.textContent = name + (detail ? ' ' + detail : '');
                    log.appendChild(item);
                });
            });
        });
    </script>
@endpush
