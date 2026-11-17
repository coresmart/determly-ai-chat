@if ($refunds === [])
    <p>No refund has been recorded. Approve the tool in the chat and this list updates.</p>
@else
    <ul>
        @foreach ($refunds as $refund)
            <li>Order {{ $refund['order_id'] ?? '' }} refunded {{ $refund['amount'] ?? '' }} at {{ $refund['at'] ?? '' }}</li>
        @endforeach
    </ul>
@endif
