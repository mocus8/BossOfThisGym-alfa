//пробные отправки запросов на сервак
async function fetchWithRetry(url, options, retries = 2) {
    for (let attempt = 0; attempt <= retries; attempt++) {
        try {
            const response = await fetch(url, options);
            if (response.ok) return await response.json();
            throw new Error(`HTTP ${response.status}`);
        } catch (error) {
            if (attempt === retries) throw error;
            await new Promise(resolve => setTimeout(resolve, 1000 * (attempt + 1)));
        }
    }
}

//открытие модалки
function openModal(modalId) {
    const modal = document.getElementById(modalId);
    modal.classList.add("open");
}

//закрытие модалки
function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    modal.classList.remove("open");
}

function setupModal(modalId, openBtnId, closeBtnId){
    const modal = document.getElementById(modalId);
    const openBtn = document.getElementById(openBtnId);
    const closeBtn = document.getElementById(closeBtnId);
    if (!openBtn || !modal || !closeBtn) return;
    
    openBtn.addEventListener("click", () => openModal(modalId));
    closeBtn.addEventListener("click", () => closeModal(modalId));
    
    modal.addEventListener("click", function(e) {
        if (e.target === this) {
            closeModal(modalId);
        };
    })
    
    document.addEventListener("keydown", function(e) {
        if (e.key === "Escape" && modal.classList.contains("open")) {
            closeModal(modalId);
        }
    });
}

setupModal("authorization-modal", "open-authorization-modal", "close-authorization-modal");
setupModal("registration-modal", "open-registration-modal-from-cart", "close-registration-modal");
setupModal("registration-modal", "open-registration-modal", "close-registration-modal");
setupModal("account-edit-modal", "open-account-editior-modal", "close-account-edit-modal");
setupModal("account-delete-modal", "open-account-edit-modal", "close-account-delete-modal");
setupModal("account-exit-modal", "open-account-exit-modal", "close-account-exit-modal");

function validatePhoneNumber(phone) {
    let cleaned = phone.replace(/[^\d+]/g, '');

    if (cleaned.startsWith('8') && cleaned.length === 11) {
        cleaned = '+7' + cleaned.substring(1);
    }

    if (cleaned.startsWith('7') && cleaned.length === 11) {
        cleaned = '+7' + cleaned.substring(1);
    }

    const regex = /^\+7\d{10}$/;

    return {
        isValid: regex.test(cleaned),
        formatted: cleaned
    };
}

function FormValidatePhoneNumber(formClass) {
    const form = document.querySelector(formClass);
    if (!form) return;
    
    form.addEventListener('submit', function(e) {
        const phoneNumberInput = this.querySelector('input[name="login"]');
        if (!phoneNumberInput) return;
        
        const phoneValidation = validatePhoneNumber(phoneNumberInput.value);
         if (phoneValidation.isValid && phoneNumberInput.value !== phoneValidation.formatted) {
            phoneNumberInput.value = phoneValidation.formatted;
        }
    });
}

FormValidatePhoneNumber('.authorization_modal_form');
FormValidatePhoneNumber('.registration_modal_form');

document.querySelector('.authorization_modal_form').addEventListener('submit', function(e) {
    //предотвращаем стандартную отправку формы
    e.preventDefault();
    
    const formData = new FormData(this);
    
    fetchWithRetry(this.action, {
        method: 'POST',
        body: formData
    })
    .then(data => {
        if (data.success) {
            window.location.reload();
        } else if (data.message === 'wrong_password') {
            document.getElementById('wrong-password-modal').classList.add('open');
        } else if (data.message === 'user_not_found') {
            document.getElementById('uknown-user-modal').classList.add('open');
        }
    })
    .catch(error => {
        console.error('Ошибка:', error);
    });
});

document.querySelector('.registration_modal_form').addEventListener('submit', function(e) {
    e.preventDefault();

    const phoneNumberInput = this.querySelector('input[name="login"]');
    const password = this.querySelector('input[name="password"]').value;
    const confirmPassword = this.querySelector('input[name="confirm-password"]').value;
    const userAlreadyExistsModal = document.getElementById('user-already-exists-modal');
    const mismatchModal = document.getElementById('password-mismatch-modal');
    const incorrectPhoneModal = document.getElementById('incorrect-phone-number-modal');

    if (password !== confirmPassword) {
        mismatchModal.classList.add('open');
        return; // ← ВАЖНО: выходим из функции
    }

    const phoneValidation = validatePhoneNumber(phoneNumberInput.value);
    if (!phoneValidation.isValid) {
        incorrectPhoneModal.classList.add('open');
        return; // ← ВАЖНО: выходим из функции
    }

    // Этот код выполнится ТОЛЬКО если проверки прошли
    const formData = new FormData(this);
    
    fetchWithRetry(this.action, {
        method: 'POST',
        body: formData
    })
    .then(data => {
    if (data.success) {
        window.location.reload();
    } else if (data.message === 'user_already_exists') {
        userAlreadyExistsModal.classList.add('open');
    }
})
    .catch(error => {
        console.error('Ошибка:', error);
    });
});


//открытие ошибки о несовпадении старого пароля
document.querySelector('.account_edit_modal .registration_modal_form').addEventListener('submit', function(e) {
    //предотвращаем стандартную отправку формы
    e.preventDefault();
    
    const formData = new FormData(this);
    
    fetchWithRetry(this.action, {
        method: 'POST',
        body: formData
    })
    .then(data => {
        if (data.success) {
            window.location.reload();
        } else if (data.message === 'old_password_missmatch') {
            document.getElementById('old-password-missmatch-modal').classList.add('open');
        }
    })
    .catch(error => {
        console.error('Ошибка:', error);
    });
});

function closeErrorModal(ErrorModalId){
    const modal = document.getElementById(ErrorModalId);

    if (modal && modal.classList.contains("open")) {
        modal.classList.remove("open");
    }
}

document.addEventListener('click', function(e) {
    closeErrorModal("incorrect-phone-number-modal");
    closeErrorModal("password-mismatch-modal");
    closeErrorModal("uknown-user-modal");
    closeErrorModal("wrong-password-modal");
    closeErrorModal("user-already-exists-modal");
    closeErrorModal("old-password-missmatch-modal");
    closeErrorModal("modal-error-address-not-found");
    closeErrorModal("modal-error-address-empty");
    closeErrorModal("modal-error-address-timeout");
    closeErrorModal("flash-payment-error");
});

function accountPasswordSwitch(nmbOfInpt) {
    const passwordInput = document.getElementById('password-input-' + nmbOfInpt);
    const passwordButton = document.getElementById('account-edit-modal-password-button-' + nmbOfInpt);
    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        passwordButton.textContent = 'Скрыть';
    } else {
        passwordInput.type = 'password';
        passwordButton.textContent = 'Показать';
    }
}

// Вешаем обработчик на кнопку
document.getElementById('account-edit-modal-password-button-1').addEventListener('click', function() {
    accountPasswordSwitch(1);
});
document.getElementById('account-edit-modal-password-button-2').addEventListener('click', function() {
    accountPasswordSwitch(2);
});

document.addEventListener('click', function(e) {
    // Добавляем проверку на существование parentElement
    if (e.target.parentElement?.classList?.contains('product_minor_images_button')) {
        document.querySelector(".product_main_img").src = e.target.src
    }
});

//есть ли в корзине + меняем кнопки (при загрузке страницы)
document.addEventListener('DOMContentLoaded', function() {
    if (typeof cartAmount !== 'undefined') {
        if (cartAmount != 0) {
            openModal('product-button-add-in-cart');
            closeModal('product-button-add-not-in-cart');
        } else {
            closeModal('product-button-add-in-cart');
            openModal('product-button-add-not-in-cart');
        }
    }
});

//есть ли в корзине + меняем кнопки (при клике на кнопки)
document.addEventListener('click', function(event) {
    if (event.target.id === 'product-button-add-not-in-cart') {
        openModal('product-button-add-in-cart');
        closeModal('product-button-add-not-in-cart');
    } else if (event.target.hasAttribute('data-product-subtract-cart')) {
        // Проверяем значение в счетчике ПЕРЕД кликом
        const counter = document.getElementById('product-cart-counter');
        const currentAmount = counter ? parseInt(counter.textContent) : 0;
        if (currentAmount === 1) {
            closeModal('product-button-add-in-cart');
            openModal('product-button-add-not-in-cart');
        }
    }
});