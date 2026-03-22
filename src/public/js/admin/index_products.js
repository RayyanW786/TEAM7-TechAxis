console.log('Fetching products...');
fetch('/api/products', {
    credentials: 'same-origin'
})
.then(res => res.json())
.then(data => {
    console.log('API response:', data);

    const container = document.getElementById('product-list');
    container.innerHTML = '';

    const products = data.data || data.items || [];

    if (products.length === 0) {
        container.innerHTML = '<p>No products found</p>';
        return;
    }

    products.forEach(product => {
        const el = document.createElement('a');
        el.href = '/admin/products/' + product.id;
        el.className = 'admin-panel';

        el.innerHTML = `
            <strong>${product.name}</strong>
            <span>£${product.price ?? product.effective_price ?? '0.00'}</span>
            <br>
            <span>Stock: ${product.stock_quantity ?? 0}</span>
        `;

        container.appendChild(el);
    });
})
.catch(err => {
    console.error('Fetch error:', err);
});