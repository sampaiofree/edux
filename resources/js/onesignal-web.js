const promptRootSelector = '[data-onesignal-prompt-root]';
const promptCardSelector = '[data-onesignal-prompt-card]';
const promptTriggerSelector = '[data-onesignal-prompt-trigger]';
const promptStatusSelector = '[data-onesignal-status]';
const logoutFormSelector = '[data-onesignal-logout-form]';

const reportedDiagnostics = new Map();
const syncedContactSignatures = new Set();
let bindingsReady = false;

const getConfig = () => window.__eduxOneSignalConfig ?? null;

const readCsrfToken = () => document.querySelector('meta[name="csrf-token"]')?.content ?? '';

const logConsole = (eventName, payload = {}, level = 'info') => {
    const logger = typeof console[level] === 'function' ? console[level] : console.info;

    logger(`[onesignal.web] ${eventName}`, payload);
};

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

const rootUrl = () => window.location.href;

const normalizeScopeUrl = (scope) => {
    try {
        return new URL(String(scope ?? ''), window.location.origin).href;
    } catch (_) {
        return String(scope ?? '');
    }
};

const toShortHash = async (value) => {
    const normalized = String(value ?? '').trim();

    if (normalized === '' || ! window.crypto?.subtle || typeof TextEncoder === 'undefined') {
        return null;
    }

    try {
        const bytes = new TextEncoder().encode(normalized);
        const digest = await window.crypto.subtle.digest('SHA-256', bytes);

        return Array.from(new Uint8Array(digest))
            .map((byte) => byte.toString(16).padStart(2, '0'))
            .join('')
            .slice(0, 16);
    } catch (_) {
        return null;
    }
};

const updatePromptCard = (state) => {
    document.querySelectorAll(promptRootSelector).forEach((root) => {
        root.hidden = state.hidden === true;
    });

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

const browserSupportState = () => {
    const config = getConfig();

    if (!config?.appId) {
        return {
            name: 'nao_configurado',
            hidden: true,
            showButton: false,
            allowAction: false,
            message: '',
        };
    }

    if (!window.isSecureContext) {
        return {
            name: 'inseguro',
            hidden: false,
            showButton: false,
            allowAction: false,
            message: 'Abra a plataforma em HTTPS para ativar as notificações.',
        };
    }

    if (!('Notification' in window) || !('serviceWorker' in navigator)) {
        return {
            name: 'nao_suportado',
            hidden: false,
            showButton: false,
            allowAction: false,
            message: 'Seu navegador não suporta notificações push.',
        };
    }

    return null;
};

const buildSnapshot = async (OneSignal, config) => {
    const browserPermission = 'Notification' in window ? Notification.permission : 'default';
    const pushPermission = OneSignal?.Notifications?.permission === true || browserPermission === 'granted';
    const subscription = OneSignal?.User?.PushSubscription ?? {};
    const subscriptionId = subscription.id ?? null;
    const subscriptionToken = subscription.token ?? null;
    const externalId = OneSignal?.User?.externalId ?? null;
    const onesignalId = OneSignal?.User?.onesignalId ?? null;

    return {
        permission: browserPermission,
        sdkReady: true,
        optedIn: Boolean(subscription.optedIn),
        subscriptionIdPresent: Boolean(subscriptionId),
        subscriptionIdHash: await toShortHash(subscriptionId),
        tokenPresent: Boolean(subscriptionToken),
        onesignalIdPresent: Boolean(onesignalId),
        externalIdMatches: config.externalId ? externalId === config.externalId : false,
        emailPresent: Boolean(config.email),
        smsPhonePresent: Boolean(config.smsPhone),
        smsPhoneHash: await toShortHash(config.smsPhone),
        pushPermission,
        serviceWorkerScope: normalizeScopeUrl(config.serviceWorkerScope),
        url: rootUrl(),
        userAgent: navigator.userAgent,
    };
};

const contactSyncSignature = (config) => JSON.stringify({
    externalId: config?.externalId ?? null,
    email: config?.email ?? null,
    smsPhone: config?.smsPhone ?? null,
});

const syncKnownContacts = async (OneSignal, config) => {
    const signature = contactSyncSignature(config);
    const shouldSyncEmail = typeof config?.email === 'string' && config.email.trim() !== '';
    const shouldSyncSms = typeof config?.smsPhone === 'string' && config.smsPhone.trim() !== '';

    if (!config?.externalId || syncedContactSignatures.has(signature) || (!shouldSyncEmail && !shouldSyncSms)) {
        return;
    }

    try {
        if (OneSignal?.User?.externalId !== config.externalId && typeof OneSignal?.login === 'function') {
            await OneSignal.login(config.externalId);
        }

        if (shouldSyncEmail && typeof OneSignal?.User?.addEmail === 'function') {
            await OneSignal.User.addEmail(config.email);
        }

        if (shouldSyncSms && typeof OneSignal?.User?.addSms === 'function') {
            await OneSignal.User.addSms(config.smsPhone);
        }

        syncedContactSignatures.add(signature);
        await reportDiagnostic('onesignal.web_contacts_synced', await buildSnapshot(OneSignal, config));
    } catch (error) {
        logConsole('onesignal.web_contact_sync_failed', {
            message: error?.message ?? String(error),
            emailPresent: shouldSyncEmail,
            smsPhonePresent: shouldSyncSms,
        }, 'warn');

        await reportDiagnostic('onesignal.web_contact_sync_failed', await buildSnapshot(OneSignal, config));
    }
};

const determinePromptState = (snapshot) => {
    if (snapshot.permission === 'denied') {
        return {
            name: 'bloqueado',
            hidden: false,
            showButton: false,
            allowAction: false,
            message: 'As notificações foram bloqueadas no Chrome. Libere nas configurações do site para voltar a receber avisos.',
        };
    }

    const active = snapshot.permission === 'granted'
        && snapshot.optedIn
        && snapshot.subscriptionIdPresent
        && snapshot.tokenPresent
        && snapshot.externalIdMatches;

    if (active) {
        return {
            name: 'ativo',
            hidden: true,
            showButton: false,
            allowAction: false,
            message: 'Notificações ativadas.',
        };
    }

    if (snapshot.permission === 'granted') {
        return {
            name: 'permissao_ok_sem_inscricao',
            hidden: false,
            showButton: true,
            allowAction: true,
            message: 'Seu navegador já permitiu notificações, mas este dispositivo ainda não terminou a ativação. Toque para concluir.',
        };
    }

    return {
        name: 'pendente',
        hidden: false,
        showButton: true,
        allowAction: true,
        message: 'Ative para receber avisos sobre aulas, recados e atualizações do seu curso.',
    };
};

const reportDiagnostic = async (eventName, payload = {}) => {
    const config = getConfig();

    if (!config?.diagnosticsUrl) {
        logConsole(eventName, payload, eventName.includes('mismatch') ? 'warn' : 'info');

        return;
    }

    const safePayload = {
        event: eventName,
        permission: payload.permission ?? null,
        opted_in: typeof payload.optedIn === 'boolean' ? payload.optedIn : null,
        external_id_matches: typeof payload.externalIdMatches === 'boolean' ? payload.externalIdMatches : null,
        email_present: typeof payload.emailPresent === 'boolean' ? payload.emailPresent : null,
        subscription_id_present: typeof payload.subscriptionIdPresent === 'boolean' ? payload.subscriptionIdPresent : null,
        subscription_id_hash: payload.subscriptionIdHash ?? null,
        sms_phone_present: typeof payload.smsPhonePresent === 'boolean' ? payload.smsPhonePresent : null,
        sms_phone_hash: payload.smsPhoneHash ?? null,
        token_present: typeof payload.tokenPresent === 'boolean' ? payload.tokenPresent : null,
        onesignal_id_present: typeof payload.onesignalIdPresent === 'boolean' ? payload.onesignalIdPresent : null,
        sdk_ready: typeof payload.sdkReady === 'boolean' ? payload.sdkReady : null,
        service_worker_scope: payload.serviceWorkerScope ?? normalizeScopeUrl(config.serviceWorkerScope),
        url: payload.url ?? rootUrl(),
        user_agent: payload.userAgent ?? navigator.userAgent,
    };
    const signature = JSON.stringify(safePayload);

    if (reportedDiagnostics.get(eventName) === signature) {
        return;
    }

    reportedDiagnostics.set(eventName, signature);
    logConsole(eventName, safePayload, eventName.includes('mismatch') ? 'warn' : 'info');

    try {
        await window.fetch(config.diagnosticsUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': readCsrfToken(),
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
            cache: 'no-store',
            keepalive: true,
            body: JSON.stringify(safePayload),
        });
    } catch (error) {
        logConsole('onesignal.web_diagnostic_failed', { message: error?.message ?? String(error) }, 'warn');
    }
};

const verifyServiceWorkerScope = async (snapshot) => {
    const config = getConfig();

    if (!window.isSecureContext || !('serviceWorker' in navigator) || !config?.serviceWorkerScope) {
        return;
    }

    try {
        const registrations = await navigator.serviceWorker.getRegistrations();
        const expectedScope = normalizeScopeUrl(config.serviceWorkerScope);
        const matches = registrations.some((registration) => registration?.scope === expectedScope);

        if (!matches) {
            await reportDiagnostic('onesignal.web_service_worker_mismatch', {
                ...snapshot,
                serviceWorkerScope: expectedScope,
            });
        }
    } catch (error) {
        logConsole('onesignal.web_service_worker_check_failed', { message: error?.message ?? String(error) }, 'warn');
    }
};

const refreshPromptState = async () => {
    const supportState = browserSupportState();

    if (supportState) {
        updatePromptCard(supportState);

        return;
    }

    let snapshot = null;

    try {
        snapshot = await withOneSignal(async (OneSignal, config) => buildSnapshot(OneSignal, config));
    } catch (error) {
        logConsole('onesignal.web_state_refresh_failed', { message: error?.message ?? String(error) }, 'warn');
        updatePromptCard({
            hidden: false,
            showButton: true,
            allowAction: true,
            message: 'Nao foi possivel preparar as notificacoes agora. Tente novamente em alguns segundos.',
        });

        return;
    }

    if (!snapshot) {
        updatePromptCard({
            hidden: true,
            showButton: false,
            allowAction: false,
            message: '',
        });

        return;
    }

    const state = determinePromptState(snapshot);
    updatePromptCard(state);

    if (state.name === 'permissao_ok_sem_inscricao') {
        void reportDiagnostic('onesignal.web_subscription_missing_after_grant', snapshot);
    }

    void verifyServiceWorkerScope(snapshot);
};

const requestPermission = async () => {
    updatePromptCard({
        hidden: false,
        busy: true,
        showButton: true,
        allowAction: false,
        message: 'Confirme no navegador para ativar as notificacoes.',
    });

    try {
        await withOneSignal(async (OneSignal, config) => {
            if (typeof OneSignal?.Slidedown?.promptPush === 'function') {
                await OneSignal.Slidedown.promptPush();
            } else if (typeof OneSignal?.Notifications?.requestPermission === 'function') {
                await OneSignal.Notifications.requestPermission();
            } else {
                const permission = await Notification.requestPermission();

                if (permission !== 'granted') {
                    return;
                }
            }

            if (config.externalId && typeof OneSignal?.login === 'function') {
                try {
                    await OneSignal.login(config.externalId);
                } catch (error) {
                    logConsole('onesignal.web_login_failed', { message: error?.message ?? String(error) }, 'warn');
                }
            }

            await syncKnownContacts(OneSignal, config);
        });
    } catch (error) {
        logConsole('onesignal.web_prompt_failed', { message: error?.message ?? String(error) }, 'warn');
        updatePromptCard({
            hidden: false,
            busy: false,
            showButton: true,
            allowAction: true,
            message: 'Nao foi possivel ativar agora. Tente novamente em alguns segundos.',
        });

        return;
    }

    await refreshPromptState();
    window.setTimeout(() => {
        void refreshPromptState();
    }, 1200);
};

const bindOneSignalSdkEvents = () => {
    void withOneSignal(async (OneSignal, config) => {
        if (window.__eduxOneSignalBindingsReady === true) {
            return;
        }

        window.__eduxOneSignalBindingsReady = true;

        const readySnapshot = await buildSnapshot(OneSignal, config);
        await reportDiagnostic('onesignal.web_sdk_ready', readySnapshot);
        await syncKnownContacts(OneSignal, config);

        OneSignal?.Notifications?.addEventListener?.('permissionPromptDisplay', async () => {
            const snapshot = await buildSnapshot(OneSignal, config);

            await reportDiagnostic('onesignal.web_prompt_displayed', snapshot);
            await refreshPromptState();
        });

        OneSignal?.Notifications?.addEventListener?.('permissionChange', async () => {
            const snapshot = await buildSnapshot(OneSignal, config);

            await reportDiagnostic('onesignal.web_permission_changed', snapshot);
            await refreshPromptState();
        });

        OneSignal?.User?.PushSubscription?.addEventListener?.('change', async () => {
            const snapshot = await buildSnapshot(OneSignal, config);

            await reportDiagnostic('onesignal.web_subscription_changed', snapshot);
            await refreshPromptState();
        });

        OneSignal?.User?.addEventListener?.('change', async () => {
            await syncKnownContacts(OneSignal, config);
            const snapshot = await buildSnapshot(OneSignal, config);

            await reportDiagnostic('onesignal.web_login_state_changed', snapshot);
            await refreshPromptState();
        });
    }).catch((error) => {
        logConsole('onesignal.web_bindings_failed', { message: error?.message ?? String(error) }, 'warn');
    });
};

const bindDocumentEvents = () => {
    if (bindingsReady) {
        return;
    }

    bindingsReady = true;

    document.addEventListener('click', (event) => {
        const trigger = event.target instanceof Element
            ? event.target.closest(promptTriggerSelector)
            : null;

        if (!trigger) {
            return;
        }

        event.preventDefault();
        void requestPermission();
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
    bindOneSignalSdkEvents();
    void refreshPromptState();

    window.addEventListener('edux:onesignal-ready', () => {
        bindOneSignalSdkEvents();
        void refreshPromptState();
    });
    window.addEventListener('edux:onesignal-skipped', () => {
        void refreshPromptState();
    });
    document.addEventListener('livewire:navigated', () => {
        void refreshPromptState();
    });
};
