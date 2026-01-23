const pushManagerSelector = '[data-push-manager]';

const urlBase64ToUint8Array = (base64String) => {
    const padding = '='.repeat((4 - (base64String.length % 4)) % 4);
    const base64 = (base64String + padding)
        .replace(/-/g, '+')
        .replace(/_/g, '/');
    const rawData = atob(base64);
    const outputArray = new Uint8Array(rawData.length);

    for (let i = 0; i < rawData.length; i += 1) {
        outputArray[i] = rawData.charCodeAt(i);
    }

    return outputArray;
};

const getCsrfToken = () => {
    const token = document.querySelector('meta[name="csrf-token"]');
    return token ? token.getAttribute('content') : null;
};

const isPushSupported = () =>
    'serviceWorker' in navigator &&
    'PushManager' in window &&
    'Notification' in window;

const buildHeaders = () => {
    const headers = {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
    };
    const csrfToken = getCsrfToken();
    if (csrfToken) {
        headers['X-CSRF-TOKEN'] = csrfToken;
    }

    return headers;
};

const getContentEncoding = () => {
    if (Array.isArray(PushManager.supportedContentEncodings) && PushManager.supportedContentEncodings.length) {
        return PushManager.supportedContentEncodings[0];
    }

    return 'aesgcm';
};

const registerServiceWorker = async () => navigator.serviceWorker.register('/sw.js');

const syncSubscription = async (subscribeUrl, subscription) => {
    const payload = subscription.toJSON();
    payload.content_encoding = getContentEncoding();

    const response = await fetch(subscribeUrl, {
        method: 'POST',
        headers: buildHeaders(),
        body: JSON.stringify(payload),
    });

    if (!response.ok) {
        throw new Error('Unable to sync subscription.');
    }
};

const removeSubscription = async (unsubscribeUrl, endpoint) => {
    const response = await fetch(unsubscribeUrl, {
        method: 'DELETE',
        headers: buildHeaders(),
        body: JSON.stringify({ endpoint }),
    });

    if (!response.ok) {
        throw new Error('Unable to remove subscription.');
    }
};

const updateUiState = (container, state) => {
    const subscribeButton = container.querySelector('[data-push-subscribe]');
    const unsubscribeButton = container.querySelector('[data-push-unsubscribe]');
    const status = container.querySelector('[data-push-status]');

    if (status) {
        status.textContent = state.message || '';
    }

    const hideSubscribe = state.mode === 'subscribed' || state.mode === 'unsupported' || state.mode === 'denied';

    if (subscribeButton) {
        subscribeButton.classList.toggle('hidden', hideSubscribe);
    }

    if (unsubscribeButton) {
        unsubscribeButton.classList.toggle('hidden', state.mode !== 'subscribed');
    }
};

const setBusy = (container, busy) => {
    const subscribeButton = container.querySelector('[data-push-subscribe]');
    const unsubscribeButton = container.querySelector('[data-push-unsubscribe]');

    if (subscribeButton) {
        subscribeButton.disabled = busy;
    }

    if (unsubscribeButton) {
        unsubscribeButton.disabled = busy;
    }
};

const initContainer = (container) => {
    if (container.dataset.pushReady === 'true') {
        return;
    }

    container.dataset.pushReady = 'true';

    const vapidKey = container.dataset.vapidKey;
    const subscribeUrl = container.dataset.subscribeUrl;
    const unsubscribeUrl = container.dataset.unsubscribeUrl;

    if (!vapidKey || !subscribeUrl || !unsubscribeUrl) {
        updateUiState(container, {
            mode: 'unsupported',
            message: 'Push nao configurado.',
        });
        return;
    }

    if (!isPushSupported()) {
        updateUiState(container, {
            mode: 'unsupported',
            message: 'Push nao suportado neste navegador.',
        });
        return;
    }

    const handleSubscribe = async () => {
        setBusy(container, true);

        try {
            const permission = await Notification.requestPermission();
            if (permission !== 'granted') {
                updateUiState(container, {
                    mode: 'denied',
                    message: 'Notificacoes bloqueadas no navegador.',
                });
                return;
            }

            const registration = await registerServiceWorker();
            const subscription = await registration.pushManager.subscribe({
                userVisibleOnly: true,
                applicationServerKey: urlBase64ToUint8Array(vapidKey),
            });

            await syncSubscription(subscribeUrl, subscription);

            updateUiState(container, {
                mode: 'subscribed',
                message: 'Notificacoes ativadas.',
            });
        } catch (error) {
            updateUiState(container, {
                mode: 'default',
                message: 'Nao foi possivel ativar agora.',
            });
        } finally {
            setBusy(container, false);
        }
    };

    const handleUnsubscribe = async () => {
        setBusy(container, true);

        try {
            const registration = await registerServiceWorker();
            const subscription = await registration.pushManager.getSubscription();

            if (subscription) {
                const endpoint = subscription.endpoint;
                await subscription.unsubscribe();
                await removeSubscription(unsubscribeUrl, endpoint);
            }

            updateUiState(container, {
                mode: 'default',
                message: 'Notificacoes desativadas.',
            });
        } catch (error) {
            updateUiState(container, {
                mode: 'subscribed',
                message: 'Nao foi possivel desativar agora.',
            });
        } finally {
            setBusy(container, false);
        }
    };

    const subscribeButton = container.querySelector('[data-push-subscribe]');
    const unsubscribeButton = container.querySelector('[data-push-unsubscribe]');

    if (subscribeButton) {
        subscribeButton.addEventListener('click', handleSubscribe);
    }

    if (unsubscribeButton) {
        unsubscribeButton.addEventListener('click', handleUnsubscribe);
    }

    (async () => {
        try {
            if (Notification.permission === 'denied') {
                updateUiState(container, {
                    mode: 'denied',
                    message: 'Notificacoes bloqueadas no navegador.',
                });
                return;
            }

            const registration = await registerServiceWorker();
            let subscription = await registration.pushManager.getSubscription();

            if (!subscription && Notification.permission === 'granted') {
                try {
                    subscription = await registration.pushManager.subscribe({
                        userVisibleOnly: true,
                        applicationServerKey: urlBase64ToUint8Array(vapidKey),
                    });
                } catch (error) {
                    updateUiState(container, {
                        mode: 'default',
                        message: 'Clique para ativar as notificacoes.',
                    });
                    return;
                }
            }

            if (subscription) {
                await syncSubscription(subscribeUrl, subscription);
                updateUiState(container, {
                    mode: 'subscribed',
                    message: 'Notificacoes ativadas.',
                });
                return;
            }

            updateUiState(container, {
                mode: 'default',
                message: 'Clique para ativar as notificacoes.',
            });
        } catch (error) {
            updateUiState(container, {
                mode: 'default',
                message: 'Clique para ativar as notificacoes.',
            });
        }
    })();
};

export const initPushManager = () => {
    document.querySelectorAll(pushManagerSelector).forEach(initContainer);
};
