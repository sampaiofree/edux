@php
    /** @var \App\Models\SystemSetting $settings */
    /** @var \App\Models\User $user */
    $serviceWorkerPath = 'push/onesignal/OneSignalSDKWorker.js';
    $serviceWorkerScope = '/push/onesignal/';
@endphp
<script>
    window.__eduxOneSignalConfig = {
        appId: @json($settings->onesignal_app_id),
        externalId: @json($user->oneSignalExternalId()),
        serviceWorkerPath: @json($serviceWorkerPath),
        serviceWorkerScope: @json($serviceWorkerScope),
    };
    window.OneSignalDeferred = window.OneSignalDeferred || [];
    window.OneSignalDeferred.push(async function (OneSignal) {
        const config = window.__eduxOneSignalConfig;

        if (!config || !config.appId || !window.isSecureContext) {
            window.dispatchEvent(new CustomEvent('edux:onesignal-skipped'));

            return;
        }

        if (window.__eduxOneSignalInitializedAppId !== config.appId) {
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
            await OneSignal.login(config.externalId);
        }

        window.dispatchEvent(new CustomEvent('edux:onesignal-ready'));
    });
</script>
<script src="https://cdn.onesignal.com/sdks/web/v16/OneSignalSDK.page.js" defer></script>
