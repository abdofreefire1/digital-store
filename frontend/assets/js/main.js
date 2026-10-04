// frontend/assets/js/main.js

document.addEventListener('DOMContentLoaded', () => {
    loadProducts();
});

async function loadProducts() {
    const loadingEl = document.getElementById('loading');
    const gridEl = document.getElementById('products-grid');
    
    try {
        const response = await ApiService.get('/products');
        
        loadingEl.classList.add('hidden');
        gridEl.classList.remove('hidden');

        if (response.success && response.data.length > 0) {
            gridEl.innerHTML = response.data.map(product => renderProductCard(product)).join('');
        } else {
            gridEl.innerHTML = '<div class="col-span-full text-center text-gray-500 py-10">لا توجد منتجات حالياً.</div>';
        }
    } catch (error) {
        loadingEl.innerHTML = '<div class="text-red-500 text-center w-full">حدث خطأ أثناء تحميل المنتجات.</div>';
    }
}

function renderProductCard(product) {
    const discountBadge = product.discount_type ? 
        `<span class="absolute top-2 right-2 bg-red-500 text-white text-xs font-bold px-2 py-1 rounded">
            ${product.discount_type === 'percentage' ? 'خصم ' + product.discount_value + '%' : 'عرض خاص'}
        </span>` : '';

    const oldPrice = product.old_price_dzd ? 
        `<span class="text-sm text-gray-500 line-through ml-2">${product.old_price_dzd} د.ج</span>` : '';

    const image = product.image ? product.image : 'https://placehold.co/400x300?text=Product';

    return `
        <div class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-xl transition-shadow relative flex flex-col">
            ${discountBadge}
            <img src="${image}" alt="${product.name}" class="w-full h-48 object-cover">
            <div class="p-4 flex flex-col flex-grow">
                <span class="text-xs text-primary font-semibold uppercase tracking-wider mb-1">${product.category_name || 'عام'}</span>
                <h3 class="text-lg font-bold text-gray-900 mb-2">${product.name}</h3>
                <div class="mt-auto pt-4 flex items-center justify-between">
                    <div>
                        <span class="text-xl font-bold text-gray-900">${product.final_price_dzd} د.ج</span>
                        ${oldPrice}
                    </div>
                </div>
                <a href="product.html?id=${product.id}" class="mt-4 w-full block text-center bg-primary hover:bg-indigo-700 text-white font-medium py-2 px-4 rounded transition-colors">
                    شراء الآن
                </a>
            </div>
        </div>
    `;
}
