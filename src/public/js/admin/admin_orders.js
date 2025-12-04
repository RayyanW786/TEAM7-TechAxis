document.addEventListener('DOMContentLoaded', function() {
    loadOrders();
});

function loadOrders() {
    const ordersList =  document.querySelector('.orders-list');
    ordersList.innerHTML = 'Loading orders...';

    fetch('/api/orders')
        .then(response => response.json())
        .then(data => fetchOrders(data))
        .catch(error => {
            console.error('Error fetching orders:', error);
            ordersList.innerHTML = 'Failed to load orders.';
        });

    function fetchOrders(data) {
        const ordersList = document.querySelector('.orders-list');
        if (!data || data.length === 0) {
            ordersList.innerHTML = 'No orders found.';
            return;
        }
        let html = `
            <table>
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Customer ID</th>
                        <th>Total Amount</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
        `;

        data.data.forEach(order => {
            html += loadOrderRows(order);
        });
        html += `
                </tbody>
            </table>
        `;
        ordersList.innerHTML = html;

    }
    function loadOrderRows(order) {
        const userId = order.user_id ? order.user_id : 'Guest';
        const total = Number(order.total_amount ?? 0);
        const status = order.status ?? 'Pending';
        const date = order.created_at ? new Date(order.created_at).toLocaleDateString() : 'N/A';
        return `
            <tr>
                <td>${order.id}</td>
                <td>${userId}</td>
                <td>$${total.toFixed(2)}</td>
                <td>${status}</td>
                <td>${date}</td>
                <td><button class="view-order-btn" data-order-id="${order.id}">View</button></td>
            </tr>
        `;
    }
    document.addEventListener('click', function(event) {
        if (event.target.classList.contains('view-order-btn')) {
            const orderId = event.target.getAttribute('data-order-id');
            window.location.href = `/orders/${orderId}`;
        }
    });
}