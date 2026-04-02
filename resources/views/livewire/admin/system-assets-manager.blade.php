<section class="rounded-card bg-white p-6 shadow-card space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <p class="text-sm uppercase tracking-wide text-edux-primary">
                {{ $isSuperAdminContext ? 'Configuração global do tenant' : 'Identidade visual' }}
            </p>
            <h2 class="text-2xl font-display text-edux-primary">
                {{ $isSuperAdminContext ? 'Editar escola e configurações' : 'Padrões do sistema' }}
            </h2>
            <p class="text-sm text-slate-600">
                {{ $isSuperAdminContext
                    ? 'Atualize os dados institucionais, responsável, e-mail e assets desta escola sem depender do domínio atual.'
                    : 'Envie arquivos base para manter todos os cursos com a mesma cara.' }}
            </p>
        </div>
    </div>

    <section class="rounded-2xl border border-edux-line/60 bg-slate-50 p-4 space-y-4">
        <div>
            <h3 class="text-lg font-display text-edux-primary">Dados da escola</h3>
            <p class="text-xs text-slate-500">
                Esses dados podem ser usados em rodapés e comunicações públicas da plataforma.
            </p>
        </div>

        <form wire:submit.prevent="saveSchoolIdentity" class="grid gap-3 md:grid-cols-2">
            <label class="space-y-1 text-sm font-semibold text-slate-600 md:col-span-2">
                <span>Domínio do sistema</span>
                <input
                    type="text"
                    wire:model.defer="domain"
                    placeholder="Ex.: cursos.dominio.com"
                    class="w-full rounded-xl border border-edux-line px-4 py-3 focus:border-edux-primary focus:ring-edux-primary/30"
                >
                @error('domain')
                    <p class="text-xs text-red-500">{{ $message }}</p>
                @enderror
                <p class="text-xs text-slate-500">Use um host completo iniciando com <code>cursos.</code>, sem protocolo, porta, caminho ou espaços.</p>
            </label>

            <label class="space-y-1 text-sm font-semibold text-slate-600">
                <span>Nome da escola</span>
                <input
                    type="text"
                    wire:model.defer="escola_nome"
                    placeholder="Ex.: Escola Profissional XYZ"
                    class="w-full rounded-xl border border-edux-line px-4 py-3 focus:border-edux-primary focus:ring-edux-primary/30"
                >
                @error('escola_nome')
                    <p class="text-xs text-red-500">{{ $message }}</p>
                @enderror
            </label>

            <label class="space-y-1 text-sm font-semibold text-slate-600">
                <span>CNPJ da escola</span>
                <input
                    type="text"
                    wire:model.defer="escola_cnpj"
                    placeholder="Ex.: 12.345.678/0001-90"
                    class="w-full rounded-xl border border-edux-line px-4 py-3 focus:border-edux-primary focus:ring-edux-primary/30"
                >
                @error('escola_cnpj')
                    <p class="text-xs text-red-500">{{ $message }}</p>
                @enderror
                <p class="text-xs text-slate-500">Opcional. Você pode salvar com ou sem máscara.</p>
            </label>

            @if ($isSuperAdminContext)
                <label class="space-y-1 text-sm font-semibold text-slate-600 md:col-span-2">
                    <span>Responsável da escola</span>
                    <select
                        wire:model.defer="owner_user_id"
                        class="w-full rounded-xl border border-edux-line px-4 py-3 focus:border-edux-primary focus:ring-edux-primary/30"
                    >
                        <option value="">Sem responsável</option>
                        @foreach ($ownerOptions as $ownerOption)
                            <option value="{{ $ownerOption->id }}">
                                {{ $ownerOption->name }} ({{ $ownerOption->email }})
                            </option>
                        @endforeach
                    </select>
                    @error('owner_user_id')
                        <p class="text-xs text-red-500">{{ $message }}</p>
                    @enderror
                    <p class="text-xs text-slate-500">Apenas administradores já vinculados a esta escola podem ser definidos como responsáveis.</p>
                </label>
            @endif

            <div class="md:col-span-2 flex items-center gap-3">
                <button
                    type="submit"
                    class="edux-btn h-fit"
                    wire:loading.attr="disabled"
                    wire:target="saveSchoolIdentity"
                >
                    Salvar dados da escola
                </button>
                @if (session()->has('status_school_identity'))
                    <p class="text-xs text-emerald-600" wire:loading.remove wire:target="saveSchoolIdentity">
                        {{ session('status_school_identity') }}
                    </p>
                @endif
            </div>
        </form>

    </section>

    <section class="rounded-2xl border border-edux-line/60 bg-slate-50 p-4 space-y-4">
        <div>
            <h3 class="text-lg font-display text-edux-primary">Configurações de e-mail</h3>
            <p class="text-xs text-slate-500">
                Cada escola pode definir seu próprio remetente e servidor SMTP. Se deixar o mailer vazio, o sistema usa a configuração padrão do servidor.
            </p>
        </div>

        <form wire:submit.prevent="saveMailSettings" class="grid gap-3 md:grid-cols-2">
            <label class="space-y-1 text-sm font-semibold text-slate-600">
                <span>Mailer</span>
                <select
                    wire:model.live="mail_mailer"
                    class="w-full rounded-xl border border-edux-line px-4 py-3 focus:border-edux-primary focus:ring-edux-primary/30"
                >
                    <option value="">Usar padrão do servidor</option>
                    <option value="smtp">SMTP</option>
                    <option value="log">Log</option>
                </select>
                @error('mail_mailer')
                    <p class="text-xs text-red-500">{{ $message }}</p>
                @enderror
            </label>

            <label class="space-y-1 text-sm font-semibold text-slate-600">
                <span>Esquema</span>
                <input
                    type="text"
                    wire:model.defer="mail_scheme"
                    placeholder="Ex.: tls"
                    class="w-full rounded-xl border border-edux-line px-4 py-3 focus:border-edux-primary focus:ring-edux-primary/30"
                >
                @error('mail_scheme')
                    <p class="text-xs text-red-500">{{ $message }}</p>
                @enderror
                <p class="text-xs text-slate-500">Opcional. Use <code>tls</code> para STARTTLS na porta 587 ou <code>ssl</code> para conexão segura direta na porta 465.</p>
            </label>

            <label class="space-y-1 text-sm font-semibold text-slate-600">
                <span>Remetente (e-mail)</span>
                <input
                    type="email"
                    wire:model.defer="mail_from_address"
                    placeholder="Ex.: contato@escola.com"
                    class="w-full rounded-xl border border-edux-line px-4 py-3 focus:border-edux-primary focus:ring-edux-primary/30"
                >
                @error('mail_from_address')
                    <p class="text-xs text-red-500">{{ $message }}</p>
                @enderror
            </label>

            <label class="space-y-1 text-sm font-semibold text-slate-600">
                <span>Nome do remetente</span>
                <input
                    type="text"
                    wire:model.defer="mail_from_name"
                    placeholder="Ex.: Escola Profissional XYZ"
                    class="w-full rounded-xl border border-edux-line px-4 py-3 focus:border-edux-primary focus:ring-edux-primary/30"
                >
                @error('mail_from_name')
                    <p class="text-xs text-red-500">{{ $message }}</p>
                @enderror
            </label>

            @if ($mail_mailer === 'smtp')
                <label class="space-y-1 text-sm font-semibold text-slate-600">
                    <span>Host SMTP</span>
                    <input
                        type="text"
                        wire:model.defer="mail_host"
                        placeholder="Ex.: smtp.seudominio.com"
                        class="w-full rounded-xl border border-edux-line px-4 py-3 focus:border-edux-primary focus:ring-edux-primary/30"
                    >
                    @error('mail_host')
                        <p class="text-xs text-red-500">{{ $message }}</p>
                    @enderror
                </label>

                <label class="space-y-1 text-sm font-semibold text-slate-600">
                    <span>Porta SMTP</span>
                    <input
                        type="number"
                        wire:model.defer="mail_port"
                        placeholder="Ex.: 587"
                        class="w-full rounded-xl border border-edux-line px-4 py-3 focus:border-edux-primary focus:ring-edux-primary/30"
                    >
                    @error('mail_port')
                        <p class="text-xs text-red-500">{{ $message }}</p>
                    @enderror
                </label>

                <label class="space-y-1 text-sm font-semibold text-slate-600">
                    <span>Usuário SMTP</span>
                    <input
                        type="text"
                        wire:model.defer="mail_username"
                        placeholder="Ex.: usuario-smtp"
                        class="w-full rounded-xl border border-edux-line px-4 py-3 focus:border-edux-primary focus:ring-edux-primary/30"
                    >
                    @error('mail_username')
                        <p class="text-xs text-red-500">{{ $message }}</p>
                    @enderror
                </label>

                <label class="space-y-1 text-sm font-semibold text-slate-600">
                    <span>Senha SMTP</span>
                    <input
                        type="password"
                        wire:model.defer="mail_password"
                        placeholder="{{ $mail_password_configured ? 'Deixe em branco para manter a senha atual' : 'Digite a senha SMTP' }}"
                        class="w-full rounded-xl border border-edux-line px-4 py-3 focus:border-edux-primary focus:ring-edux-primary/30"
                    >
                    @error('mail_password')
                        <p class="text-xs text-red-500">{{ $message }}</p>
                    @enderror
                    @if ($mail_password_configured)
                        <p class="text-xs text-slate-500">Uma senha já está salva. Deixe em branco para manter.</p>
                    @endif
                </label>
            @endif

            <div class="md:col-span-2 flex items-center gap-3">
                <button
                    type="submit"
                    class="edux-btn h-fit"
                    wire:loading.attr="disabled"
                    wire:target="saveMailSettings"
                >
                    Salvar e-mail
                </button>
                @if (session()->has('status_mail_settings'))
                    <p class="text-xs text-emerald-600" wire:loading.remove wire:target="saveMailSettings">
                        {{ session('status_mail_settings') }}
                    </p>
                @endif
            </div>
        </form>

        <form wire:submit.prevent="sendTestEmail" class="grid gap-3 border-t border-edux-line/60 pt-4 md:grid-cols-[1fr_auto] md:items-center">
            <label class="space-y-1 text-sm font-semibold text-slate-600">
                <span>E-mail para teste</span>
                <input
                    type="email"
                    wire:model.defer="test_email"
                    placeholder="Ex.: admin@escola.com"
                    class="w-full rounded-xl border border-edux-line px-4 py-3 focus:border-edux-primary focus:ring-edux-primary/30"
                >
                @error('test_email')
                    <p class="text-xs text-red-500">{{ $message }}</p>
                @enderror
                <p class="text-xs text-slate-500">Usa os valores atualmente preenchidos acima, mesmo antes de salvar.</p>
            </label>

            <div class="space-y-2">
                <button
                    type="submit"
                    class="edux-btn h-fit"
                    wire:loading.attr="disabled"
                    wire:target="sendTestEmail"
                >
                    Enviar e-mail de teste
                </button>

                @if (session()->has('status_mail_test'))
                    <p class="text-xs text-slate-600" wire:loading.remove wire:target="sendTestEmail">
                        {{ session('status_mail_test') }}
                    </p>
                @endif
            </div>
        </form>
    </section>

    <section class="rounded-2xl border border-edux-line/60 bg-slate-50 p-4 space-y-4">
        <div>
            <h3 class="text-lg font-display text-edux-primary">Notificações Push</h3>
            <p class="text-xs text-slate-500">
                Configure o OneSignal desta escola para enviar push no navegador e no app. O identificador de cada aluno segue o formato
                <code>tenant:{{ $settings->id }}:user:{id}</code>.
            </p>
        </div>

        <form wire:submit.prevent="saveOneSignalSettings" class="grid gap-3 md:grid-cols-2">
            <label class="space-y-1 text-sm font-semibold text-slate-600">
                <span>OneSignal App ID</span>
                <input
                    type="text"
                    wire:model.defer="onesignal_app_id"
                    placeholder="UUID do app da escola no OneSignal"
                    autocomplete="off"
                    autocapitalize="none"
                    spellcheck="false"
                    class="w-full rounded-xl border border-edux-line px-4 py-3 focus:border-edux-primary focus:ring-edux-primary/30"
                >
                @error('onesignal_app_id')
                    <p class="text-xs text-red-500">{{ $message }}</p>
                @enderror
            </label>

            <label class="space-y-1 text-sm font-semibold text-slate-600">
                <span>Chave REST API</span>
                <input
                    type="password"
                    wire:model.defer="onesignal_rest_api_key"
                    placeholder="{{ $onesignal_rest_api_key_configured ? 'Deixe em branco para manter a chave atual' : 'Cole a chave REST da escola' }}"
                    autocomplete="new-password"
                    autocapitalize="none"
                    spellcheck="false"
                    class="w-full rounded-xl border border-edux-line px-4 py-3 focus:border-edux-primary focus:ring-edux-primary/30"
                >
                @error('onesignal_rest_api_key')
                    <p class="text-xs text-red-500">{{ $message }}</p>
                @enderror
                @if ($onesignal_rest_api_key_configured)
                    <p class="text-xs text-slate-500">Uma chave REST já está salva. Deixe em branco para manter.</p>
                @endif
            </label>

            <div class="md:col-span-2 flex items-center gap-3">
                <button
                    type="submit"
                    class="edux-btn h-fit"
                    wire:loading.attr="disabled"
                    wire:target="saveOneSignalSettings"
                >
                    Salvar push
                </button>
                @if (session()->has('status_onesignal_settings'))
                    <p class="text-xs text-emerald-600" wire:loading.remove wire:target="saveOneSignalSettings">
                        {{ session('status_onesignal_settings') }}
                    </p>
                @endif
            </div>
        </form>

        <form wire:submit.prevent="sendTestPush" class="grid gap-3 border-t border-edux-line/60 pt-4 md:grid-cols-[1fr_auto] md:items-end">
            <label class="space-y-1 text-sm font-semibold text-slate-600">
                <span>Aluno para teste</span>
                <select
                    wire:model.defer="test_push_user_id"
                    class="w-full rounded-xl border border-edux-line px-4 py-3 focus:border-edux-primary focus:ring-edux-primary/30"
                >
                    <option value="">Selecione um aluno</option>
                    @foreach ($studentOptions as $studentOption)
                        <option value="{{ $studentOption->id }}">
                            {{ $studentOption->name }} ({{ $studentOption->email }})
                        </option>
                    @endforeach
                </select>
                @error('test_push_user_id')
                    <p class="text-xs text-red-500">{{ $message }}</p>
                @enderror
                <p class="text-xs text-slate-500">O teste usa as credenciais preenchidas acima, mesmo antes de salvar.</p>
            </label>

            <div class="space-y-2">
                <button
                    type="submit"
                    class="edux-btn h-fit"
                    wire:loading.attr="disabled"
                    wire:target="sendTestPush"
                >
                    Enviar push de teste
                </button>

                @if (session()->has('status_onesignal_test'))
                    <p class="text-xs text-slate-600" wire:loading.remove wire:target="sendTestPush">
                        {{ session('status_onesignal_test') }}
                    </p>
                @endif
            </div>
        </form>
    </section>

    <section class="rounded-2xl border border-edux-line/60 bg-slate-50 p-4 space-y-4">
        <div>
            <h3 class="text-lg font-display text-edux-primary">Meta Ads Pixel</h3>
            <p class="text-xs text-slate-500">
                ID numérico do pixel usado na LP pública de cursos em <code>/catalogo/{slug}</code>.
            </p>
        </div>

        <form wire:submit.prevent="saveMetaAdsPixel" class="grid gap-3 md:grid-cols-[1fr_auto] md:items-end">
            <label class="space-y-1 text-sm font-semibold text-slate-600">
                <span>Pixel ID</span>
                <input
                    type="text"
                    inputmode="numeric"
                    wire:model.defer="meta_ads_pixel"
                    placeholder="Ex.: 123456789012345"
                    class="w-full rounded-xl border border-edux-line px-4 py-3 focus:border-edux-primary focus:ring-edux-primary/30"
                >
                @error('meta_ads_pixel')
                    <p class="text-xs text-red-500">{{ $message }}</p>
                @enderror
                <p class="text-xs text-slate-500">Aceita apenas números. Se deixar vazio, o pixel não será carregado.</p>
            </label>

            <button
                type="submit"
                class="edux-btn h-fit"
                wire:loading.attr="disabled"
                wire:target="saveMetaAdsPixel"
            >
                Salvar Pixel
            </button>
        </form>
    </section>

    <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
        @foreach ($fields as $key => $data)
            @php
                $column = $data['column'];
                $preview = $settings->assetUrl($column);
                $accept = $key === 'carta_estagio'
                    ? '.webp,.png,.jpg,.jpeg,image/webp,image/png,image/jpeg'
                    : 'image/*';
            @endphp

            <article class="rounded-2xl border border-edux-line/60 p-4 space-y-4" wire:key="system-asset-{{ $key }}">
                <div class="h-32 w-full overflow-hidden rounded-xl bg-edux-background flex items-center justify-center">
                    @if ($preview)
                        <img src="{{ $preview }}" alt="{{ $data['label'] }}" class="h-full w-full object-cover">
                    @else
                        <span class="text-xs text-slate-400">Sem imagem</span>
                    @endif
                </div>
                <div class="space-y-1">
                    <h3 class="text-lg font-display text-edux-primary">{{ $data['label'] }}</h3>
                    <p class="text-xs text-slate-500">{{ $data['hint'] }}</p>
                </div>
                <form wire:submit.prevent="save('{{ $key }}')" class="space-y-2">
                    <label class="block text-sm font-semibold text-slate-600">
                        <span class="sr-only">Selecionar arquivo</span>
                        <input type="file" wire:model="uploads.{{ $key }}" accept="{{ $accept }}"
                            class="block w-full rounded-xl border border-dashed border-edux-line px-4 py-2 text-sm file:mr-4 file:rounded-full file:border-0 file:bg-edux-primary file:px-4 file:py-2 file:text-sm file:font-semibold file:text-white focus:border-edux-primary focus:ring-edux-primary/30">
                    </label>
                    @error("uploads.$key")
                        <p class="text-xs text-red-500">{{ $message }}</p>
                    @enderror
                    @if (session()->has("status_{$key}"))
                        <p class="text-xs text-emerald-600" wire:loading.remove wire:target="save('{{ $key }}'), uploads.{{ $key }}">
                            {{ session("status_{$key}") }}
                        </p>
                    @endif
                    <div class="flex items-center gap-2">
                        <button type="submit" class="edux-btn text-sm"
                            wire:loading.attr="disabled"
                            wire:target="save('{{ $key }}'), uploads.{{ $key }}">
                            Salvar
                        </button>
                        <span class="text-xs text-slate-500" wire:loading wire:target="save('{{ $key }}'), uploads.{{ $key }}">
                            Enviando...
                        </span>
                    </div>
                </form>
            </article>
        @endforeach
    </div>
</section>
