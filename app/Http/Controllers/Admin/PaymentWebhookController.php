<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PaymentInternalAction;
use App\Http\Controllers\Controller;
use App\Jobs\ProcessPaymentWebhookEvent;
use App\Models\PaymentEvent;
use App\Models\PaymentEventMapping;
use App\Models\PaymentFieldMapping;
use App\Models\PaymentProductMapping;
use App\Models\PaymentWebhookLink;
use App\Support\Payments\PaymentWebhookProcessor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PaymentWebhookController extends Controller
{
    public function __construct()
    {
        $this->middleware('role:admin');
    }

    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));

        $links = PaymentWebhookLink::query()
            ->withCount([
                'events as events_count',
                'events as pending_events_count' => fn ($query) => $query->where('processing_status', 'pending'),
            ])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($sub) use ($search) {
                    $sub->where('name', 'like', "%{$search}%")
                        ->orWhere('endpoint_uuid', 'like', "%{$search}%");
                });
            })
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return view('admin.webhooks.index', compact('links', 'search'));
    }

    public function create(): View
    {
        return view('admin.webhooks.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate($this->linkValidationRules());
        $systemSettingId = $request->user()?->adminContextSystemSettingId();

        $link = PaymentWebhookLink::create([
            'system_setting_id' => $systemSettingId,
            'name' => $validated['name'],
            'endpoint_uuid' => (string) Str::uuid(),
            'is_active' => (bool) ($validated['is_active'] ?? false),
            'action_mode' => $validated['action_mode'],
            'security_mode' => $validated['security_mode'] ?: null,
            'secret' => $validated['secret'] ?: null,
            'signature_header' => $validated['signature_header'] ?: null,
            'created_by' => $request->user()?->id,
        ]);

        return redirect()
            ->route('admin.webhooks.edit', $link)
            ->with('status', 'Link de webhook criado.');
    }

    public function edit(PaymentWebhookLink $webhookLink): View
    {
        $webhookLink->load([
            'fieldMappings' => fn ($query) => $query
                ->whereIn('field_key', $this->fieldKeys())
                ->orderBy('field_key'),
            'events' => fn ($query) => $query->latest()->limit(20),
        ]);

        $simulationPayload = $this->referencePayloadForLink($webhookLink);
        $simulationPreview = session(
            $this->simulationPreviewSessionKey($webhookLink),
            session('simulation_preview')
        );
        $jsonPathOptions = $this->allowedJsonPathsForLink($webhookLink, $simulationPayload);

        return view('admin.webhooks.edit', [
            'link' => $webhookLink,
            'simulationPreview' => $simulationPreview,
            'simulationPayload' => $simulationPayload,
            'jsonPathOptions' => $jsonPathOptions,
            'fieldLabels' => PaymentFieldMapping::configurableFields(),
        ]);
    }

    public function update(Request $request, PaymentWebhookLink $webhookLink): RedirectResponse
    {
        $validated = $request->validate($this->linkValidationRules());

        $webhookLink->update([
            'name' => $validated['name'],
            'is_active' => (bool) ($validated['is_active'] ?? false),
            'action_mode' => $validated['action_mode'],
            'security_mode' => $validated['security_mode'] ?: null,
            'secret' => $validated['secret'] ?: null,
            'signature_header' => $validated['signature_header'] ?: null,
        ]);

        return redirect()
            ->route('admin.webhooks.edit', $webhookLink)
            ->with('status', 'Link de webhook atualizado.');
    }

    public function destroy(PaymentWebhookLink $webhookLink): RedirectResponse
    {
        $webhookLink->delete();

        return redirect()
            ->route('admin.webhooks.index')
            ->with('status', 'Link removido.');
    }

    public function upsertFieldMapping(Request $request, PaymentWebhookLink $webhookLink): RedirectResponse
    {
        $allowedJsonPaths = $this->allowedJsonPathsForLink($webhookLink, $this->referencePayloadForLink($webhookLink));

        $rules = [
            'field_mappings' => ['nullable', 'array'],
        ];

        foreach ($this->fieldKeys() as $fieldKey) {
            $rules["field_mappings.{$fieldKey}.json_path"] = ['nullable', 'string', 'max:255', Rule::in($allowedJsonPaths)];
        }

        $validated = $request->validate($rules);
        $fieldMappings = $this->normalizeFieldMappings($validated['field_mappings'] ?? []);

        DB::transaction(function () use ($fieldMappings, $webhookLink): void {
            $webhookLink->fieldMappings()
                ->whereIn('field_key', $this->fieldKeys())
                ->delete();

            $records = collect($fieldMappings)
                ->map(fn (?string $jsonPath, string $fieldKey): ?array => $jsonPath === null
                    ? null
                    : [
                        'field_key' => $fieldKey,
                        'json_path' => $jsonPath,
                        'is_required' => false,
                    ])
                ->filter()
                ->values()
                ->all();

            if ($records !== []) {
                $webhookLink->fieldMappings()->createMany($records);
            }
        });

        return redirect()
            ->route('admin.webhooks.edit', $webhookLink)
            ->with('status', 'Mapeamentos de campos salvos.');
    }

    public function removeFieldMapping(PaymentWebhookLink $webhookLink, PaymentFieldMapping $mapping): RedirectResponse
    {
        abort_if($mapping->payment_webhook_link_id !== $webhookLink->id, 404);

        $mapping->delete();

        return redirect()
            ->route('admin.webhooks.edit', $webhookLink)
            ->with('status', 'Mapeamento de campo removido.');
    }

    public function upsertEventMapping(Request $request, PaymentWebhookLink $webhookLink): RedirectResponse
    {
        $validated = $request->validate([
            'external_event_code' => ['required', 'string', 'max:120'],
            'internal_action' => ['required', Rule::in(array_map(
                static fn (PaymentInternalAction $action) => $action->value,
                PaymentInternalAction::cases()
            ))],
        ]);

        PaymentEventMapping::updateOrCreate(
            [
                'payment_webhook_link_id' => $webhookLink->id,
                'external_event_code' => trim($validated['external_event_code']),
            ],
            [
                'internal_action' => $validated['internal_action'],
            ]
        );

        return redirect()
            ->route('admin.webhooks.edit', $webhookLink)
            ->with('status', 'Mapeamento de evento salvo.');
    }

    public function removeEventMapping(PaymentWebhookLink $webhookLink, PaymentEventMapping $mapping): RedirectResponse
    {
        abort_if($mapping->payment_webhook_link_id !== $webhookLink->id, 404);

        $mapping->delete();

        return redirect()
            ->route('admin.webhooks.edit', $webhookLink)
            ->with('status', 'Mapeamento de evento removido.');
    }

    public function upsertProductMapping(Request $request, PaymentWebhookLink $webhookLink): RedirectResponse
    {
        $systemSettingId = (int) ($request->user()?->adminContextSystemSettingId() ?? 0);

        $validated = $request->validate([
            'external_product_id' => ['required', 'string', 'max:191'],
            'course_id' => ['required', 'integer', Rule::exists('courses', 'id')->where('system_setting_id', $systemSettingId)],
            'is_active' => ['nullable', 'boolean'],
        ]);

        PaymentProductMapping::updateOrCreate(
            [
                'payment_webhook_link_id' => $webhookLink->id,
                'external_product_id' => trim($validated['external_product_id']),
            ],
            [
                'course_id' => (int) $validated['course_id'],
                'is_active' => (bool) ($validated['is_active'] ?? false),
            ]
        );

        return redirect()
            ->route('admin.webhooks.edit', $webhookLink)
            ->with('status', 'Mapeamento de produto salvo.');
    }

    public function removeProductMapping(PaymentWebhookLink $webhookLink, PaymentProductMapping $mapping): RedirectResponse
    {
        abort_if($mapping->payment_webhook_link_id !== $webhookLink->id, 404);

        $mapping->delete();

        return redirect()
            ->route('admin.webhooks.edit', $webhookLink)
            ->with('status', 'Mapeamento de produto removido.');
    }

    public function events(Request $request, PaymentWebhookLink $webhookLink): View
    {
        $status = trim((string) $request->query('status', ''));
        $search = trim((string) $request->query('search', ''));

        $events = PaymentEvent::query()
            ->where('payment_webhook_link_id', $webhookLink->id)
            ->when($status !== '', fn ($query) => $query->where('processing_status', $status))
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($sub) use ($search) {
                    $sub->where('external_event_code', 'like', "%{$search}%")
                        ->orWhere('buyer_email', 'like', "%{$search}%")
                        ->orWhere('external_tx_id', 'like', "%{$search}%")
                        ->orWhere('external_product_id', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate(30)
            ->withQueryString();

        return view('admin.webhooks.events', compact('webhookLink', 'events', 'status', 'search'));
    }

    public function showEvent(PaymentWebhookLink $webhookLink, PaymentEvent $paymentEvent): View
    {
        abort_if($paymentEvent->payment_webhook_link_id !== $webhookLink->id, 404);

        $paymentEvent->load('logs');

        return view('admin.webhooks.event-show', [
            'webhookLink' => $webhookLink,
            'event' => $paymentEvent,
        ]);
    }

    public function replay(PaymentWebhookLink $webhookLink, PaymentEvent $paymentEvent): RedirectResponse
    {
        abort_if($paymentEvent->payment_webhook_link_id !== $webhookLink->id, 404);

        ProcessPaymentWebhookEvent::dispatch($paymentEvent->id, true);

        return redirect()
            ->route('admin.webhooks.events.show', [$webhookLink, $paymentEvent])
            ->with('status', 'Replay agendado para fila.');
    }

    public function simulate(Request $request, PaymentWebhookLink $webhookLink, PaymentWebhookProcessor $processor): RedirectResponse
    {
        $validated = $request->validate([
            'payload_json' => ['required', 'string'],
        ]);

        $decoded = json_decode((string) $validated['payload_json'], true);

        if (! is_array($decoded)) {
            return redirect()
                ->route('admin.webhooks.edit', $webhookLink)
                ->withErrors(['payload_json' => 'JSON invalido para simulacao.'])
                ->withInput();
        }

        $preview = $processor->preview($webhookLink, $decoded);

        return redirect()
            ->route('admin.webhooks.edit', $webhookLink)
            ->with($this->simulationPreviewSessionKey($webhookLink), $preview)
            ->with($this->simulationPayloadSessionKey($webhookLink), (string) $validated['payload_json'])
            // Compatibilidade com sessao antiga.
            ->with('simulation_preview', $preview)
            ->with('simulation_payload', (string) $validated['payload_json'])
            ->with('status', 'Simulacao concluida.');
    }

    private function simulationPayloadSessionKey(PaymentWebhookLink $webhookLink): string
    {
        return 'admin.webhooks.simulation_payload.'.$webhookLink->id;
    }

    private function simulationPreviewSessionKey(PaymentWebhookLink $webhookLink): string
    {
        return 'admin.webhooks.simulation_preview.'.$webhookLink->id;
    }

    private function referencePayloadForLink(PaymentWebhookLink $webhookLink): string
    {
        $simulationPayload = (string) session(
            $this->simulationPayloadSessionKey($webhookLink),
            (string) session('simulation_payload', '')
        );

        if (trim($simulationPayload) !== '') {
            return $simulationPayload;
        }

        $latestEventPayload = $webhookLink->events()
            ->latest()
            ->first()?->raw_payload;

        if (! is_array($latestEventPayload) || $latestEventPayload === []) {
            return '';
        }

        $encoded = json_encode($latestEventPayload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return is_string($encoded) ? $encoded : '';
    }

    /**
     * @return array<int, string>
     */
    private function allowedJsonPathsForLink(PaymentWebhookLink $webhookLink, string $simulationPayload): array
    {
        $paths = $this->extractJsonPathsFromPayload($simulationPayload);
        $currentMappings = $webhookLink->fieldMappings
            ->whereIn('field_key', $this->fieldKeys())
            ->pluck('json_path')
            ->filter(fn (mixed $path): bool => is_string($path) && trim($path) !== '')
            ->map(fn (string $path): string => trim($path))
            ->all();

        $merged = [...$paths, ...$currentMappings];

        return array_values(array_unique($merged));
    }

    /**
     * @return array<int, string>
     */
    private function extractJsonPathsFromPayload(string $payloadJson): array
    {
        if (trim($payloadJson) === '') {
            return [];
        }

        $decoded = json_decode($payloadJson, true);
        if (! is_array($decoded)) {
            return [];
        }

        $absolutePaths = $this->collectAbsoluteJsonPaths($decoded);
        $relativeItemPaths = $this->collectRelativeItemPaths($decoded);

        $paths = [...$absolutePaths, ...$relativeItemPaths];
        sort($paths);

        return array_values(array_unique($paths));
    }

    /**
     * @return array<int, string>
     */
    private function collectAbsoluteJsonPaths(mixed $value, string $prefix = ''): array
    {
        $paths = [];

        if ($prefix !== '') {
            $paths[] = $prefix;
        }

        if (! is_array($value)) {
            return $paths;
        }

        if (array_is_list($value) && $prefix !== '') {
            $wildcardPrefix = $prefix.'.*';
            $paths[] = $wildcardPrefix;

            $firstItem = $value[0] ?? null;
            if ($firstItem !== null) {
                $paths = [...$paths, ...$this->collectAbsoluteJsonPaths($firstItem, $wildcardPrefix)];
            }
        }

        foreach ($value as $key => $item) {
            $segment = (string) $key;
            $next = $prefix === '' ? $segment : $prefix.'.'.$segment;
            $paths = [...$paths, ...$this->collectAbsoluteJsonPaths($item, $next)];
        }

        return $paths;
    }

    /**
     * @return array<int, string>
     */
    private function collectRelativeItemPaths(array $payload): array
    {
        $paths = [];

        foreach ($payload as $value) {
            if (! is_array($value) || ! array_is_list($value)) {
                continue;
            }

            foreach ($value as $item) {
                if (! is_array($item)) {
                    continue;
                }

                $paths = [...$paths, ...$this->collectNestedPaths($item)];
            }
        }

        return $paths;
    }

    /**
     * @return array<int, string>
     */
    private function collectNestedPaths(array $value, string $prefix = ''): array
    {
        $paths = [];

        foreach ($value as $key => $item) {
            if (is_int($key)) {
                continue;
            }

            $segment = (string) $key;
            $path = $prefix === '' ? $segment : $prefix.'.'.$segment;
            $paths[] = $path;

            if (is_array($item)) {
                $paths = [...$paths, ...$this->collectNestedPaths($item, $path)];
            }
        }

        return $paths;
    }

    /**
     * @return array<string, array<int, mixed>|string>
     */
    private function linkValidationRules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
            'action_mode' => ['required', Rule::in(array_keys(PaymentWebhookLink::actionModes()))],
            'security_mode' => ['nullable', Rule::in(['', 'header_secret', 'hmac_sha256'])],
            'secret' => ['nullable', 'string', 'max:255'],
            'signature_header' => ['nullable', 'string', 'max:120'],
        ];
    }

    /**
     * @return array<int, string>
     */
    private function fieldKeys(): array
    {
        return array_keys(PaymentFieldMapping::configurableFields());
    }

    /**
     * @return array<string, ?string>
     */
    private function normalizeFieldMappings(mixed $rawFieldMappings): array
    {
        $rawFieldMappings = is_array($rawFieldMappings) ? $rawFieldMappings : [];
        $normalized = [];

        foreach ($this->fieldKeys() as $fieldKey) {
            $jsonPath = data_get($rawFieldMappings, "{$fieldKey}.json_path");
            $jsonPath = is_string($jsonPath) ? trim($jsonPath) : '';

            $normalized[$fieldKey] = $jsonPath !== '' ? $jsonPath : null;
        }

        return $normalized;
    }
}
