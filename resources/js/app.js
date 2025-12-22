import './bootstrap';
import collapse from '@alpinejs/collapse';
import intlTelInput from 'intl-tel-input';
import utilsScriptUrl from 'intl-tel-input/build/js/utils.js?url';
import 'intl-tel-input/build/css/intlTelInput.css';

document.addEventListener('alpine:init', () => {
    window.Alpine.plugin(collapse);
});

const intlPhoneInstances = new Map();

const destroyOrphanIntlInputs = () => {
    intlPhoneInstances.forEach((iti, input) => {
        if (! document.body.contains(input)) {
            iti.destroy();
            intlPhoneInstances.delete(input);
        }
    });
};

const setupIntlPhoneInputs = () => {
    destroyOrphanIntlInputs();

    document.querySelectorAll('.js-intl-phone').forEach((input) => {
        if (intlPhoneInstances.has(input)) {
            return;
        }

        const targetId = input.dataset.target;
        const hiddenInput = targetId ? document.getElementById(targetId) : null;

        const iti = intlTelInput(input, {
            initialCountry: 'br',
            separateDialCode: true,
            preferredCountries: ['br'],
            utilsScript: utilsScriptUrl,
        });

        const sync = () => {
            if (! hiddenInput) {
                return;
            }

            hiddenInput.value = iti.getNumber();
            hiddenInput.dispatchEvent(new Event('input'));
        };

        input.addEventListener('countrychange', sync);
        input.addEventListener('blur', sync);
        input.addEventListener('keyup', () => {
            if (input.value.trim() === '' && hiddenInput) {
                hiddenInput.value = '';
                hiddenInput.dispatchEvent(new Event('input'));
            }
        });

        if (hiddenInput && hiddenInput.value) {
            iti.setNumber(hiddenInput.value);
        }

        intlPhoneInstances.set(input, iti);
    });
};

document.addEventListener('livewire:load', () => {
    setupIntlPhoneInputs();

    if (window.Livewire) {
        window.Livewire.hook('message.processed', () => {
            setupIntlPhoneInputs();
        });
    }
});
