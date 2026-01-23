<?php

namespace App\Jobs;

use App\Enums\UserRole;
use App\Models\Notification as EduNotification;
use App\Models\User;
use App\Notifications\StudentAnnouncementPush;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Notification as NotificationFacade;

class SendNotificationPush implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(private readonly int $notificationId)
    {
    }

    public function handle(): void
    {
        $notification = EduNotification::find($this->notificationId);

        if (! $notification) {
            return;
        }

        if (! $notification->published_at || $notification->published_at->isFuture() || $notification->pushed_at) {
            return;
        }

        User::query()
            ->where('role', UserRole::STUDENT)
            ->whereHas('pushSubscriptions')
            ->chunkById(500, function ($users) use ($notification): void {
                NotificationFacade::send($users, new StudentAnnouncementPush($notification));
            });

        $notification->pushed_at = now();
        $notification->save();
    }
}
