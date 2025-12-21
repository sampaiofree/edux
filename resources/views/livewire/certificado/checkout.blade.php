<div class="w-full max-w-[460px] bg-white rounded-xl shadow p-5">
    @if ($showSuccess)
        <div class="space-y-6">
            <div class="space-y-2 text-center">
                <h1 class="text-2xl font-bold text-black font-['Poppins']">Pagamento confirmado!</h1>
                <p class="text-base text-black font-['Inter']">
                    Seu certificado está em processamento e estará disponível em instantes.
                </p>
            </div>

            <div class="rounded-lg border border-gray-200 bg-gray-50 p-4 space-y-2 font-['Inter'] text-sm text-gray-700">
                <div class="flex justify-between">
                    <span>Curso</span>
                    <span class="font-semibold text-black">{{ $courseName !== '' ? $courseName : 'Curso não informado' }}</span>
                </div>
                <div class="flex justify-between">
                    <span>Nome no certificado</span>
                    <span class="font-semibold text-black">{{ $certificateName }}</span>
                </div>
                <div class="flex justify-between">
                    <span>Carga horária</span>
                    <span class="font-semibold text-black">{{ $workload }}h</span>
                </div>
            </div>

            <div class="space-y-3">
                <a href="{{ route('certificado.download') }}" class="edux-btn w-full flex items-center justify-center">
                    Baixar certificado
                </a>
                <button type="button" class="w-full h-[50px] rounded-xl border border-gray-300 font-bold text-black font-['Inter']">
                    Enviar novamente por WhatsApp
                </button>
                <button type="button" class="w-full h-[50px] rounded-xl border border-gray-300 font-bold text-black font-['Inter']">
                    Enviar novamente por e-mail
                </button>
            </div>
        </div>
    @elseif ($showPix)
        <div class="space-y-6">
            <div class="space-y-2 text-center">
                <h1 class="text-2xl font-bold text-black font-['Poppins']">Pagamento via PIX</h1>
                <p class="text-base text-black font-['Inter']">
                    Escaneie o QR Code ou copie o código PIX abaixo.
                </p>
            </div>

            <div class="flex justify-center">
                <div class="h-48 w-48 rounded-lg border border-dashed border-gray-300 flex items-center justify-center text-sm text-gray-500 font-['Inter']">
                    QR Code (mock)
                </div>
            </div>

            <div class="space-y-2">
                <label class="text-sm font-semibold text-black font-['Inter']">Código PIX copia e cola</label>
                <div class="rounded-lg border border-gray-200 bg-gray-50 p-3 text-xs text-gray-700 font-['Inter'] break-all">
                    00020101021226880014br.gov.bcb.pix2563pix.exemplo.com/qrcode1234567890
                </div>
                <button
                    type="button"
                    x-data
                    x-on:click="navigator.clipboard.writeText('00020101021226880014br.gov.bcb.pix2563pix.exemplo.com/qrcode1234567890')"
                    class="w-full h-[50px] rounded-xl border border-gray-300 font-bold text-black font-['Inter']"
                >
                    Copiar código
                </button>
            </div>

            <button type="button" wire:click="confirmPayment" class="edux-btn w-full flex items-center justify-center">
                Já paguei
            </button>
        </div>
    @else
        <div class="space-y-6">
            <div class="space-y-2 text-center">
                <p class="text-sm text-gray-500 font-['Inter']">Passo {{ $step }} de 5</p>
                <h1 class="text-2xl font-bold text-black font-['Poppins']">Comprar certificado</h1>
            </div>

            @if ($step === 1)
                <div class="space-y-4">
                    <div class="space-y-1 text-center">
                        <p class="text-sm text-gray-500 font-['Inter']">Curso selecionado</p>
                        <p class="text-lg font-semibold text-black font-['Inter']">
                            {{ $courseName !== '' ? $courseName : 'Curso não informado' }}
                        </p>
                        <button type="button" wire:click="openCourseModal" class="text-sm font-semibold text-blue-600 font-['Inter']">
                            Não é este o curso? Trocar curso
                        </button>
                    </div>

                    <div class="rounded-lg border border-gray-200 bg-gray-50 p-4 text-sm text-gray-700 font-['Inter']">
                        Declaro que concluí este curso e assisti todas as aulas.
                    </div>

                    <button type="button" wire:click="nextStep" class="edux-btn w-full flex items-center justify-center">
                        Confirmar conclusão
                    </button>
                </div>
            @endif

            @if ($step === 2)
                <div class="space-y-5">
                    <div class="space-y-2">
                        <label class="text-sm font-semibold text-black font-['Inter']">Nome no certificado</label>
                        <input
                            type="text"
                            wire:model.defer="certificateName"
                            class="w-full rounded-lg border border-gray-300 px-3 py-3 font-['Inter']"
                            placeholder="Digite seu nome completo"
                        />
                        @error('certificateName')
                            <p class="text-xs text-red-500 font-['Inter']">{{ $message }}</p>
                        @enderror
                        <p class="text-xs text-gray-500 font-['Inter']">
                            Confira o nome. Não será possível alterar depois do pagamento.
                        </p>
                    </div>

                    <div class="space-y-3">
                        <p class="text-sm font-semibold text-black font-['Inter']">Pré-visualização</p>
                        <div class="relative h-48 w-full rounded-lg border border-gray-200 bg-gray-50 p-4">
                            <div class="absolute top-4 left-4 text-xs text-gray-500 font-['Inter']">Prévia do certificado</div>
                            <div class="absolute inset-0 flex flex-col items-center justify-center text-center space-y-1">
                                <span class="text-base font-semibold text-black font-['Poppins']">
                                    {{ $certificateName !== '' ? $certificateName : 'Seu nome aqui' }}
                                </span>
                                <span class="text-sm text-gray-700 font-['Inter']">
                                    {{ $courseName !== '' ? $courseName : 'Nome do curso' }}
                                </span>
                                <span class="text-xs text-gray-500 font-['Inter']">{{ $this->formattedCompletionDate() }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            @if ($step === 3)
                <div class="space-y-4">
                    <div class="space-y-2">
                        <label class="text-sm font-semibold text-black font-['Inter']">Data de conclusão</label>
                        <input
                            type="date"
                            wire:model.defer="completionDate"
                            class="w-full rounded-lg border border-gray-300 px-3 py-3 font-['Inter']"
                        />
                        @error('completionDate')
                            <p class="text-xs text-red-500 font-['Inter']">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="space-y-2">
                        <label class="text-sm font-semibold text-black font-['Inter']">WhatsApp</label>
                        <div class="flex gap-2">
                            <select class="rounded-lg border border-gray-300 px-3 py-3 font-['Inter']">
                                <option value="+55">+55</option>
                            </select>
                            <input
                                type="text"
                                inputmode="numeric"
                                wire:model.defer="whatsapp"
                                class="flex-1 rounded-lg border border-gray-300 px-3 py-3 font-['Inter']"
                                placeholder="Somente números"
                            />
                        </div>
                        @error('whatsapp')
                            <p class="text-xs text-red-500 font-['Inter']">{{ $message }}</p>
                        @enderror
                        <p class="text-xs text-gray-500 font-['Inter']">
                            O certificado será enviado neste WhatsApp.
                        </p>
                    </div>

                    <div class="space-y-2">
                        <label class="text-sm font-semibold text-black font-['Inter']">E-mail</label>
                        <input
                            type="email"
                            wire:model.defer="email"
                            class="w-full rounded-lg border border-gray-300 px-3 py-3 font-['Inter']"
                            placeholder="seuemail@exemplo.com"
                        />
                        @error('email')
                            <p class="text-xs text-red-500 font-['Inter']">{{ $message }}</p>
                        @enderror
                        <p class="text-xs text-gray-500 font-['Inter']">
                            O certificado também será enviado por e-mail.
                        </p>
                    </div>
                </div>
            @endif

            @if ($step === 4)
                <div class="space-y-4">
                    <p class="text-sm text-gray-500 font-['Inter']">
                        Quanto maior a carga, maior o valor do certificado.
                    </p>

                    <div class="grid grid-cols-1 gap-3">
                        @foreach ($workloads as $option)
                            @php
                                $isSelected = $workload === $option['hours'];
                                $isHighlight = $option['highlight'] ?? false;
                            @endphp
                            <button
                                type="button"
                                wire:click="$set('workload', {{ $option['hours'] }})"
                                class="w-full rounded-xl border px-4 py-4 text-left font-['Inter'] {{ $isSelected ? 'border-yellow-400 bg-yellow-50' : 'border-gray-200 bg-white' }}"
                            >
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-lg font-semibold text-black">{{ $option['hours'] }}h</p>
                                        <p class="text-sm text-gray-500">{{ $option['price'] }}</p>
                                    </div>
                                    @if ($isHighlight)
                                        <span class="rounded-full bg-yellow-200 px-3 py-1 text-xs font-semibold text-black">Mais escolhida</span>
                                    @endif
                                </div>
                            </button>
                        @endforeach
                    </div>
                </div>
            @endif

            @if ($step === 5)
                <div class="space-y-4">
                    <div class="rounded-lg border border-gray-200 bg-gray-50 p-4 space-y-2 font-['Inter'] text-sm text-gray-700">
                        <div class="flex justify-between">
                            <span>Curso</span>
                            <span class="font-semibold text-black">{{ $courseName !== '' ? $courseName : 'Curso não informado' }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span>Nome no certificado</span>
                            <span class="font-semibold text-black">{{ $certificateName }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span>Carga horária</span>
                            <span class="font-semibold text-black">{{ $workload }}h</span>
                        </div>
                    </div>

                    <button type="button" wire:click="startPixPayment" class="edux-btn w-full flex items-center justify-center">
                        Pagar com PIX
                    </button>
                </div>
            @endif

            <div class="flex items-center justify-between pt-4">
                <button
                    type="button"
                    wire:click="previousStep"
                    class="text-sm font-semibold text-gray-600 font-['Inter'] disabled:opacity-40"
                    @if ($step === 1) disabled @endif
                >
                    Voltar
                </button>
                @if ($step > 1 && $step < 5)
                    <button type="button" wire:click="nextStep" class="text-sm font-semibold text-blue-600 font-['Inter']">
                        Próximo
                    </button>
                @endif
            </div>
        </div>

        @if ($showCourseModal)
            <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 px-4">
                <div class="w-full max-w-md rounded-xl bg-white p-5 space-y-4">
                    <div class="flex items-center justify-between">
                        <h2 class="text-lg font-semibold text-black font-['Poppins']">Selecione o curso</h2>
                        <button type="button" wire:click="closeCourseModal" class="text-sm font-semibold text-gray-500 font-['Inter']">
                            Fechar
                        </button>
                    </div>

                    <div class="max-h-64 overflow-y-auto space-y-2">
                        @forelse ($courses as $course)
                            <button
                                type="button"
                                wire:click="selectCourse({{ $course->id }})"
                                class="w-full rounded-lg border border-gray-200 px-4 py-3 text-left font-['Inter'] hover:border-blue-400"
                            >
                                {{ $course->title }}
                            </button>
                        @empty
                            <p class="text-sm text-gray-500 font-['Inter']">Nenhum curso disponível.</p>
                        @endforelse
                    </div>
                </div>
            </div>
        @endif
    @endif
</div>
