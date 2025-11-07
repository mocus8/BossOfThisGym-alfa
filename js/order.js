// Функция очистки интерфейса
function clearOrderInterface(previousType) {
    if (previousType === 'delivery') {
        // Очищаем интерфейс доставки
        document.getElementById('delivery-address').value = '';
        document.getElementById('order-right-delivery-address').textContent = 'не указан';
        document.getElementById('modal-error-address-empty').classList.remove('open');
        document.getElementById('modal-error-address-not-found').classList.remove('open');
        document.getElementById('modal-error-address-timeout').classList.remove('open');
        document.getElementById('error-pay-delivery-no-address').classList.remove('open');
        document.getElementById('error-pay-pickup-no-address').classList.remove('open');
    } else {
        // Очищаем интерфейс самовывоза
        document.getElementById('order-right-pickup-address').textContent = 'не указан';
        
        // Сбрасываем все кнопки выбора магазина
        document.querySelectorAll('[id^="select-pickup-store-"]').forEach(btn => {
            btn.textContent = 'Заберу отсюда';
            btn.style.cursor = 'pointer';
            btn.style.pointerEvents = 'auto';
            btn.disabled = false;
            btn.removeAttribute('data-listener-added');
        });
    }
}

// Функция обновления типа доставки в БД
function updateDeliveryTypeInDB(deliveryType) {
    fetchWithRetry('src/updateDeliveryType.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({delivery_type: deliveryType})
    })
    .then(data => {
        if (!data.success) {
            console.error('Ошибка обновления типа доставки:', data.message);
        }
    })
    .catch(error => {
        console.error('Ошибка сети:', error);
    });
}

// Обработчик переключения типа доставки
document.querySelector('.order_types').addEventListener('click', function(e) {
    // e.target - элемент, на котором произошел клик
    // e.currentTarget - элемент, на котором висит обработчик (.order_types)
    
    // Проверяем, был ли клик по кнопке .order_type
    const target = e.target.closest('.order_type');
    if (!target) return; // если клик был не по кнопке - выходим
    
    // Дальше работаем с target (нажатой кнопкой)
    const isDelivery = target.id === 'order-type-delivery';
    const previousType = document.getElementById('order-type-delivery').classList.contains('chosen') ? 'delivery' : 'pickup';
    
    // если кликаем на ту же кнопку
    if ((isDelivery && previousType === 'delivery') || (!isDelivery && previousType === 'pickup')) {
        return;
    }

    // ОЧИСТКА ИНТЕРФЕЙСА ПЕРЕЕД ПЕРЕКЛЮЧЕНИЕМ
    clearOrderInterface(previousType);

    // Переключение модалок
    // toggle автоматически добавляет или удаляет класс
    document.getElementById('modal-order-type-delivery').classList.toggle('open', isDelivery);
    document.getElementById('modal-order-type-pickup').classList.toggle('open', !isDelivery);
    
    // Переключение стилей кнопок выбора типа доставки
    document.getElementById('order-type-delivery').classList.toggle('chosen', isDelivery);
    document.getElementById('order-type-pickup').classList.toggle('chosen', !isDelivery);
    
    // ОБНОВЛЯЕМ ТИП ДОСТАВКИ В БД
    updateDeliveryTypeInDB(isDelivery ? 'delivery' : 'pickup');
});






document.querySelectorAll('.order_right_pay_button').forEach(button => {
    button.addEventListener('click', async function() {
        const orderId = this.getAttribute('data-order-id');
        const isDelivery = document.getElementById('order-type-delivery').classList.contains('chosen');
        const deliveryAddress = document.getElementById('order-right-delivery-address').innerText;
        const pickupAddress = document.getElementById('order-right-pickup-address').innerText;
        
        if (isDelivery && deliveryAddress.includes("не указан")) {
            document.getElementById("error-pay-delivery-no-address").classList.add("open");
        } else if (!isDelivery && pickupAddress.includes("не указан")) {
            document.getElementById("error-pay-pickup-no-address").classList.add("open");
        } else {
            try {
                // ПЕРЕДАЕМ order_id В POST
                const response = await fetch('/create_payment.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        order_id: orderId
                    })
                });
                
                console.log('Status:', response.status);
                const rawText = await response.text();
                console.log('Raw response:', rawText);
                
                const result = JSON.parse(rawText);
                
                if (result.confirmation_url) {
                    window.location.href = result.confirmation_url;
                } else {
                    throw new Error(result.error || 'Payment error');
                }
            } catch (error) {
                console.error('Full error:', error);
                alert('Ошибка: ' + error.message);
            }
        }
    });
});




//версия без отладки
// document.querySelectorAll('.order_right_pay_button').forEach(button => {
//     button.addEventListener('click', async function() {
//         const isDelivery = document.getElementById('order-type-delivery').classList.contains('chosen');
//         const deliveryAddress = document.getElementById('order-right-delivery-address').innerText;
//         const pickupAddress = document.getElementById('order-right-pickup-address').innerText;
        
//         // Закрываем все ошибки сначала
//         // closeErrorModal("error-pay-delivery-no-address");
//         // closeErrorModal("error-pay-pickup-no-address");
        
//         if (isDelivery && deliveryAddress.includes("не указан")) {
//             document.getElementById("error-pay-delivery-no-address").classList.add("open");
//         } else if (!isDelivery && pickupAddress.includes("не указан")) {
//             document.getElementById("error-pay-pickup-no-address").classList.add("open");
//         } else {
//             //здесь логика оплаты черз юкассу
//             try {
//                 const response = await fetch('/create_payment.php', {
//                     method: 'POST'
//                 });
                
//                 const result = await response.json();
                
//                 if (result.confirmation_url) {
//                     window.location.href = result.confirmation_url;
//                 } else {
//                     throw new Error(result.error || 'Payment error');
//                 }
//             } catch (error) {
//                 alert('Ошибка: ' + error.message);
//             }
//         }
//     });
// });

document.addEventListener('click', function(e) {
    if (e.target.classList.contains('order_right_pay_button')) {
        return;
    }
    if (e.target.closest('.error_pay_no_address')) {
        return;
    }

    closeErrorModal("error-pay-delivery-no-address");
    closeErrorModal("error-pay-pickup-no-address");
});