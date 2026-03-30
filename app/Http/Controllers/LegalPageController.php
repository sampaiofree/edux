<?php

namespace App\Http\Controllers;

use App\Models\SystemSetting;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LegalPageController extends Controller
{
    public function privacy(Request $request): View
    {
        $settings = SystemSetting::current()->loadMissing('owner');

        return view('legal.privacy', [
            'settings' => $settings,
            'schoolName' => $this->schoolName($settings),
            'contactEmail' => $this->contactEmail($settings),
            'contactName' => $this->contactName($settings),
            'displayDomain' => $this->displayDomain($settings, $request),
        ]);
    }

    public function support(Request $request): View
    {
        $settings = SystemSetting::current()->loadMissing('owner');

        return view('legal.support', [
            'settings' => $settings,
            'schoolName' => $this->schoolName($settings),
            'contactEmail' => $this->contactEmail($settings),
            'contactName' => $this->contactName($settings),
            'displayDomain' => $this->displayDomain($settings, $request),
            'supportNumbers' => $settings->supportWhatsappNumbers()
                ->active()
                ->orderBy('position')
                ->orderBy('id')
                ->get(),
        ]);
    }

    private function schoolName(SystemSetting $settings): string
    {
        return trim((string) ($settings->escola_nome ?? '')) ?: 'Escola';
    }

    private function contactEmail(SystemSetting $settings): ?string
    {
        $value = trim((string) ($settings->mail_from_address ?? ''));

        return $value !== '' ? $value : null;
    }

    private function contactName(SystemSetting $settings): ?string
    {
        $value = trim((string) ($settings->mail_from_name ?? ''));

        if ($value !== '') {
            return $value;
        }

        $schoolName = trim((string) ($settings->escola_nome ?? ''));

        return $schoolName !== '' ? $schoolName : null;
    }

    private function displayDomain(SystemSetting $settings, Request $request): string
    {
        return trim((string) ($settings->domain ?? '')) ?: $request->getHost();
    }
}
