<?php

namespace App\Notifications;

use App\Models\Notification as EduNotification;
use App\Models\SystemSetting;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

class StudentAnnouncementPush extends Notification
{
    public function __construct(private readonly EduNotification $notification)
    {
    }

    public function via(object $notifiable): array
    {
        return [WebPushChannel::class];
    }

    public function toWebPush(object $notifiable, Notification $notification): WebPushMessage
    {
        $settings = SystemSetting::current();
        $icon = $settings->assetUrl('favicon_path') ?? $settings->assetUrl('default_logo_dark_path');
        $image = $this->notification->image_path
            ? asset('storage/'.$this->notification->image_path)
            : null;
        $body = $this->notification->body
            ? Str::of($this->notification->body)->limit(160)->toString()
            : null;
        $targetUrl = $this->notification->button_url ?: route('learning.notifications.index');

        $message = (new WebPushMessage())
            ->title($this->notification->title)
            ->tag('notification-'.$this->notification->id)
            ->data([
                'url' => $targetUrl,
                'notification_id' => $this->notification->id,
            ]);

        if ($body) {
            $message->body($body);
        }

        if ($icon) {
            $message->icon($icon)->badge($icon);
        }

        if ($image) {
            $message->image($image);
        }

        return $message;
    }
}
