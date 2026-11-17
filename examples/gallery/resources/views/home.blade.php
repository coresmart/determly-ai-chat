@extends('layout')

@section('title', 'Determly gallery')

@section('content')
    <h1>Determly gallery</h1>
    <p>Runnable Persona examples on the Laravel widget. The scripted pages dispatch to <code>/examples/{slug}/dispatch</code> in echo mode. <a href="/examples/laravel-ai">Laravel AI</a> and <a href="/examples/prism">Prism</a> send the sentence to a model. Those two need <code>DETERMLY_CHAT_PROVIDER</code>, <code>DETERMLY_CHAT_MODEL</code>, and the provider key.</p>
    <ul>
        @foreach ($examples as $slug => $page)
            <li>
                <a href="/examples/{{ $slug }}">{{ $page['title'] }}</a>
                — {{ $page['summary'] }}
            </li>
        @endforeach
    </ul>
    <p>Not in this gallery: {{ implode(', ', $skipped) }}.</p>
@endsection
