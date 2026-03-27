<?php

namespace Tests\Feature;

use App\Models\AccountDeletionRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AccountDeletionRequestFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_sees_deletion_card_on_account_page(): void
    {
        $student = $this->defaultTenantStudent();

        $response = $this->actingAs($student)->get(route('account.edit'));

        $response->assertOk();
        $response->assertSee('Solicitar exclusao da conta');
    }

    public function test_admin_does_not_see_deletion_card_on_account_page(): void
    {
        $admin = $this->defaultTenantAdmin();

        $response = $this->actingAs($admin)->get(route('account.edit'));

        $response->assertOk();
        $response->assertDontSee('Solicitar exclusao da conta');
    }

    public function test_student_can_create_deletion_request_with_optional_reason(): void
    {
        $student = $this->defaultTenantStudent([
            'whatsapp' => '11999999999',
        ]);

        $response = $this->actingAs($student)->post(route('account.deletion-requests.store'), [
            'reason' => 'Nao quero mais usar a plataforma.',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('account_deletion_requests', [
            'user_id' => $student->id,
            'requested_name' => $student->name,
            'requested_email' => $student->email,
            'requested_whatsapp' => '11999999999',
            'status' => AccountDeletionRequest::STATUS_PENDING,
            'reason' => 'Nao quero mais usar a plataforma.',
        ]);
    }

    public function test_student_cannot_create_duplicate_pending_request(): void
    {
        $student = $this->defaultTenantStudent();

        AccountDeletionRequest::query()->create([
            'user_id' => $student->id,
            'requested_name' => $student->name,
            'requested_email' => $student->email,
            'requested_whatsapp' => $student->whatsapp,
            'reason' => null,
            'status' => AccountDeletionRequest::STATUS_PENDING,
            'requested_at' => now(),
        ]);

        $response = $this->actingAs($student)->post(route('account.deletion-requests.store'), [
            'reason' => 'Novo pedido',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseCount('account_deletion_requests', 1);
    }

    public function test_only_admin_can_access_admin_listing(): void
    {
        $admin = $this->defaultTenantAdmin();
        $student = $this->createStudentForTenant($admin);

        $this->actingAs($student)
            ->get(route('admin.account-deletion-requests.index'))
            ->assertForbidden();

        $this->actingAs($admin)
            ->get(route('admin.account-deletion-requests.index'))
            ->assertOk();
    }

    public function test_admin_can_delete_student_account_and_finalize_request(): void
    {
        Storage::fake('public');

        $admin = $this->defaultTenantAdmin();
        $student = $this->createStudentForTenant($admin, [
            'profile_photo_path' => 'profile-photos/student-photo.jpg',
        ]);

        Storage::disk('public')->put('profile-photos/student-photo.jpg', 'fake');
        DB::table('sessions')->insert([
            'id' => 'session-student-1',
            'user_id' => $student->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'phpunit',
            'payload' => 'payload',
            'last_activity' => time(),
        ]);

        $request = AccountDeletionRequest::query()->create([
            'user_id' => $student->id,
            'requested_name' => $student->name,
            'requested_email' => $student->email,
            'requested_whatsapp' => $student->whatsapp,
            'reason' => 'Teste',
            'status' => AccountDeletionRequest::STATUS_PENDING,
            'requested_at' => now(),
        ]);

        $response = $this->actingAs($admin)->post(route('admin.account-deletion-requests.destroy-account', $request));

        $response->assertRedirect();
        $this->assertDatabaseMissing('users', ['id' => $student->id]);
        $this->assertDatabaseMissing('sessions', ['user_id' => $student->id]);
        Storage::disk('public')->assertMissing('profile-photos/student-photo.jpg');

        $this->assertDatabaseHas('account_deletion_requests', [
            'id' => $request->id,
            'status' => AccountDeletionRequest::STATUS_DELETED,
            'resolved_by' => $admin->id,
        ]);
    }

    public function test_admin_marks_request_as_deleted_when_user_already_missing(): void
    {
        $admin = $this->defaultTenantAdmin();
        $student = $this->createStudentForTenant($admin);

        $request = AccountDeletionRequest::query()->create([
            'user_id' => $student->id,
            'requested_name' => $student->name,
            'requested_email' => $student->email,
            'requested_whatsapp' => $student->whatsapp,
            'reason' => null,
            'status' => AccountDeletionRequest::STATUS_PENDING,
            'requested_at' => now(),
        ]);

        $student->delete();

        $response = $this->actingAs($admin)->post(route('admin.account-deletion-requests.destroy-account', $request));

        $response->assertRedirect();
        $this->assertDatabaseHas('account_deletion_requests', [
            'id' => $request->id,
            'status' => AccountDeletionRequest::STATUS_DELETED,
            'resolved_by' => $admin->id,
        ]);
    }

    public function test_admin_can_reject_request(): void
    {
        $admin = $this->defaultTenantAdmin();
        $student = $this->createStudentForTenant($admin);

        $request = AccountDeletionRequest::query()->create([
            'user_id' => $student->id,
            'requested_name' => $student->name,
            'requested_email' => $student->email,
            'requested_whatsapp' => $student->whatsapp,
            'reason' => null,
            'status' => AccountDeletionRequest::STATUS_PENDING,
            'requested_at' => now(),
        ]);

        $response = $this->actingAs($admin)->post(route('admin.account-deletion-requests.reject', $request), [
            'resolution_note' => 'Cadastro inconsistente no momento.',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('account_deletion_requests', [
            'id' => $request->id,
            'status' => AccountDeletionRequest::STATUS_REJECTED,
            'resolved_by' => $admin->id,
            'resolution_note' => 'Cadastro inconsistente no momento.',
        ]);
    }
}
