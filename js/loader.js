// 1. Быстро показываем основной контент
document.addEventListener('DOMContentLoaded', function() {
    const loader = document.getElementById('loader');
    const desktop = document.querySelector('.desktop');
    
    if (loader) {
        // Сразу показываем страницу
        desktop.classList.add('desktop_visible');
        
        // Прячем лоадер, но не удаляем (на случай долгой загрузки)
        loader.style.opacity = '0';
        loader.style.pointerEvents = 'none';
    }
});

// 2. Полная очистка после загрузки всего
window.addEventListener('load', function() {
    const loader = document.getElementById('loader');
    
    // Полностью убираем лоадер
    setTimeout(() => {
        if (loader && loader.parentNode) {
            loader.remove();
        }
    }, 300);
});

// 3. Защита от "зависшего" лоадера
setTimeout(function() {
    const loader = document.getElementById('loader');
    if (loader && loader.style.opacity === '0' && loader.parentNode) {
        loader.remove();
    }
}, 5000); // На всякий случай убираем через 5 сек