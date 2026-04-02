const promptCardSelector = '[data-onesignal-prompt-card]';
const promptTriggerSelector = '[data-onesignal-prompt-trigger]';
const promptStatusSelector = '[data-onesignal-status]';
const logoutFormSelector = '[data-onesignal-logout-form]';

const getConfig = () => window.__eduxOneSignalConfig ?? null;

const withOneSignal = async (callback) => {
    const config = getConfig();

    if (!config?.appId) {
        return null;
    }

    window.OneSignalDeferred = window.OneSignalDeferred || [];

    return new Promise((resolve, reject) => {
        let settled = false;
        const timeoutId = window.setTimeout(() => {
            if (settled) {
                return;
            }

            settled = true;
            reject(new Error('OneSignal SDK timeout.'));
        }, 12000);

        window.OneSignalDeferred.push(async (OneSignal) => {
            if (settled) {
                return;
            }

            try {
                const result = await callback(OneSignal, config);

                settled = true;
                window.clearTimeout(timeoutId);
                resolve(result);
            } catch (error) {
                settled = true;
                window.clearTimeout(timeoutId);
                reject(error);
            }
        });
    });
};

const updatePromptCard = (state) => {
    document.querySelectorAll(promptCardSelector).forEach((card) => {
        const trigger = card.querySelector(promptTriggerSelector);
        const status = card.querySelector(promptStatusSelector);

        card.hidden = state.hidden === true;

        if (status && typeof state.message === 'string') {
            status.textContent = state.message;
        }

        if (trigger) {
            trigger.disabled = state.busy === true || state.allowAction === false;
            trigger.classList.toggle('hidden', state.showButton === false);
        }
    });
};

const syncPromptState = () => {
    const config = getConfig();

    if (!config?.appId) {
        updatePromptCard({
            hidden: true,
            showButton: false,
            allowAction: false,
            message: '',
        });

        return;
    }

    if (!window.isSecureContext) {
        updatePromptCard({
            hidden: false,
            showButton: false,
            allowAction: false,
            message: 'Abra a plataforma em HTTPS para ativar as notificações.',
        });

        return;
    }

    if (!('Notification' in window) || !('serviceWorker' in navigator)) {
        updatePromptCard({
            hidden: false,
            showButton: false,
            allowAction: false,
            message: 'Seu navegador não suporta notificações push.',
        });

        return;
    }

    switch (Notification.permission) {
    case 'granted':
        updatePromptCard({
            hidden: true,
            showButton: false,
            allowAction: false,
            message: 'Notificações ativadas.',
        });
        break;
    case 'denied':
        updatePromptCard({
            hidden: false,
            showButton: false,
            allowAction: false,
            message: 'As notificações foram bloqueadas no navegador. Libere nas configurações do site para voltar a receber avisos.',
        });
        break;
    default:
        updatePromptCard({
            hidden: false,
            showButton: true,
            allowAction: true,
            message: 'Ative para receber avisos sobre novas mensagens, aulas e novidades do curso.',
        });
        break;
    }
};

const requestPermission = async () => {
    updatePromptCard({
        hidden: false,
        busy: true,
        showButton: true,
        allowAction: false,
        message: 'Aguardando sua confirmação no navegador...',
    });

    try {
        await withOneSignal(async (OneSignal, config) => {
            if (OneSignal?.Notifications?.requestPermission) {
                await OneSignal.Notifications.requestPermission();
            } else {
                const permission = await Notification.requestPermission();

                if (permission !== 'granted') {
                    return;
                }
            }

            if (config.externalId) {
                await OneSignal.login(config.externalId);
            }
        });
    } catch (_) {
        updatePromptCard({
            hidden: false,
            busy: false,
            showButton: true,
            allowAction: true,
            message: 'Não foi possível ativar agora. Tente novamente em alguns segundos.',
        });

        return;
    }

    syncPromptState();
};

const bindDocumentEvents = () => {
    document.addEventListener('click', (event) => {
        const trigger = event.target instanceof Element
            ? event.target.closest(promptTriggerSelector)
            : null;

        if (!trigger) {
            return;
        }

        event.preventDefault();
        requestPermission();
    });

    document.addEventListener('submit', (event) => {
        const form = event.target instanceof HTMLFormElement
            ? event.target
            : null;

        if (!form || !form.matches(logoutFormSelector) || form.dataset.onesignalLogoutHandled === 'true') {
            return;
        }

        form.dataset.onesignalLogoutHandled = 'true';
        event.preventDefault();

        withOneSignal(async (OneSignal) => {
            if (typeof OneSignal?.logout === 'function') {
                await OneSignal.logout();
            }
        })
            .catch(() => null)
            .finally(() => {
                form.submit();
            });
    });
};

export const initOneSignalWeb = () => {
    bindDocumentEvents();
    syncPromptState();

    window.addEventListener('edux:onesignal-ready', syncPromptState);
    window.addEventListener('edux:onesignal-skipped', syncPromptState);
    document.addEventListener('livewire:navigated', syncPromptState);
};
