@extends('layout')

@section('title', $example['title'].' — Determly gallery')

@section('content')
    <div class="card">
        <p>This page builds a <code>Theme</code> in PHP and passes it to the widget. The launcher and the panel use a teal accent, a white surface, a serif font, and large corners.</p>
        <p>An app sets the same values with <code>DETERMLY_CHAT_ACCENT</code> and the other appearance env vars, or with <code>config/determly-chat.php</code>. The steps are in the installation guide.</p>
    </div>
@endsection
