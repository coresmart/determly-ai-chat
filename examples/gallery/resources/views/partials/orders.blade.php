<p>Showing {{ $status }}.</p>
<table>
    <thead>
        <tr>
            <th>Order</th>
            <th>Customer</th>
            <th>Status</th>
            <th>Total</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($orders as $order)
            <tr>
                <td>{{ $order['id'] }}</td>
                <td>{{ $order['customer'] }}</td>
                <td>{{ $order['status'] }}</td>
                <td>{{ $order['total'] }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="4">No orders</td>
            </tr>
        @endforelse
    </tbody>
</table>
