@extends('layout')

@section('title', 'Determly gallery')

@section('content')
    <h1>Determly gallery</h1>
    <p>Runnable Persona examples on the Laravel widget. Each page dispatches to <code>/examples/{slug}/dispatch</code> in echo mode, with no model key.</p>
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
