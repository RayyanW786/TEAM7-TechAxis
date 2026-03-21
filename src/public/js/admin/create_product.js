document.getElementById('image_url').addEventListener('input', function () {
    const preview = document.getElementById('image_preview');
    preview.src = this.value;
    preview.style.display = this.value ? 'block' : 'none';
});

async function loadCategories() {
    try {
        const res = await fetch('/api/categories');
        const data = await res.json();

        console.log("CATEGORIES:", data);

        const select = document.getElementById('category_id');

        const categories = data.items; 

        categories.forEach(cat => {
            const option = document.createElement('option');

            option.value = cat.category_id;
            option.textContent = cat.name;

            select.appendChild(option);
        });

    } catch (err) {
        console.error("Failed to load categories:", err);
    }
}

loadCategories();

window.createProduct = async function () {
    try {
        const res = await fetch('/api/products', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({
                name: document.getElementById('name').value,
                slug: document.getElementById('slug').value,
                description: document.getElementById('description').value,
                summary: document.getElementById('summary').value,
                price: document.getElementById('price').value,
                stock_quantity: document.getElementById('stock_quantity').value,
                category_id: document.getElementById('category_id').value,
                status: 'active'
            })
        });

        const data = await res.json();
        console.log("CREATE RESPONSE:", data);

        if (!res.ok) {
            alert(JSON.stringify(data));
            return;
        }

        const productId = data.id ?? data.product?.id;
        const imageUrl = document.getElementById('image_url').value;

        if (imageUrl) {
            const imgRes = await fetch(`/api/products/${productId}/images`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    url: imageUrl,
                    alt_text: data.name ?? "Product image"
                })
            });

            const imgData = await imgRes.json();
            console.log("IMAGE RESPONSE:", imgData);

            if (!imgRes.ok) {
                alert("Image failed: " + JSON.stringify(imgData));
                return;
            }
        }
        window.location.href = '/admin/products';

    } catch (err) {
        console.error(err);
        alert("Error creating product");
    }
};