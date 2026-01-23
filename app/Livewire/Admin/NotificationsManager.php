<?php

namespace App\Livewire\Admin;

use App\Jobs\SendNotificationPush;
use App\Models\Notification;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Features\SupportFileUploads\WithFileUploads;

class NotificationsManager extends Component
{
    use WithPagination, WithFileUploads;

    public ?Notification $editing = null;
    public bool $showModal = false;

    public string $title = '';
    public ?string $body = null;
    public ?string $video_url = null;
    public ?string $button_label = null;
    public ?string $button_url = null;
    public ?string $published_at = null;
    public $image;

    protected $rules = [
        'title' => ['required', 'string', 'max:255'],
        'body' => ['nullable', 'string'],
        'video_url' => ['nullable', 'url'],
        'button_label' => ['nullable', 'string', 'max:100'],
        'button_url' => ['nullable', 'url'],
        'published_at' => ['nullable', 'date'],
        'image' => ['nullable', 'image', 'max:4096'],
    ];

    public function save(): void
    {
        $data = $this->validate();

        if ($this->editing) {
            $this->editing->fill($data);
        } else {
            $this->editing = new Notification($data);
        }

        if ($this->image) {
            if ($this->editing->image_path) {
                Storage::disk('public')->delete($this->editing->image_path);
            }

            $this->editing->image_path = $this->image->store('notifications', 'public');
        }

        $this->editing->save();
        $this->queuePushIfNeeded($this->editing);

        session()->flash('status', 'Notificacao salva.');
        $this->closeModal();
    }

    public function newNotification(): void
    {
        $this->resetForm();
        $this->showModal = true;
    }

    public function edit(int $notificationId): void
    {
        $notification = Notification::find($notificationId);

        if (! $notification) {
            return;
        }

        $this->editing = $notification;
        $this->fill([
            'title' => $notification->title,
            'body' => $notification->body,
            'video_url' => $notification->video_url,
            'button_label' => $notification->button_label,
            'button_url' => $notification->button_url,
            'published_at' => optional($notification->published_at)->format('Y-m-d\TH:i'),
        ]);

        $this->showModal = true;
    }

    public function delete(int $notificationId): void
    {
        $notification = Notification::find($notificationId);

        if (! $notification) {
            return;
        }

        if ($notification->image_path) {
            Storage::disk('public')->delete($notification->image_path);
        }

        $notification->delete();

        if ($this->showModal) {
            $this->closeModal();
        } else {
            $this->resetForm();
        }

        session()->flash('status', 'Notificacao removida.');
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->resetForm();
    }

    public function render()
    {
        return view('livewire.admin.notifications-manager', [
            'notifications' => Notification::latest()->paginate(10),
        ]);
    }

    private function resetForm(): void
    {
        $this->editing = null;
        $this->image = null;
        $this->title = '';
        $this->body = null;
        $this->video_url = null;
        $this->button_label = null;
        $this->button_url = null;
        $this->published_at = null;
    }

    private function queuePushIfNeeded(Notification $notification): void
    {
        if (! $notification->published_at || $notification->pushed_at) {
            return;
        }

        if ($notification->published_at->isFuture()) {
            SendNotificationPush::dispatch($notification->id)->delay($notification->published_at);

            return;
        }

        SendNotificationPush::dispatch($notification->id);
    }
}
