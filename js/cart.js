// Функция для обновления счетчика в header
function updateHeaderCounter(count) {
    const headerCounter = document.getElementById('header-cart-counter');
    if (headerCounter) {
        headerCounter.textContent = count;
    }
}

// Функция для обновления счетчика на странице товара
function updateProductCounter(count) {
    const productCounter = document.getElementById('product-cart-counter');
    if (productCounter) {
        productCounter.textContent = count;
    }
}

// Функция для обновления общей суммы
function updateTotalPrice(totalPrice) {
    document.querySelectorAll('[data-total-counter]').forEach(el => {
        el.textContent = totalPrice;
    });
}

// Функция для обновления количества товара
function updateProductQuantity(productId, newAmount, newTotal) {
    const amountElement = document.getElementById(`cart-counter-${productId}`);
    const priceElement = document.querySelector(`[data-product-id="${productId}"] .cart_product_price`);
    
    if (amountElement) amountElement.textContent = newAmount;
    if (priceElement) priceElement.textContent = newTotal + ' ₽';
}

// Функция для удаления товара из DOM
function removeProductFromDOM(productId) {
    const productElement = document.querySelector(`.cart_product[data-product-id="${productId}"]`);
    if (productElement) {
        productElement.remove();
    }
}

// Функция для обновления общего количества товаров
function updateOrderCartCount(totalCount) {
    const orderCountElement = document.getElementById('order-cart-count');
    if (orderCountElement) {
        orderCountElement.textContent = totalCount;
    }
}

// Функция для проверки пустой корзины
function checkEmptyCart(totalCount) {
    const cartProducts = document.querySelector('.cart_products');
    const emptyCart = document.querySelector('.cart_empty');
    const orderButton = document.querySelector('.order-button-link');
    if (totalCount === 0 && !emptyCart) {
        cartProducts.innerHTML = '<div class="cart_empty">Корзина пуста</div>';
        orderButton.remove();
    } else if (totalCount > 0 && emptyCart) {
        emptyCart.remove();
    }
}

// Универсальная функция для работы с корзиной
function handleCartAction(productId, action) {
    const button = event.target;
    button.disabled = true;
    
    fetchWithRetry('src/addRemoveCart.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `product_id=${productId}&action=${action}`
    })
    .then(data => {
        button.disabled = false;
        
        if (data.success) {
            // Обновляем все счетчики и данные
            updateHeaderCounter(data.cart.totalCount);
            updateOrderCartCount(data.cart.totalCount);
            updateTotalPrice(data.cart.totalPrice);
            
            // Обновляем или удаляем товары
            data.cart.items.forEach(item => {
                updateProductQuantity(item.id, item.amount, item.total);
            });
            
            // Проверяем пустую корзину
            checkEmptyCart(data.cart.totalCount);
            
            // Удаляем товары, которых нет в ответе сервера
            const existingProductIds = data.cart.items.map(item => item.id.toString());
            document.querySelectorAll('.cart_product').forEach(product => {
                const productId = product.dataset.productId;
                if (!existingProductIds.includes(productId)) {
                    product.remove();
                }
            });
            
        } else {
            alert(data.message || 'Ошибка при работе с корзиной');
        }
    })
    .catch(error => {
        button.disabled = false;
        alert('Ошибка сети при работе с корзиной');
    });
}

// Функция для добавления товара в корзину со страниц товаров
function manipulateCartFromProductPage(productId, action) {
    fetchWithRetry('src/addRemoveCart.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'product_id=' + productId + '&action=' + action
    })
    .then(data => {
        if (data.success) {
            // Обновляем счетчик в header
            updateHeaderCounter(data.cart.totalCount);
            // Обновляем счетчик на странице товара
            updateProductCounter(data.cart.items.find(item => item.id == productId)?.amount || 0);
        } else {
            alert(data.message || 'Ошибка при работе с корзиной');
        }
    })
    .catch(error => {
        alert('Ошибка сети при работе с корзиной', error);
    });
}

// Единый обработчик для всех действий с корзиной
document.addEventListener('click', function (event) {
    // Убавление со страниц товаров
    if (event.target.hasAttribute('data-product-subtract-cart')) {
        event.preventDefault();
        const productId = event.target.getAttribute('data-product-id');
        manipulateCartFromProductPage(productId, 'subtract_cart');
    }
    // Добавление в корзину со страниц товаров
    if (event.target.hasAttribute('data-product-add-cart')) {
        event.preventDefault();
        const productId = event.target.getAttribute('data-product-id');
        manipulateCartFromProductPage(productId, 'add_to_cart');
    }
    // Действия внутри корзины
    else if (event.target.hasAttribute('data-subtract-cart')) {
        const productId = event.target.getAttribute('data-product-id');
        handleCartAction(productId, 'subtract_cart');
    } 
    else if (event.target.hasAttribute('data-add-cart')) {
        const productId = event.target.getAttribute('data-product-id');
        handleCartAction(productId, 'add_to_cart');
    } 
    else if (event.target.hasAttribute('data-remove-cart')) {
        const productId = event.target.getAttribute('data-product-id');
        handleCartAction(productId, 'remove_cart');
    }
});