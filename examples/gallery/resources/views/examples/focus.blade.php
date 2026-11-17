@extends('layout')

@section('title', $example['title'].' — Determly gallery')

@section('content')
    <div class="card">
        <p>The stock widget leaves <code>autoFocusInput</code> off. These buttons focus the open composer.</p>
        <p>
            <button type="button" id="focus-handle">Focus with determlyChat.focusInput()</button>
            <button type="button" id="focus-event">Dispatch persona:focusInput</button>
        </p>
        <p id="focus-result"></p>
    </div>
@endsection

@push('scripts')
    <script>
        function galleryFocusResult(text) {
            var node = document.getElementById('focus-result');
            if (node) node.textContent = text;
        }
        document.getElementById('focus-handle').addEventListener('click', function () {
            var handle = window.determlyChat;
            if (!handle || typeof handle.focusInput !== 'function') {
                galleryFocusResult('The handle is not ready yet.');
                return;
            }
            handle.focusInput();
            galleryFocusResult('Called determlyChat.focusInput(). autoFocusInput stays off.');
        });
        document.getElementById('focus-event').addEventListener('click', function () {
            window.dispatchEvent(new CustomEvent('persona:focusInput'));
            galleryFocusResult('Dispatched persona:focusInput. autoFocusInput stays off.');
        });
    </script>
@endpush
