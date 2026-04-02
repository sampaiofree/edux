@php
    /** @var \App\Models\SystemSetting $settings */
    /** @var \App\Models\User $user */
    $serviceWorkerPath = 'push/onesignal/OneSignalSDKWorker.js';
    $serviceWorkerScope = '/push/onesignal/';
    $onesignalDebugMode = ! app()->environment('production');
@endphp
<script>
    window.__eduxOneSignalConfig = {
        appId: @json($settings->onesignal_app_id),
        externalId: @json($user->oneSignalExternalId()),
        serviceWorkerPath: @json($serviceWorkerPath),
        serviceWorkerScope: @json($serviceWorkerScope),
        diagnosticsUrl: @json(route('learning.onesignal.diagnostics.store')),
        debugMode: @json($onesignalDebugMode),
    };
    window.OneSignalDeferred = window.OneSignalDeferred || [];
    window.OneSignalDeferred.push(async function (OneSignal) {
        const config = window.__eduxOneSignalConfig;

        if (!config || !config.appId || !window.isSecureContext) {
            window.dispatchEvent(new CustomEvent('edux:onesignal-skipped'));

            return;
        }

        if (window.__eduxOneSignalInitializedAppId !== config.appId) {
            if (config.debugMode && OneSignal?.Debug?.setLogLevel) {
                OneSignal.Debug.setLogLevel('trace');
            }

            await OneSignal.init({
                appId: config.appId,
                serviceWorkerPath: config.serviceWorkerPath,
                serviceWorkerParam: { scope: config.serviceWorkerScope },
                notifyButton: {
                    enable: true,
                },
            });

            window.__eduxOneSignalInitializedAppId = config.appId;
        }

        if (config.externalId) {
            try {
                await OneSignal.login(config.externalId);
            } catch (error) {
                console.warn('[onesignal.web] login_failed', error);
            }
        }

        window.dispatchEvent(new CustomEvent('edux:onesignal-ready'));
    });
</script>
<script src="https://cdn.onesignal.com/sdks/web/v16/OneSignalSDK.page.js" defer></script>
