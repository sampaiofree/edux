@props(['status' => null, 'errors' => []])

<div
    x-data="{
        toasts: [],
        counter: 0,
        init(initial = null, errs = []) {
            if (initial) {
                this.add({ message: initial, type: 'success' });
            }
            if (errs && errs.length) {
                errs.forEach(msg => this.add({ message: msg, type: 'error', timeout: 6000 }));
            }
        },
        add({ message, type = 'info', timeout = 4000 }) {
            if (!message) return;
            const id = ++this.counter;
            this.toasts.push({ id, message, type });
            setTimeout(() => this.remove(id), timeout);
        },
        remove(id) {
            this.toasts = this.toasts.filter(t => t.id !== id);
        },
        show(detail) {
            this.add(detail || {});
        },
        tone(type) {
            if (type === 'success') return { bg: 'bg-emerald-100', text: 'text-emerald-900', accent: 'bg-emerald-500' };
            if (type === 'error') return { bg: 'bg-red-100', text: 'text-red-900', accent: 'bg-red-500' };
            if (type === 'warning') return { bg: 'bg-amber-100', text: 'text-amber-900', accent: 'bg-amber-500' };
            return { bg: 'bg-slate-100', text: 'text-slate-900', accent: 'bg-slate-500' };
        },
    }"
    x-init="init({{ json_encode($status) }}, {{ json_encode($errors) }})"
    @notify.window="show($event.detail)"
    class="pointer-events-none fixed right-4 top-4 z-50 flex w-full max-w-sm flex-col gap-3 sm:right-6 sm:top-6 sm:max-w-md"
>
    <template x-for="toast in toasts" :key="toast.id">
        <div
            class="pointer-events-auto flex items-start gap-3 rounded-2xl p-4 shadow-lg ring-1 ring-black/5"
            :class="tone(toast.type).bg"
        >
            <span class="mt-1 h-2 w-2 rounded-full" :class="tone(toast.type).accent"></span>
            <div class="flex-1 text-sm font-semibold leading-5" :class="tone(toast.type).text" x-text="toast.message"></div>
            <button
                type="button"
                class="text-xs font-semibold text-slate-500 hover:text-slate-700"
                @click="remove(toast.id)"
            >
                Fechar
            </button>
        </div>
    </template>
</div>
