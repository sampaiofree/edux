<?php

namespace App\Jobs;

use App\Models\PaymentEvent;
use App\Support\Payments\PaymentWebhookProcessor;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessPaymentWebhookEvent implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $paymentEventId,
        public bool $force = false,
    ) {
    }

    public function handle(PaymentWebhookProcessor $processor): void
    {
        $event = PaymentEvent::find($this->paymentEventId);
        if (! $event) {
            return;
        }

        $processor->process($event, $this->force);
    }
}
