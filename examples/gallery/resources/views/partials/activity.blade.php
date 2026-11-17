@if ($activity === [])
    <p>No feedback yet. Rate or copy the assistant message.</p>
@else
    <ul>
        @foreach ($activity as $row)
            <li>{{ $row['event'] ?? '' }} — {{ $row['summary'] ?? '' }} <span class="hint">{{ $row['at'] ?? '' }}</span></li>
        @endforeach
    </ul>
@endif
