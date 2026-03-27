<?php

namespace App\Mail;

use App\Models\Course;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class CourseEnrollmentNotification extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public Course $course,
        public string $accessUrl
    ) {}

    public function build(): self
    {
        return $this->subject('Você foi matriculado(a) em '.$this->course->title)
            ->view('emails.course-enrollment-notification')
            ->with([
                'user' => $this->user,
                'course' => $this->course,
                'accessUrl' => $this->accessUrl,
            ]);
    }
}
