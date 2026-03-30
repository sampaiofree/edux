<?php

namespace App\Jobs;

use App\Enums\UserRole;
use App\Models\Notification as EduNotification;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use App\Support\OneSignal\OneSignalPushService;

class SendNotificationPush implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(private readonly int $notificationId) {}

    public function handle(): void
    {
        $notification = EduNotification::find($this->notificationId);

        if (! $notification) {
            return;
        }

        if (! $notification->published_at || $notification->published_at->isFuture() || $notification->pushed_at) {
            return;
        }

        $settings = $notification->systemSetting;
        $pushService = app(OneSignalPushService::class);

        if (! $settings || ! $pushService->isConfiguredFor($settings)) {
            Log::warning('onesignal.push_skipped_missing_configuration', [
                'notification_id' => $notification->id,
                'system_setting_id' => $notification->system_setting_id,
            ]);

            return;
        }

        $attempted = false;

        User::withoutGlobalScopes()
            ->where('role', UserRole::STUDENT->value)
            ->where('system_setting_id', $notification->system_setting_id)
            ->chunkById(500, function ($users) use ($notification, $settings, $pushService, &$attempted): void {
                if ($users->isEmpty()) {
                    return;
                }

                $attempted = true;
                $pushService->sendNotification($settings, $notification, $users);
            });

        if (! $attempted) {
            Log::info('onesignal.push_skipped_no_students', [
                'notification_id' => $notification->id,
                'system_setting_id' => $notification->system_setting_id,
            ]);
        }

        $notification->pushed_at = now();
        $notification->save();
    }
}
