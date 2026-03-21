window.updateProduct = function () {
    fetch('/api/products/' + window.location.pathname.split('/').pop(), {
        method: 'PATCH',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({
            name: document.getElementById('name').value,
            description: document.getElementById('description').value,
            price: document.getElementById('price').value,
            stock_quantity: document.getElementById('stock_quantity').value,
            status: 'active'
        })
    })
    .then(async res => {
        const data = await res.json();
        console.log("RESPONSE:", data);

        if (!res.ok) {
            alert(JSON.stringify(data));
            return;
        }

        alert("Product updated");
    });
};

window.deleteProduct = function () {
    if (!confirm("Delete this product?")) return;

    fetch('/api/products/' + window.location.pathname.split('/').pop(), {
        method: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(() => {
        window.location.href = '/admin/products';
    });
};