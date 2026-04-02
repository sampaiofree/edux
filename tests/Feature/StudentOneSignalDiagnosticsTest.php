<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class StudentOneSignalDiagnosticsTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_can_send_safe_onesignal_browser_diagnostics(): void
    {
        Log::spy();

        $student = $this->defaultTenantStudent([
            'email' => 'aluno-diagnostics@example.com',
        ]);

        $response = $this->actingAs($student)->postJson(route('learning.onesignal.diagnostics.store'), [
            'event' => 'onesignal.web_subscription_changed',
            'permission' => 'granted',
            'opted_in' => true,
            'external_id_matches' => true,
            'email_present' => true,
            'subscription_id_present' => true,
            'subscription_id_hash' => 'abcdef1234567890',
            'sms_phone_present' => true,
            'sms_phone_hash' => '1234abcd5678ef90',
            'token_present' => true,
            'onesignal_id_present' => true,
            'sdk_ready' => true,
            'service_worker_scope' => 'https://cursos.example.test/push/onesignal/',
            'url' => 'https://cursos.example.test/dashboard?tab=cursos',
            'user_agent' => 'Mozilla/5.0 Test Browser',
        ]);

        $response->assertAccepted()->assertJson(['status' => 'ok']);

        Log::shouldHaveReceived('info')->withArgs(function (string $message, array $context) use ($student): bool {
            return $message === 'onesignal.web_subscription_changed'
                && ($context['user_id'] ?? null) === $student->id
                && ($context['system_setting_id'] ?? null) === $student->system_setting_id
                && ($context['permission'] ?? null) === 'granted'
                && ($context['email_present'] ?? null) === true
                && ($context['subscription_id_hash'] ?? null) === 'abcdef1234567890'
                && ($context['sms_phone_present'] ?? null) === true
                && ($context['sms_phone_hash'] ?? null) === '1234abcd5678ef90'
                && ($context['token_present'] ?? null) === true
                && ! array_key_exists('subscription_id', $context)
                && ! array_key_exists('sms_phone', $context)
                && ! array_key_exists('token', $context);
        })->once();
    }

    public function test_subscription_missing_after_grant_is_logged_as_warning(): void
    {
        Log::spy();

        $student = $this->defaultTenantStudent([
            'email' => 'aluno-warning@example.com',
        ]);

        $response = $this->actingAs($student)->postJson(route('learning.onesignal.diagnostics.store'), [
            'event' => 'onesignal.web_subscription_missing_after_grant',
            'permission' => 'granted',
            'opted_in' => false,
            'external_id_matches' => false,
            'subscription_id_present' => false,
            'token_present' => false,
            'onesignal_id_present' => true,
            'sdk_ready' => true,
        ]);

        $response->assertAccepted();

        Log::shouldHaveReceived('warning')->withArgs(function (string $message, array $context) use ($student): bool {
            return $message === 'onesignal.web_subscription_missing_after_grant'
                && ($context['user_id'] ?? null) === $student->id
                && ($context['permission'] ?? null) === 'granted';
        })->once();
    }

    public function test_guest_cannot_send_onesignal_browser_diagnostics(): void
    {
        $response = $this->postJson(route('learning.onesignal.diagnostics.store'), [
            'event' => 'onesignal.web_sdk_ready',
        ]);

        $response->assertUnauthorized();
    }

    public function test_contact_sync_failure_is_logged_as_warning_without_raw_phone_data(): void
    {
        Log::spy();

        $student = $this->defaultTenantStudent([
            'email' => 'aluno-contact-sync@example.com',
        ]);

        $response = $this->actingAs($student)->postJson(route('learning.onesignal.diagnostics.store'), [
            'event' => 'onesignal.web_contact_sync_failed',
            'permission' => 'default',
            'opted_in' => false,
            'external_id_matches' => true,
            'email_present' => true,
            'subscription_id_present' => false,
            'sms_phone_present' => true,
            'sms_phone_hash' => '55aa77bb99cc11dd',
            'token_present' => false,
            'onesignal_id_present' => true,
            'sdk_ready' => true,
        ]);

        $response->assertAccepted();

        Log::shouldHaveReceived('warning')->withArgs(function (string $message, array $context): bool {
            return $message === 'onesignal.web_contact_sync_failed'
                && ($context['email_present'] ?? null) === true
                && ($context['sms_phone_present'] ?? null) === true
                && ($context['sms_phone_hash'] ?? null) === '55aa77bb99cc11dd'
                && ! array_key_exists('sms_phone', $context);
        })->once();
    }

    public function test_modal_shown_event_is_accepted_and_logged(): void
    {
        Log::spy();

        $student = $this->defaultTenantStudent([
            'email' => 'aluno-modal@example.com',
        ]);

        $response = $this->actingAs($student)->postJson(route('learning.onesignal.diagnostics.store'), [
            'event' => 'onesignal.web_modal_shown',
            'permission' => 'default',
            'opted_in' => false,
            'external_id_matches' => true,
            'subscription_id_present' => false,
            'token_present' => false,
            'onesignal_id_present' => true,
            'sdk_ready' => true,
        ]);

        $response->assertAccepted();

        Log::shouldHaveReceived('info')->withArgs(function (string $message, array $context) use ($student): bool {
            return $message === 'onesignal.web_modal_shown'
                && ($context['user_id'] ?? null) === $student->id
                && ($context['sdk_ready'] ?? null) === true;
        })->once();
    }
}
