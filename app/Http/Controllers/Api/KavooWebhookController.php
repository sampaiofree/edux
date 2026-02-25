<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Enums\UserRole;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Kavoo;
use App\Models\User;
use App\Mail\WelcomeKavooUser;
use App\Support\KavooTrackingAttributionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class KavooWebhookController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $payload = $request->json()->all();

        $customer = Arr::get($payload, 'customer', []);
        $address = Arr::get($payload, 'address', []);
        $items = Arr::get($payload, 'items', []);
        $affiliate = Arr::get($payload, 'affiliate', []);
        $transaction = Arr::get($payload, 'transaction', []);

        if (! is_array($items) || $items === []) {
            return response()->json(['status' => 'ok'], 200);
        }

        $kavooData = [
            'customer_name' => Arr::get($customer, 'name'),
            'customer_first_name' => Arr::get($customer, 'first_name'),
            'customer_last_name' => Arr::get($customer, 'last_name'),
            'customer_email' => Arr::get($customer, 'email'),
            'customer_phone' => Arr::get($customer, 'phone'),
            'transaction_code' => Arr::get($transaction, 'code'),
            'status_code' => Arr::get(Arr::get($payload, 'status', []), 'code'),
            'customer' => $customer,
            'address' => $address,
            'items' => $items,
            'affiliate' => $affiliate,
            'transaction' => $transaction,
            'payment' => Arr::get($payload, 'payment'),
            'commissions' => Arr::get($payload, 'commissions'),
            'shipping' => Arr::get($payload, 'shipping'),
            'links' => Arr::get($payload, 'links'),
            'tracking' => Arr::get($payload, 'tracking'),
            'status' => Arr::get($payload, 'status'),
            'recurrence' => Arr::get($payload, 'recurrence'),
        ];

        $transactionCode = Arr::get($transaction, 'code');

        $user = null;

        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }

            $itemProductId = Arr::get($item, 'product_id');

            $itemData = $kavooData + [
                'item_product_id' => $itemProductId,
                'item_product_name' => Arr::get($item, 'product_name'),
                'items' => [$item],
            ];

            try {
                $kavoo = ($transactionCode && $itemProductId !== null && $itemProductId !== '')
                    ? Kavoo::updateOrCreate(
                        ['transaction_code' => $transactionCode, 'item_product_id' => $itemProductId],
                        $itemData
                    )
                    : Kavoo::create($itemData);
            } catch (\Throwable $exception) {
                Log::error('Falha ao salvar registro Kavoo', [
                    'error' => $exception->getMessage(),
                    'transaction_code' => $transactionCode,
                    'item_product_id' => $itemProductId,
                ]);
                continue;
            }

            $course = $kavooData['status_code'] === 'SALE_APPROVED'
                ? $this->resolveCourseForKavoo($kavoo)
                : null;

            if ($user === null && $kavooData['status_code'] === 'SALE_APPROVED') {
                $user = $this->ensureCustomerUser($kavoo);
            }

            if ($user) {
                $this->ensureEnrollment($kavoo, $user, $course);
            }

            if ($kavooData['status_code'] === 'SALE_APPROVED') {
                $this->recordApprovedTracking($kavoo, $course, $user);
            }
        }

        return response()->json(['status' => 'ok'], 200);
    }

    private function ensureCustomerUser(Kavoo $kavoo): ?User
    {
        $customerEmail = $kavoo->customer_email;

        if (empty($customerEmail)) {
            return null;
        }

        $existingUser = User::where('email', $customerEmail)->first();
        if ($existingUser) {
            return $existingUser;
        }

        try {
            $user = User::create([
                'name' => $kavoo->customer_name ?? $customerEmail,
                'display_name' => $kavoo->customer_name ?? $customerEmail,
                'email' => $customerEmail,
                'whatsapp' => $kavoo->customer_phone,
                'role' => UserRole::STUDENT,
                'password' => 'mudar123',
            ]);

            try {
                Mail::to($user->email)->send(new WelcomeKavooUser($user, 'mudar123'));
            } catch (\Throwable $mailException) {
                Log::warning('Falha ao enviar e-mail de boas-vindas Kavoo', [
                    'error' => $mailException->getMessage(),
                    'customer_email' => $customerEmail,
                    'kavoo_id' => $kavoo->id,
                ]);
            }

            return $user;
        } catch (\Throwable $exception) {
            Log::error('Falha ao criar usuario da Kavoo', [
                'error' => $exception->getMessage(),
                'customer_email' => $customerEmail,
                'kavoo_id' => $kavoo->id,
            ]);
        }

        return null;
    }

    private function ensureEnrollment(Kavoo $kavoo, User $user, ?Course $course = null): void
    {
        $course = $course ?? $this->resolveCourseForKavoo($kavoo);
        if (! $course) {
            return;
        }

        if (Enrollment::where([
            'course_id' => $course->id,
            'user_id' => $user->id,
        ])->exists()) {
            return;
        }

        try {
            Enrollment::create([
                'course_id' => $course->id,
                'user_id' => $user->id,
            ]);
        } catch (\Throwable $exception) {
            Log::error('Falha ao criar matricula Kavoo', [
                'error' => $exception->getMessage(),
                'course_id' => $course->id,
                'user_id' => $user->id,
                'kavoo_id' => $kavoo->id,
            ]);
        }
    }

    private function resolveCourseForKavoo(Kavoo $kavoo): ?Course
    {
        $itemProductId = $kavoo->item_product_id;

        if (empty($itemProductId)) {
            return null;
        }

        return Course::where('kavoo_id', $itemProductId)->first();
    }

    private function recordApprovedTracking(Kavoo $kavoo, ?Course $course, ?User $user): void
    {
        try {
            app(KavooTrackingAttributionService::class)->recordApprovedSale($kavoo, $course, $user);
        } catch (\Throwable $exception) {
            Log::warning('Falha ao registrar tracking de conversao Kavoo', [
                'error' => $exception->getMessage(),
                'kavoo_id' => $kavoo->id,
                'transaction_code' => $kavoo->transaction_code,
                'item_product_id' => $kavoo->item_product_id,
            ]);
        }
    }
}
