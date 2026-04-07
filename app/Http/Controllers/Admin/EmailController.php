<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Mail\AdminAudienceMessage;
use App\Models\Course;
use App\Models\SystemSetting;
use App\Models\User;
use App\Support\Mail\TenantMailManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class EmailController extends Controller
{
    public function __construct(
        private readonly TenantMailManager $tenantMailManager,
    ) {
        $this->middleware('role:admin');
    }

    public function create(): View
    {
        return view('admin.email', [
            'courses' => Course::query()
                ->orderBy('title')
                ->get(['id', 'title']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validated($request);
        $systemSetting = SystemSetting::current();
        $course = null;

        $recipientsQuery = User::query()
            ->where('role', UserRole::STUDENT->value)
            ->whereNotNull('email')
            ->where('email', '<>', '');

        if ($validated['audience'] === 'course') {
            $course = Course::query()->find($validated['course_id']);

            if (! $course) {
                return back()
                    ->withErrors([
                        'course_id' => 'Selecione um curso válido do tenant atual.',
                    ])
                    ->withInput();
            }

            $recipientsQuery->whereHas('enrollments', function ($query) use ($course): void {
                $query->where('course_id', $course->id);
            });
        }

        $recipients = $recipientsQuery
            ->orderBy('name')
            ->get();

        if ($recipients->isEmpty()) {
            return back()
                ->withErrors([
                    'audience' => 'Nenhum aluno com e-mail válido foi encontrado para o filtro selecionado.',
                ])
                ->withInput();
        }

        foreach ($recipients as $recipient) {
            $this->tenantMailManager->send(
                $systemSetting,
                $recipient,
                new AdminAudienceMessage(
                    systemSetting: $systemSetting,
                    user: $recipient,
                    subjectLine: $validated['subject'],
                    bodyText: $validated['body'],
                    buttonText: $validated['button_text'] ?? null,
                    buttonUrl: $validated['button_url'] ?? null,
                )
            );
        }

        $segmentLabel = $course
            ? 'curso "'.$course->title.'"'
            : 'todos os alunos';

        return redirect()
            ->route('admin.email.create')
            ->with('status', 'E-mail enviado para '.$recipients->count().' aluno(s) do segmento '.$segmentLabel.'.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        $validated = $request->validate([
            'subject' => ['required', 'string', 'max:160'],
            'body' => ['required', 'string', 'max:10000'],
            'audience' => ['required', Rule::in(['all', 'course'])],
            'course_id' => [
                'nullable',
                'integer',
                Rule::requiredIf(fn (): bool => $request->input('audience') === 'course'),
            ],
            'button_text' => ['nullable', 'string', 'max:80', 'required_with:button_url'],
            'button_url' => ['nullable', 'string', 'max:2048', 'url', 'required_with:button_text'],
        ]);

        $validated['subject'] = trim((string) $validated['subject']);
        $validated['body'] = trim((string) $validated['body']);
        $validated['button_text'] = isset($validated['button_text'])
            ? trim((string) $validated['button_text'])
            : null;
        $validated['button_url'] = isset($validated['button_url'])
            ? trim((string) $validated['button_url'])
            : null;

        if ($validated['button_text'] === '') {
            $validated['button_text'] = null;
        }

        if ($validated['button_url'] === '') {
            $validated['button_url'] = null;
        }

        if (($validated['audience'] ?? 'all') !== 'course') {
            $validated['course_id'] = null;
        }

        return $validated;
    }
}
