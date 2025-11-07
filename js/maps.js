
// СКРЫВАЕМ ОШИБКИ АДРЕСА ПРИ ЗАГРУЗКЕ СТРАНИЦЫ
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.error_address_not_found').forEach(block => {
        block.classList.remove('open');
    });
});


//  Показ ошибки
function showMapError(type = 'all') {

    console.error('Ошибка Яндекс.Карт:', type);
    
    let className = `error_${type}_map`;
    if (type === 'all') {
        // Показываем все ошибки
        document.querySelectorAll('[class*="error_"]').forEach(block => {
            block.classList.add('open');
        });
    } else {
        // Показываем конкретную ошибку
        document.querySelectorAll(`.${className}`).forEach(block => {
            block.classList.add('open');
        });
    }
}


// Карта магазинов
function initStoresMap(){
    const map = new ymaps.Map('stores-map', { 
        center: [55.76, 37.64], 
        zoom: 10 
    });









    // Загружаем магазины из БД
    fetch('src/getStores.php')
    .then(response => response.json())
    .then(stores => {
        stores.forEach((store) => {
            if (store.coordinates && store.coordinates.length === 2) {
                const yandexMapsUrl = `https://yandex.ru/maps/?text=${encodeURIComponent(store.address.replace(/<br>/g, ', '))}`;
                map.geoObjects.add(new ymaps.Placemark(store.coordinates, {
                    balloonContent: `
                        <div style="min-width: 250px; font-family: 'Jost', Arial; font-size: 16px; color: black;">
                            <div style="margin-bottom: 15px;">
                                <strong>${store.address}</strong><br>
                                <div style="margin-top: 10px;">${store.work_hours}</div>
                                <a href='tel: ${store.phone}' class="colour_href">
                                    <div style="margin-top: 10px;">${store.phone}</div>
                                </a>
                            </div>
                            <div style="text-align: center;">
                                <a href="${yandexMapsUrl}" 
                                target="_blank" 
                                style="display: inline-block; background: #4B4B4B; color: black; border: 0.15vw solid black; padding: 3% 5%; border-radius: 0.5vw;">
                                    Открыть в Картах
                                </a>
                            </div>
                        </div>
                    `
                }, {
                    iconLayout: 'default#image',
                    iconImageHref: '/img/custom_map_pin.png',
                    iconImageSize: [60, 55],
                    iconImageOffset: [-20, -40]
                }));
            }
        });
        map.setBounds(map.geoObjects.getBounds());
    })
    .catch(error => {
        console.error('Ошибка загрузки магазинов:', error);
    });

    // const stores = [
    //     [55.666208, 37.816980, 'Москва, Ул. Головачёва, вл8с1<br><br>Время работы:<br>10:00 - 23:00 (будние дни)<br>10:00 - 21:00 (выходные и праздники)<br>'],
    //     [55.846623, 37.600648, 'Москва, Ул. Станционная, 11<br><br>Время работы:<br>10:00 - 22:00 (будние дни)<br>10:00 - 21:00 (выходные и праздники)<br>'],
    //     [56.345, 37.520, 'Дмитров, Историческая площадь, 11<br><br>Время работы:<br>10:00 - 23:00 (будние дни)<br>10:00 - 21:00 (выходные и праздники)<br>']
    // ];
    
    // stores.forEach(store => {
    //     const yandexMapsUrl = `https://yandex.ru/maps/?text=${encodeURIComponent(store[2].replace(/<br>/g, ', '))}`;
        
    //     map.geoObjects.add(new ymaps.Placemark([store[0], store[1]], {
    //         balloonContent: `
    //             <div style="min-width: 250px; font-family: 'Jost', Arial; font-size: 16px; color: black;">
    //                 <div style="margin-bottom: 15px;">
    //                     ${store[2].replace(/<br>/g, '<br>')}
    //                 </div>
    //                 <div style="text-align: center;">
    //                     <a href="${yandexMapsUrl}" 
    //                     target="_blank" 
    //                     style="display: inline-block; background: #4B4B4B; color: black; border: 0.15vw solid black; padding: 3% 5%; border-radius: 0.5vw;">
    //                         Открыть в Картах
    //                     </a>
    //                 </div>
    //             </div>
    //         `
    //     }, {
    //         iconLayout: 'default#image',
    //         iconImageHref: '/img/custom_map_pin.png',
    //         iconImageSize: [60, 55],
    //         iconImageOffset: [-20, -40]
    //     }));
    // });
    
    // map.setBounds(map.geoObjects.getBounds());
}

// Карта доставки
function initDeliveryMap(){
    const map = new ymaps.Map('delivery-map', { 
        center: [55.76, 37.64], 
        zoom: 10,
        controls: ['zoomControl']
    });
    
    let marker = null;
    const addressInput = document.getElementById('delivery-address');
    const searchBtn = document.getElementById('delivery-search-btn');
    const typeToPickupBtn = document.getElementById('order-type-pickup');

    // Санитизация ввода
    function sanitizeAddress(address) {
        return address
            .replace(/[<>"`\\\/]/g, '') // Удаляем самые опасные
            .replace(/'/g, '’')         // Заменяем апостроф на красивый
            .replace(/&/g, 'и')         // Заменяем & на "и"
            .replace(/\s+/g, ' ')
            .trim()
            .substring(0, 200); // Ограничить длину
    }

    // Обработка адреса
    function processAddress(address) {
        // Скрываем все ошибки адреса
        document.getElementById('modal-error-address-empty').classList.remove('open');
        document.getElementById('modal-error-address-not-found').classList.remove('open');
        document.getElementById('modal-error-address-timeout').classList.remove('open');

        if (!address.trim()) {
            setTimeout(() => {
                document.getElementById('modal-error-address-empty').classList.add('open');
            }, 10);
            return;
        }

        if (searchBtn) {
            searchBtn.disabled = true;
            searchBtn.textContent = 'Поиск...';
        }
        
        const normalizedAddress = address.replace(/[.,]/g, '').replace(/\s+/g, ' ').trim();
        
        // ФЛАГ ТАЙМАУТА - ДОБАВЛЯЕМ ЭТУ ПЕРЕМЕННУЮ
        let isGeocodeTimedOut = false;
        
        // ДОБАВЛЯЕМ ТАЙМАУТ
        const geocodeTimeout = setTimeout(() => {
            console.error('Таймаут геокодирования');
            isGeocodeTimedOut = true; // УСТАНАВЛИВАЕМ ФЛАГ
            if (searchBtn) {
                searchBtn.disabled = false;
                searchBtn.textContent = 'Найти';
            }
            // Показываем ошибку таймаута
            document.getElementById('modal-error-address-timeout').classList.add('open');
        }, 10000); // время таймаута - 10 сек 
        
        ymaps.geocode(normalizedAddress).then(function(res) {
            // ПРОВЕРЯЕМ ФЛАГ - ЕСЛИ ТАЙМАУТ УЖЕ СРАБОТАЛ, ВЫХОДИМ
            if (isGeocodeTimedOut) {
                return;
            }
            
            clearTimeout(geocodeTimeout); // ОЧИЩАЕМ ТАЙМАУТ ПРИ УСПЕХЕ
            
            const firstResult = res.geoObjects.get(0);
            
            if (!firstResult) {
                setTimeout(() => {
                    document.getElementById('modal-error-address-not-found').classList.add('open');
                }, 10);
                if (searchBtn) {
                    searchBtn.disabled = false;
                    searchBtn.textContent = 'Найти';
                }
                return;
            }

            showAddressOnMap(firstResult);
            if (searchBtn) {
                searchBtn.disabled = false;
                searchBtn.textContent = 'Найти';
            }
        }).catch(error => {
            // ПРОВЕРЯЕМ ФЛАГ - ЕСЛИ ТАЙМАУТ УЖЕ СРАБОТАЛ, ВЫХОДИМ
            if (isGeocodeTimedOut) {
                return;
            }
            
            clearTimeout(geocodeTimeout); // ОЧИЩАЕМ ТАЙМАУТ ПРИ ОШИБКЕ
            console.error('Ошибка геокодирования:', error);
            if (searchBtn) {
                searchBtn.disabled = false;
                searchBtn.textContent = 'Найти';
            }
            // При других ошибках тоже показываем таймаут
            document.getElementById('modal-error-address-timeout').classList.add('open');
        });
    }

    // Показать адрес на карте
    function showAddressOnMap(geoObject) {
        const coords = geoObject.geometry.getCoordinates();
        const address = geoObject.getAddressLine();
        const postalCode = geoObject.properties.get('metaDataProperty.GeocoderMetaData.Address.postal_code');
        
        if (marker) {
            map.geoObjects.remove(marker);
        }
        
        marker = new ymaps.Placemark(coords, {
            balloonContent: `
                <div style="min-width: 250px; font-size: 120% !important; font-family: 'Jost', Arial !important;">
                    <strong>Адрес доставки:</strong><br>
                    ${sanitizeAddress(address.replace(/^Россия,\s*/, ''))}
                    <div style="margin-top: 5%; text-align: center;">
                        <button id="select-delivery-address" 
                                style="border: 0.15vw solid black; padding: 3% 5%; padding-left: 2%; border-radius: 0.5vw; cursor: pointer; 
                                background: #4B4B4B !important;">
                            ✅ Доставить сюда
                        </button>
                    </div>
                </div>
            `
        }, {
            preset: 'islands#redDotIcon',
            balloonCloseButton: true,
            balloonAutoPan: true
        });
        
        map.geoObjects.add(marker);
        map.setCenter(coords, 16);
        
    // Обработчик события открытия балуна
        marker.events.add('balloonopen', function() {
            const currentAddress = document.getElementById('order-right-delivery-address').innerText;
            const cleanAddress = sanitizeAddress(address.replace(/^Россия,\s*/, ''));
            const isSelected = currentAddress === cleanAddress;
            
            // Мгновенно обновляем кнопку если адрес выбран
            if (isSelected) {
                setTimeout(() => {
                    const selectBtn = document.getElementById('select-delivery-address');
                    if (selectBtn) {
                        selectBtn.textContent = '✅ Адрес выбран';
                        selectBtn.style.cursor = 'default';
                        selectBtn.style.pointerEvents = 'none';
                        selectBtn.disabled = true;
                    }
                }, 0); // Минимальная задержка
            } else {
                // Обычная логика для невыбранного адреса
                setTimeout(() => {
                    const selectBtn = document.getElementById('select-delivery-address');
                    if (selectBtn && !selectBtn.hasAttribute('data-listener-added')) {
                        selectBtn.setAttribute('data-listener-added', 'true');
                        selectBtn.addEventListener('click', function() {
                            const cleanAddress = sanitizeAddress(address.replace(/^Россия,\s*/, ''));
                            
                            fetchWithRetry('src/saveDeliveryAddress.php', {
                                method: 'POST',
                                headers: { 'Content-Type': 'application/json' },
                                body: JSON.stringify({delivery_type: 'delivery', address: cleanAddress, postalCode: postalCode})
                            })
                            .then(data => {
                                if (data.success) {
                                    selectBtn.textContent = '✅ Адрес выбран';
                                    selectBtn.style.cursor = 'default';
                                    selectBtn.style.pointerEvents = 'none';
                                    selectBtn.disabled = true;
                                    document.getElementById('order-right-delivery-address').innerText = cleanAddress;
                                }
                            });
                        });
                    }
                }, 100);
            }
        });
        
        marker.balloon.open();
    }

    function clearDeliveryMap() {
        map.geoObjects.remove(marker);
        map.setCenter([55.76, 37.64], 8);
    };
    
    // старый код, кастомные подсказки без dadata
    // const suggestionsContainer = document.createElement('div');
    // suggestionsContainer.className = 'address-suggestions';

    // function showSuggestions(suggestions) {
    //     clearSuggestions();
        
    //     if (suggestions.length === 0) return;
        
    //     suggestions.forEach((item, index) => {
    //         const div = document.createElement('div');
    //         div.className = 'suggestion-item';
    //         div.textContent = formatAddress(item.getAddressLine());
            
    //         div.addEventListener('click', function() {
    //             selectSuggestion(item);
    //         });
            
    //         suggestionsContainer.appendChild(div);
    //     });
        
    //     suggestionsContainer.style.display = 'block';
    //     addressInput.classList.add('has-suggestions');
    // }

    //ИНИЦИАЛИЗАЦИЯ DADATA КЛЮЧ ЧЕРЕЗ
    if (addressInput) {
        fetch('src/serviceProxy.php')
            .then(response => response.json())
            .then(data => {
                if (data.key && window.Dadata) {
                    // Используем стандартный виджет DaData
                    window.Dadata.createSuggestions(addressInput, {
                        token: data.key,
                        type: "address",
                        count: 5
                    });
                    
                    // Обработчик выбора подсказки
                    document.addEventListener('click', function(e) {
                        let target = e.target;
                        while (target && target !== document.body) {
                            if (target.classList && target.classList.contains('suggestions-suggestion')) {
                                setTimeout(() => {
                                    processAddress(addressInput.value);
                                }, 100);
                                break;
                            }
                            target = target.parentElement;
                        }
                    });
                }
            })
            .catch(error => {
                console.error('Ошибка загрузки ключа DaData:', error);
            });
    }

    // Обработчик кнопки "Найти"
    if (searchBtn) {
        searchBtn.addEventListener('click', function() {
            processAddress(sanitizeAddress(addressInput.value));
        });
    }

    // Обработчик кнопки переключения типа (с доставки на самовывоз)
    if (typeToPickupBtn) {
        typeToPickupBtn.addEventListener('click', function() {
            if (!typeToPickupBtn.classList.contains('chosen')) {
                clearDeliveryMap();
            }
        });
    }
}

// Карта самовывоза
function initPickupMap(){
    const map = new ymaps.Map('pickup-map', { 
        center: [55.8, 37.64], 
        zoom: 8 
    });
    
    let selectedStoreMarker = null;
    const typeToDeliveryBtn = document.getElementById('order-type-delivery');

    function selectPickupStore(storeId, address, marker, index) {
        fetchWithRetry('src/saveDeliveryAddress.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({delivery_type: 'pickup', store_id: storeId  })
        })
        .then(data => {
            if (data.success) {
                // Сбрасываем предыдущий выбранный маркер
                if (selectedStoreMarker) {
                    selectedStoreMarker.options.set({
                        iconImageHref: '/img/custom_map_pin.png'
                    });
                }
                
                // Устанавливаем новый выбранный маркер
                marker.options.set({
                    iconImageHref: '/img/custom_map_pin_chosen.png'
                });
                selectedStoreMarker = marker;
                
                // ОБНОВЛЯЕМ ТОЛЬКО ВЫБРАННУЮ КНОПКУ
                const selectBtn = document.getElementById(`select-pickup-store-${index}`);
                if (selectBtn) {
                    selectBtn.textContent = '✅ Магазин выбран';
                    selectBtn.style.cursor = 'default';
                    selectBtn.style.pointerEvents = 'none';
                    selectBtn.disabled = true;
                }
                
                // Обновляем адрес в правой части
                document.getElementById('order-right-pickup-address').innerText = address;
                
                // Закрываем балун
                marker.balloon.close();
            }
        });
    }

    function clearPickupMap() {
        if (selectedStoreMarker) {
            selectedStoreMarker.options.set({
                iconImageHref: '/img/custom_map_pin.png'
            });
            selectedStoreMarker = null;
        }

        map.setCenter([55.76, 37.64], 8);
    }
    
    // Загружаем магазины из БД
    fetch('src/getStores.php')
    .then(response => response.json())
    .then(stores => {
        stores.forEach((store, index) => {
            if (store.coordinates && store.coordinates.length === 2) {
                const placemark = new ymaps.Placemark(store.coordinates, {
                    balloonContent: `
                        <div style="min-width: 250px; font-family: 'Jost', Arial; font-size: 16px; color: black;">
                            <div style="margin-bottom: 15px;">
                                <strong>${store.address}</strong><br>
                                <div style="margin-top: 10px;">${store.work_hours}</div>
                                <a href='tel: ${store.phone}' class="colour_href">
                                    <div style="margin-top: 10px;">${store.phone}</div>
                                </a>
                            </div>
                            <div style="text-align: center;">
                                <button id="select-pickup-store-${index}" 
                                        style="border: 0.15vw solid black; padding: 3% 5%; border-radius: 0.5vw; cursor: pointer; 
                                        background: #4B4B4B !important;">
                                    Заберу отсюда
                                </button>
                            </div>
                        </div>
                    `
                }, {
                    iconLayout: 'default#image',
                    iconImageHref: '/img/custom_map_pin.png',
                    iconImageSize: [60, 55],
                    iconImageOffset: [-20, -40]
                });
                
                map.geoObjects.add(placemark);
                
                // Обработчик открытия балуна
                placemark.events.add('balloonopen', function() {
                    const currentAddress = document.getElementById('order-right-pickup-address').innerText;
                    const isSelected = currentAddress === store.address;
                    
                    if (isSelected) {
                        setTimeout(() => {
                            const selectBtn = document.getElementById(`select-pickup-store-${index}`);
                            if (selectBtn) {
                                selectBtn.textContent = '✅ Магазин выбран';
                                selectBtn.style.cursor = 'default';
                                selectBtn.style.pointerEvents = 'none';
                                selectBtn.disabled = true;
                            }
                        }, 0);
                    } else {
                        setTimeout(() => {
                            const selectBtn = document.getElementById(`select-pickup-store-${index}`);
                            if (selectBtn && !selectBtn.hasAttribute('data-listener-added')) {
                                selectBtn.setAttribute('data-listener-added', 'true');
                                selectBtn.addEventListener('click', function() {
                                    selectPickupStore(store.id, store.address, placemark, index);
                                });
                            }
                        }, 100);
                    }
                });
            }
        });
        
    })
    .catch(error => {
        console.error('Ошибка загрузки магазинов:', error);
    });

    // Обработчик кнопки переключения типа (с доставки на самовывоз)
    if (typeToDeliveryBtn) {
        typeToDeliveryBtn.addEventListener('click', function() {
            if (!typeToDeliveryBtn.classList.contains('chosen')) {
                clearPickupMap();
            }
        });
    }
}



if (typeof ymaps === 'undefined') {
    showMapError();
} else {
    ymaps.ready(() => {
        // Карта магазинов
        if (document.getElementById('stores-map')) {

            document.querySelector('.stores_map_loader').style.opacity = '0';
            document.querySelector('.stores_map_loader').style.visibility = 'hidden';

            try {
                initStoresMap();
            } catch (error) {
                console.error('Ошибка карты магазинов:', error);
                showMapError('stores');
            }
        }

        // Карта заказа (проверяем API ключ)
        if (document.getElementById('delivery-map')) {

            document.querySelectorAll('.order_map_loader').forEach(loader => {
            // Плавное исчезновение
            loader.style.opacity = '0';
            loader.style.visibility = 'hidden';
            
            // Через время завершения анимации - полностью убираем
            setTimeout(() => {
                    loader.style.display = 'none';
                }, 200); // Время должно совпадать с transition (0.4s)
            });

            ymaps.geocode('Москва', { results: 1 })
                .then(() => {
                    // API работает, инициализируем карту заказа
                    initDeliveryMap();
                })
                .catch(error => {
                    // API не работает
                    console.error('Неверный API ключ Яндекс.Карт:', error);
                    showMapError('delivery');
                });
        }

        // Карта самовывоза
        if (document.getElementById('pickup-map')) {

            // document.querySelectorAll('.order_map_loader').forEach(loader => {
            //     loader.style.display = 'none';
            // });
            // т. к. на одной статнице скроеться сразу для двух 

            try {
                initPickupMap();
            } catch (error) {
                console.error('Ошибка карты самовывоза:', error);
                showMapError('pickup');
            }
        }
    });
}