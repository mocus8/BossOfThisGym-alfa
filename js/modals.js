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

    const regex = /^\+79\d{9}$/;

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

//получение токена капчи
async function getRecaptchaToken(form) {
    const siteKey = form.dataset.recaptchaSiteKey;
    await grecaptcha.ready;
    return await grecaptcha.execute(siteKey, {action: 'submit'});
}

// крутой объект для универсальной модалки в хедере с текстом
const HeaderModal = (function() {
    let closeTimer = null;
    let modal, text, progress, closeBtn;

    function init() {
        modal = document.getElementById('header-modal');
        text = document.getElementById('header-modal-text');
        progress = document.getElementById('header-modal-progress-fill');
        closeBtn = document.getElementById('header-modal-close');

        if (!modal || !text || !progress || !closeBtn) {
            console.error('Modal elements not found');
            return;
        }

        closeBtn.addEventListener('click', close);
    }

    function open(innerText) {
        if (!modal) return;

        close();

        text.textContent = innerText;
        modal.classList.remove("hidden");

        progress.classList.remove("shrinking");
        // Принудительный reflow, гарантируем что анимация перезапуститься
        void progress.offsetWidth;
        progress.classList.add("shrinking");

       closeTimer = setTimeout(close, 5000);
    }

    function close() {
        if (!modal) return;

        modal.classList.add("hidden");
        progress.classList.remove("shrinking");

        if(text) text.textContent = '';

        if (closeTimer) clearTimeout(closeTimer);

        closeTimer = null;
    }

    // Автоинициализация при загрузке
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
    
    // возвращаем функции для открытия и закрытия
    return { open, close };
})(); // () на конце выполняет сразу (для всех функций), и это исользуется все последующее разы

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

let resendTimer = null;
let resendTimeLeft = 0;

//запуск таймера повторной отправки
function startResendTimer(seconds = 60) {
    const smsFirstCodeButton = document.getElementById('first-sms-code');
    const smsRetryCodeButton = document.getElementById('retry-sms-code');

    resendTimeLeft = seconds;
    
    // Блокируем кнопки сразу
    smsFirstCodeButton.disabled = true;
    smsRetryCodeButton.disabled = true;
    
    resendTimer = setInterval(() => {
        resendTimeLeft--;
        
        const timerSpans = document.querySelectorAll(`[data-action="retry-sms-code-timer"]`);
        
        if (resendTimeLeft <= 0) {
            clearInterval(resendTimer);
            smsFirstCodeButton.disabled = false;
            smsRetryCodeButton.disabled = false;
            timerSpans.forEach(span => {
                span.textContent = ``;
            });
        } else {
            timerSpans.forEach(span => {
                span.textContent = `(${resendTimeLeft}с)`;
            });
        }
    }, 1000);
}

//остановка таймера повторной отправки
function clearResendTimer() {
    const timerSpans = document.querySelectorAll(`[data-action="retry-sms-code-timer"]`);
    if (resendTimer) {
        clearInterval(resendTimer);
        resendTimer = null;
        resendTimeLeft = 0;

        timerSpans.forEach(span => {
            span.textContent = ``;
        });
    }
}

//запуск таймера блокировки 
function startUnlockTimer(blockedUntilTimestamp) {
    clearResendTimer();
    
    const interval = setInterval(() => {
        const now = Math.floor(Date.now() / 1000);
        const timeLeft = blockedUntilTimestamp - now;
        
        if (timeLeft <= 0) {
            clearInterval(interval);
        }
    }, 1000);
}

// проверка на блок по попыткам
async function isAttemptsBlocked() {
    const incorrectSmsCodeModal = document.getElementById('incorrect-sms-code-modal');

    try {
        const response = await fetch('/src/isAttemptsBlocked.php');
        const result = await response.json();

        if (result.success) {
            return false;
        }

        if (result.error === 'blocked') {
            clearResendTimer();
            startUnlockTimer(result.blocked_until);

            incorrectSmsCodeModal.querySelector('.error_modal_text').textContent = `Система заблокирована до ${new Date(result.blocked_until * 1000).toLocaleTimeString()}`;
            incorrectSmsCodeModal.classList.add('open');

            document.querySelector('input[name="sms_code"]').value = '';

            return true;
        }

        return false;
    } catch (error) {
        incorrectSmsCodeModal.querySelector('.error_modal_text').textContent = `Ошибка проверки блокировки`;
        return false;
    }
}

// проверка наличия пользователя
async function isUserAlreadyExist() {
    const userAlreadyExistsModal = document.getElementById('user-already-exists-modal');
    
    const phoneNumberInput = document.querySelector('.registration_modal_form').querySelector('input[name="login"]');
    const phoneValidation = validatePhoneNumber(phoneNumberInput.value);
    
    try {
        const response = await fetch('/src/isUserAlreadyExist.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                login: phoneValidation.formatted
            })
        });

        const result = await response.json();

        if (result.success) {
            userAlreadyExistsModal.classList.add('open');
            return true;
        } else {
            return false;
        }
    } catch (error) {
        incorrectSmsCodeModal.querySelector('.error_modal_text').textContent = `Ошибка проверки блокировки`;
        return false;
    }
}

// переключение состояния формы
function toggleSmsCodeState() {
    const smsFirstCodeButton = document.getElementById('first-sms-code');
    const smsRetryCodeButton = document.getElementById('retry-sms-code');
    const phoneChangeButton = document.getElementById('phone-change');
    const SmsCodeSection = document.querySelector('.registration_modal_form').querySelector('input[name="sms_code"]').closest('.registration_modal_input_back');
    const phoneNumberSection = document.querySelector('.registration_modal_form').querySelector('input[name="login"]').closest('.registration_modal_input_back');
    
    document.querySelector('input[name="sms_code"]').value = '';

    smsFirstCodeButton.classList.toggle('hidden');
    smsRetryCodeButton.classList.toggle('hidden');
    phoneChangeButton.classList.toggle('hidden');
    SmsCodeSection.classList.toggle('hidden');
    phoneNumberSection.classList.toggle('hidden');
}

// отправка кода
async function sendSmsCode() {
    if (await isAttemptsBlocked()) {
        return;
    }

    if (await isUserAlreadyExist()) {
        return;
    }

    const phoneNumberInput = document.querySelector('.registration_modal_form').querySelector('input[name="login"]');
    const phoneValidation = validatePhoneNumber(phoneNumberInput.value);

    const incorrectPhoneModal = document.getElementById('incorrect-phone-number-modal');
    const incorrectSmsCodeModal = document.getElementById('incorrect-sms-code-modal');

    const smsFirstCodeButton = document.getElementById('first-sms-code');
    const smsRetryCodeButton = document.getElementById('retry-sms-code');

    const smsFirstCodeButtonText = smsFirstCodeButton.querySelector('.first_sms_code_btn_text');
    const smsRetryCodeButtonText = smsRetryCodeButton.querySelector('.retry_sms_code_btn_text');

    const smsFirstCodeButtonTextOrgnl = smsFirstCodeButtonText.textContent;
    const smsRetryCodeButtonTextOrgnl = smsRetryCodeButtonText.textContent;

    if (!smsFirstCodeButton.classList.contains('hidden')) {
        smsFirstCodeButton.disabled = true;
        smsFirstCodeButtonText.textContent = 'Обработка...';
    } else {
        smsRetryCodeButton.disabled = true;
        smsRetryCodeButtonText.textContent = 'Обработка...';
    }

    try {

        if (!phoneValidation.isValid) {
            incorrectPhoneModal.classList.add('open');
            smsFirstCodeButtonText.textContent = smsFirstCodeButtonTextOrgnl;
            smsRetryCodeButtonText.textContent = smsRetryCodeButtonTextOrgnl;
            smsFirstCodeButton.disabled = false;
            smsRetryCodeButton.disabled = false;
            return;
        }

        // передаем телефон В POST для отправки смс
        const response = await fetch('/src/smscSend.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                phone: phoneValidation.formatted
            })
        });
    
        const result = await response.json();

        if (!response.ok || !result.success) {
            throw new Error(result.error || result.message || `Ошибка ${response.status}! Попробуйте еще раз`);
        }

        if (!smsFirstCodeButton.classList.contains('hidden')) {
            toggleSmsCodeState();
        }

        document.querySelector('input[name="sms_code"]').value = '';

        //это для теста без реальных sms, потом убрать!!!
        alert(`смски дорогие, пока так (но функционал для реальных смс уже есть) Код подтверждения: ${result.debug_code}, был бы отправлен на номер ${result.debug_phone}`);

        HeaderModal.open('SMS-код был отправлен на указанный номер');

        startResendTimer(30);
    } catch (error) {
        if (smsFirstCodeButton.classList.contains('hidden')) {
            toggleSmsCodeState();
        }

        incorrectSmsCodeModal.querySelector('.error_modal_text').textContent = error.message;
        incorrectSmsCodeModal.classList.add('open');
    } finally {
        smsFirstCodeButtonText.textContent = smsFirstCodeButtonTextOrgnl;
        smsRetryCodeButtonText.textContent = smsRetryCodeButtonTextOrgnl;
    }
}

// подтверждение кода
async function confirmSmsCode() {
    if (await isAttemptsBlocked()) {
        return;
    }

    const smsCodeInput = document.querySelector('.registration_modal_form').querySelector('input[name="sms_code"]');
    const incorrectSmsCodeModal = document.getElementById('incorrect-sms-code-modal');

    const smsFirstCodeButton = document.getElementById('first-sms-code');

    const smsRetryCodeButton = document.getElementById('retry-sms-code');
    const smsRetryCodeButtonText = smsRetryCodeButton.querySelector('.retry_sms_code_btn_text');
    const smsRetryCodeButtonTextOrgnl = smsRetryCodeButtonText.textContent;

    const phoneNumberInput = document.querySelector('.registration_modal_form').querySelector('input[name="login"]');

    smsFirstCodeButton.disabled = true;
    smsRetryCodeButton.disabled = true;
    smsRetryCodeButtonText.textContent = 'Обработка...';

    try {
        const response = await fetch('/src/smscVerify.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                code: smsCodeInput.value
            })
        });
    
        const result = await response.json();

        if (result.error === 'blocked') {
            startUnlockTimer(result.blocked_until);
            smsFirstCodeButton.disabled = false;
            smsRetryCodeButton.disabled = false;
            throw new Error(`Система заблокирована до ${new Date(result.blocked_until * 1000).toLocaleTimeString()}`);
        }

        if (!response.ok || !result.success) {
            throw new Error(result.error || result.message || `Ошибка ${response.status}! Попробуйте еще раз`);
        }

        clearResendTimer();

        phoneNumberInput.value = validatePhoneNumber(phoneNumberInput.value).formatted;
        phoneNumberInput.readOnly = true;

        toggleSmsCodeState();
        
        document.querySelector('input[name="sms_code"]').value = '';

        smsFirstCodeButton.textContent = 'Успешно';
    } catch (error) {
        incorrectSmsCodeModal.querySelector('.error_modal_text').textContent = error.message;
        incorrectSmsCodeModal.classList.add('open');
    } finally {
        smsRetryCodeButtonText.textContent = smsRetryCodeButtonTextOrgnl;
    }
}

// первичная отправка кода
document.getElementById('first-sms-code').addEventListener('click', sendSmsCode);

// отправить код снова
document.getElementById('retry-sms-code').addEventListener('click', sendSmsCode);

// изменить телефон
document.getElementById('phone-change').addEventListener('click', async function(e) {
    toggleSmsCodeState();
});

// Обработчик ввода (по 5 символам)
document.querySelector('input[name="sms_code"]').addEventListener('input', function(e) {
    this.value = this.value.replace(/\D/g, '');
    if (this.value.length === 5) {
        confirmSmsCode();
    }
});

// Обработчик нажатия enter
document.querySelector('input[name="sms_code"]').addEventListener('keydown', function(e) {
    if (e.key === 'Enter') {
        e.preventDefault();
        confirmSmsCode();
    }
});

// потдтерждение формы регистрации
document.querySelector('.registration_modal_form').addEventListener('submit', async function(e) {
    e.preventDefault();

    if (await isAttemptsBlocked()) {
        return;
    }

    const token = await getRecaptchaToken(this);

    const phoneNumberInput = this.querySelector('input[name="login"]');
    const phoneValidation = validatePhoneNumber(phoneNumberInput.value);

    const password = this.querySelector('input[name="password"]').value;
    const confirmPassword = this.querySelector('input[name="confirm-password"]').value;
    
    const submitRegistrtionButton = document.getElementById('submit-registration');

    const userAlreadyExistsModal = document.getElementById('user-already-exists-modal');
    const mismatchModal = document.getElementById('password-mismatch-modal');
    const incorrectPhoneModal = document.getElementById('incorrect-phone-number-modal');
    const incorrectSmsCodeModal = document.getElementById('incorrect-sms-code-modal');

    try {
        submitRegistrtionButton.textContent = 'Регистрация...';
        submitRegistrtionButton.disabled = true;

        if (password !== confirmPassword) {
            throw new Error('password_mismatch');
        }

        if (!phoneValidation.isValid) {
            throw new Error('incorrect_phone');
        }

        const formData = new FormData(this);
        formData.append('recaptcha_response', token);

        const result = await fetchWithRetry(this.action, {
            method: 'POST',
            body: formData
        })

        if (!result.success) {
            throw new Error(result.message);
        }
        
        window.location.reload();
    } catch (error) {
        const errorMessages = {
            'user_already_exists': 'Пользователь уже зарегистрирован',
            'phone_not_verified': 'Телефон не подтвержден',
            'phone_changed': 'Телефон был изменен после подтверждения по sms',
            'code_expired': 'Код подтверждения устарел',
            'recaptcha_false': 'Не удалось пройти проверку на ботов',
            'error': 'Ошибка'
        };

        if (error.message === 'user_already_exists') {
            userAlreadyExistsModal.classList.add('open');
        } else if (error.message === 'password_mismatch') {
            mismatchModal.classList.add('open');
        } else if (error.message === 'incorrect_phone') {
            incorrectPhoneModal.classList.add('open');
        } else if (errorMessages[error.message]) {
            incorrectSmsCodeModal.querySelector('.error_modal_text').textContent = errorMessages[error.message];
            incorrectSmsCodeModal.classList.add('open');
        } else if (error.message) {
            incorrectSmsCodeModal.querySelector('.error_modal_text').textContent = error.message;
            incorrectSmsCodeModal.classList.add('open');
        } else {
            incorrectSmsCodeModal.querySelector('.error_modal_text').textContent = 'Произошла ошибка';
            incorrectSmsCodeModal.classList.add('open');
        }
    } finally {
        submitRegistrtionButton.textContent = 'Зарегистрироваться';
        submitRegistrtionButton.disabled = false;
    }
});

// document.querySelector('.registration_modal_form').addEventListener('submit', async function(e) {
//     e.preventDefault();

//     if (await isAttemptsBlocked()) {
//         return;
//     }

//     const token = await getRecaptchaToken(this);

//     const phoneNumberInput = this.querySelector('input[name="login"]');
//     const phoneValidation = validatePhoneNumber(phoneNumberInput.value);

//     const password = this.querySelector('input[name="password"]').value;
//     const confirmPassword = this.querySelector('input[name="confirm-password"]').value;
    
//     const submitRegistrtionButton = document.getElementById('submit-registration');

//     const userAlreadyExistsModal = document.getElementById('user-already-exists-modal');
//     const mismatchModal = document.getElementById('password-mismatch-modal');
//     const incorrectPhoneModal = document.getElementById('incorrect-phone-number-modal');
//     const incorrectSmsCodeModal = document.getElementById('incorrect-sms-code-modal');

//     if (password !== confirmPassword) {
//         mismatchModal.classList.add('open');
//         return; // ← ВАЖНО: выходим из функции
//     }

//     if (!phoneValidation.isValid) {
//         incorrectPhoneModal.classList.add('open');
//         return; // ← ВАЖНО: выходим из функции
//     }

//     // Этот код выполнится ТОЛЬКО если проверки прошли
//     const formData = new FormData(this);
//     formData.append('recaptcha_response', token);

//     const result = await fetchWithRetry(this.action, {
//         method: 'POST',
//         body: formData
//     })

//     if (!result.success) {
//         const errorMessages = {
//             'user_already_exists': 'Пользователь уже зарегистрирован',
//             'phone_not_verified': 'Телефон не подтвержден',
//             'phone_changed': 'Телефон был изменен после подтверждения по sms',
//             'code_expired': 'Код подтверждения устарел',
//             'recaptcha_false': 'Не удалось пройти проверку на ботов.'
//         };
        
//         if (result.message === 'user_already_exists') {
//             userAlreadyExistsModal.classList.add('open');
//         } else if (errorMessages[result.message]) {
//             incorrectSmsCodeModal.querySelector('.error_modal_text').textContent = errorMessages[result.message];
//             incorrectSmsCodeModal.classList.add('open');
//         } else if (result.message) {
//             incorrectSmsCodeModal.querySelector('.error_modal_text').textContent = result.message;
//             incorrectSmsCodeModal.classList.add('open');
//         }
//     } else {
//         window.location.reload();
//     }
// });

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
    // Закрываем модалки только если клик НЕ на кнопки (мб добавить еще элементы если все равно сразу закрывается)
    if (!e.target.closest('button')) {
        closeErrorModal("incorrect-phone-number-modal");
        closeErrorModal("incorrect-sms-code-modal");
        closeErrorModal("password-mismatch-modal");
        closeErrorModal("uknown-user-modal");
        closeErrorModal("wrong-password-modal");
        closeErrorModal("user-already-exists-modal");
        closeErrorModal("old-password-missmatch-modal");
        closeErrorModal("modal-error-address-not-found");
        closeErrorModal("modal-error-address-empty");
        closeErrorModal("modal-error-address-timeout");
        closeErrorModal("flash-payment-error");
    }
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