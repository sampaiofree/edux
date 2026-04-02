import './bootstrap';
import collapse from '@alpinejs/collapse';
import intlTelInput from 'intl-tel-input';
import utilsScriptUrl from 'intl-tel-input/build/js/utils.js?url';
import 'intl-tel-input/build/css/intlTelInput.css';
import { initCertificateShare } from './certificate-share';

document.addEventListener('alpine:init', () => {
    window.Alpine.plugin(collapse);
});

const intlPhoneInstances = new Map();

const destroyOrphanIntlInputs = () => {
    intlPhoneInstances.forEach((iti, input) => {
        if (! document.body.contains(input)) {
            iti.destroy();
            intlPhoneInstances.delete(input);
        }
    });
};

const setupIntlPhoneInputs = () => {
    destroyOrphanIntlInputs();

    document.querySelectorAll('.js-intl-phone').forEach((input) => {
        if (intlPhoneInstances.has(input)) {
            return;
        }

        const targetId = input.dataset.target;
        const hiddenInput = targetId ? document.getElementById(targetId) : null;

        const iti = intlTelInput(input, {
            initialCountry: 'br',
            separateDialCode: true,
            preferredCountries: ['br'],
            utilsScript: utilsScriptUrl,
        });

        const sync = () => {
            if (! hiddenInput) {
                return;
            }

            hiddenInput.value = iti.getNumber();
            hiddenInput.dispatchEvent(new Event('input'));
        };

        input.addEventListener('countrychange', sync);
        input.addEventListener('input', sync);
        input.addEventListener('change', sync);
        input.addEventListener('blur', sync);
        input.addEventListener('keyup', () => {
            if (input.value.trim() === '' && hiddenInput) {
                hiddenInput.value = '';
                hiddenInput.dispatchEvent(new Event('input'));
            }
        });

        if (hiddenInput && hiddenInput.value) {
            iti.setNumber(hiddenInput.value);
        }

        intlPhoneInstances.set(input, iti);
    });
};

const HOME_COURSE_VACANCY_STORAGE_KEY = 'edux:home:vacancies:v2';
const HOME_COURSE_VACANCY_TICK_MS = 30000;
const HOME_COURSE_VACANCY_CLOSED_LABEL = 'Inscrições encerradas';
let homeCourseVacancyIntervalId = null;

const toInt = (value) => {
    const parsed = Number.parseInt(String(value ?? ''), 10);

    return Number.isFinite(parsed) ? parsed : null;
};

const randomInt = (min, max) => Math.floor(Math.random() * ((max - min) + 1)) + min;

const isValidVacancyEntry = (entry) => {
    if (!entry || typeof entry !== 'object') {
        return false;
    }

    const initial = toInt(entry.initial);
    const createdAt = toInt(entry.created_at_ms);
    const dropEveryTicks = toInt(entry.drop_every_ticks);

    if (initial === null || createdAt === null || dropEveryTicks === null) {
        return false;
    }

    return initial >= 1 && initial <= 20 && createdAt > 0 && dropEveryTicks >= 1 && dropEveryTicks <= 3;
};

const readHomeCourseVacancies = () => {
    try {
        const raw = window.localStorage.getItem(HOME_COURSE_VACANCY_STORAGE_KEY);

        if (!raw) {
            return {};
        }

        const parsed = JSON.parse(raw);
        if (!parsed || typeof parsed !== 'object' || Array.isArray(parsed)) {
            return {};
        }

        return parsed;
    } catch (_) {
        return {};
    }
};

const persistHomeCourseVacancies = (state) => {
    try {
        window.localStorage.setItem(HOME_COURSE_VACANCY_STORAGE_KEY, JSON.stringify(state));
    } catch (_) {
        // Ignore storage failures; UI still works for the current session.
    }
};

const normalizeCityScope = (value) => {
    const normalized = String(value ?? '')
        .replace(/[-_]+/g, ' ')
        .replace(/\s+/g, ' ')
        .trim()
        .toLowerCase();

    return normalized;
};

const cityScopeFromUrl = (url) => {
    try {
        const parsed = new URL(String(url || ''), window.location.href);

        return normalizeCityScope(parsed.searchParams.get('cidade') || '');
    } catch (_) {
        return '';
    }
};

const currentCityScope = () => {
    try {
        return normalizeCityScope(new URLSearchParams(window.location.search).get('cidade') || '');
    } catch (_) {
        return '';
    }
};

const vacancyStateKey = (courseSlug, cityScope) => (
    `${courseSlug}@@${cityScope !== '' ? cityScope : '__global__'}`
);

const ensureVacancyEntry = (state, stateKey) => {
    const current = state[stateKey];
    if (isValidVacancyEntry(current)) {
        return [current, false];
    }

    const entry = {
        initial: randomInt(1, 20),
        created_at_ms: Date.now(),
        drop_every_ticks: randomInt(1, 3),
    };

    state[stateKey] = entry;

    return [entry, true];
};

const vacancyRemaining = (entry, nowMs) => {
    const elapsedTicks = Math.max(0, Math.floor((nowMs - entry.created_at_ms) / HOME_COURSE_VACANCY_TICK_MS));
    const decrements = Math.floor(elapsedTicks / entry.drop_every_ticks);

    return Math.max(entry.initial - decrements, 0);
};

const vacancyLabel = (remaining) => (
    remaining === 1 ? '1 vaga restante' : `${remaining} vagas restantes`
);

const pulseVacancyBadge = (badge) => {
    badge.classList.remove('is-updated');
    void badge.offsetWidth;
    badge.classList.add('is-updated');

    window.setTimeout(() => {
        badge.classList.remove('is-updated');
    }, 900);
};

const applyHomeCourseCardState = (card, remaining, shouldPulse) => {
    const badge = card.querySelector('[data-vacancy-badge]');
    const cta = card.querySelector('[data-course-cta]');
    const link = card.querySelector('[data-course-link]');

    if (!badge || !cta || !link) {
        return;
    }

    const courseUrl = (card.dataset.courseUrl || '').trim();
    const waitlistUrl = (card.dataset.waitlistUrl || '').trim();

    if (remaining > 0) {
        badge.textContent = vacancyLabel(remaining);
        badge.classList.remove('is-closed');

        cta.textContent = 'Saiba mais';
        cta.classList.remove('is-waitlist', 'is-disabled');

        link.classList.remove('is-disabled');
        link.removeAttribute('aria-disabled');
        link.removeAttribute('tabindex');
        link.setAttribute('href', courseUrl || '#');

        if (shouldPulse) {
            pulseVacancyBadge(badge);
        }

        return;
    }

    badge.textContent = HOME_COURSE_VACANCY_CLOSED_LABEL;
    badge.classList.add('is-closed');

    if (waitlistUrl !== '') {
        cta.textContent = 'Entrar na lista de espera';
        cta.classList.add('is-waitlist');
        cta.classList.remove('is-disabled');

        link.classList.remove('is-disabled');
        link.removeAttribute('aria-disabled');
        link.removeAttribute('tabindex');
        link.setAttribute('href', waitlistUrl);

        return;
    }

    cta.textContent = 'Lista de espera indisponível';
    cta.classList.remove('is-waitlist');
    cta.classList.add('is-disabled');

    link.classList.add('is-disabled');
    link.setAttribute('aria-disabled', 'true');
    link.setAttribute('tabindex', '-1');
    link.removeAttribute('href');
};

const refreshHomeCourseCardVacancies = (cards, storedVacancies, nowMs) => {
    let createdAnyEntry = false;
    cards.forEach((card) => {
        const courseSlug = (card.dataset.courseSlug || '').trim();
        if (courseSlug === '') {
            return;
        }

        const cityScope = normalizeCityScope(
            card.dataset.cityScope || cityScopeFromUrl(card.dataset.courseUrl || '') || currentCityScope()
        );
        const stateKey = vacancyStateKey(courseSlug, cityScope);
        const [entry, created] = ensureVacancyEntry(storedVacancies, stateKey);
        if (created) {
            createdAnyEntry = true;
        }

        const previousRemaining = toInt(card.dataset.vacanciesCurrent);
        const remaining = vacancyRemaining(entry, nowMs);
        const shouldPulse = previousRemaining !== null && remaining < previousRemaining;

        applyHomeCourseCardState(card, remaining, shouldPulse);
        card.dataset.vacanciesCurrent = String(remaining);
    });

    return createdAnyEntry;
};

const applyLpCheckoutStateAsWaitlist = (link, waitlistUrl) => {
    link.textContent = 'Entrar na lista de espera';
    link.dataset.ctaType = 'whatsapp';
    link.classList.remove('lp-vacancy-cta-disabled');
    link.classList.add('lp-vacancy-cta-waitlist');
    link.removeAttribute('aria-disabled');
    link.removeAttribute('tabindex');
    link.setAttribute('href', waitlistUrl);
    link.setAttribute('target', '_blank');
    link.setAttribute('rel', 'noopener');
};

const applyLpCheckoutStateAsDisabled = (link) => {
    link.textContent = 'Lista de espera indisponível';
    link.dataset.ctaType = 'checkout';
    link.classList.remove('lp-vacancy-cta-waitlist');
    link.classList.add('lp-vacancy-cta-disabled');
    link.setAttribute('aria-disabled', 'true');
    link.setAttribute('tabindex', '-1');
    link.removeAttribute('href');
    link.removeAttribute('target');
    link.removeAttribute('rel');
};

const applyHomeLpVacancyState = (lpRoot, remaining, shouldPulse) => {
    const badge = document.querySelector('[data-lp-vacancy-badge]');
    const cityFixedTopLabel = document.querySelector('[data-lp-city-fixed-top-label]');
    const checkoutSection = document.querySelector('[data-lp-checkout-section]');
    const checkoutClosedBanner = document.querySelector('[data-lp-checkout-closed-banner]');
    const checkoutLinks = document.querySelectorAll('a[data-checkout-link]');
    const waitlistUrl = (lpRoot.dataset.waitlistUrl || '').trim();
    const cityName = (lpRoot.dataset.cityName || '').trim();

    if (cityFixedTopLabel && cityName !== '') {
        cityFixedTopLabel.textContent = remaining > 0
            ? `📍 ${vacancyLabel(remaining)} para ${cityName}`
            : `📍 ${HOME_COURSE_VACANCY_CLOSED_LABEL} para ${cityName}`;
        cityFixedTopLabel.classList.toggle('is-closed', remaining === 0);

        if (shouldPulse && remaining > 0) {
            pulseVacancyBadge(cityFixedTopLabel);
        }
    }

    if (badge) {
        badge.textContent = remaining > 0 ? vacancyLabel(remaining) : HOME_COURSE_VACANCY_CLOSED_LABEL;
        badge.classList.toggle('is-closed', remaining === 0);

        if (shouldPulse && remaining > 0) {
            pulseVacancyBadge(badge);
        }
    }

    if (remaining > 0) {
        checkoutSection?.classList.remove('is-closed');
        checkoutClosedBanner?.classList.add('hidden');

        return;
    }

    checkoutSection?.classList.add('is-closed');
    checkoutClosedBanner?.classList.remove('hidden');

    checkoutLinks.forEach((link) => {
        if (waitlistUrl !== '') {
            applyLpCheckoutStateAsWaitlist(link, waitlistUrl);

            return;
        }

        applyLpCheckoutStateAsDisabled(link);
    });
};

const refreshHomeLpVacancy = (lpRoot, storedVacancies, nowMs) => {
    const courseSlug = (lpRoot.dataset.courseSlug || '').trim();
    if (courseSlug === '') {
        return false;
    }

    const cityScope = normalizeCityScope(lpRoot.dataset.cityScope || currentCityScope());
    const stateKey = vacancyStateKey(courseSlug, cityScope);
    const [entry, created] = ensureVacancyEntry(storedVacancies, stateKey);
    const previousRemaining = toInt(lpRoot.dataset.vacanciesCurrent);
    const remaining = vacancyRemaining(entry, nowMs);
    const shouldPulse = previousRemaining !== null && remaining < previousRemaining;

    applyHomeLpVacancyState(lpRoot, remaining, shouldPulse);
    lpRoot.dataset.vacanciesCurrent = String(remaining);

    return created;
};

const refreshHomeCourseVacancies = () => {
    const cards = document.querySelectorAll('[data-home-course-card="1"]');
    const lpRoot = document.querySelector('[data-lp-vacancy="1"]');
    if (cards.length === 0 && !lpRoot) {
        return;
    }

    const storedVacancies = readHomeCourseVacancies();
    const nowMs = Date.now();
    let shouldPersist = false;

    if (cards.length > 0) {
        shouldPersist = refreshHomeCourseCardVacancies(cards, storedVacancies, nowMs) || shouldPersist;
    }

    if (lpRoot) {
        shouldPersist = refreshHomeLpVacancy(lpRoot, storedVacancies, nowMs) || shouldPersist;
    }

    if (shouldPersist) {
        persistHomeCourseVacancies(storedVacancies);
    }
};

const setupHomeCourseVacancies = () => {
    refreshHomeCourseVacancies();

    if (homeCourseVacancyIntervalId !== null) {
        return;
    }

    homeCourseVacancyIntervalId = window.setInterval(() => {
        refreshHomeCourseVacancies();
    }, HOME_COURSE_VACANCY_TICK_MS);
};

const STUDENT_NAVIGATION_DELAY_MS = 180;
const STUDENT_ACTION_OVERLAY_DEFAULT_LABEL = 'Preparando PDF...';

let studentNavigationDelayId = null;
let studentNavigationVisible = false;
let studentActionHideTimeoutId = null;

const hasStudentShell = () => document.body?.dataset.studentShell === '1';

const setStudentActionOverlayLabel = (label) => {
    const overlayLabel = document.querySelector('[data-student-action-overlay-label]');

    if (!overlayLabel) {
        return;
    }

    overlayLabel.textContent = String(label || STUDENT_ACTION_OVERLAY_DEFAULT_LABEL);
};

const showStudentNavigationOverlay = () => {
    studentNavigationDelayId = null;

    if (studentNavigationVisible) {
        return;
    }

    studentNavigationVisible = true;
    document.documentElement.dataset.studentNavigating = '1';
};

const stopStudentNavigationOverlay = () => {
    if (studentNavigationDelayId !== null) {
        window.clearTimeout(studentNavigationDelayId);
        studentNavigationDelayId = null;
    }

    if (! studentNavigationVisible) {
        delete document.documentElement.dataset.studentNavigating;

        return;
    }

    studentNavigationVisible = false;
    delete document.documentElement.dataset.studentNavigating;
};

const clearStudentActionOverlayTimeout = () => {
    if (studentActionHideTimeoutId === null) {
        return;
    }

    window.clearTimeout(studentActionHideTimeoutId);
    studentActionHideTimeoutId = null;
};

const showStudentActionOverlay = (label = STUDENT_ACTION_OVERLAY_DEFAULT_LABEL) => {
    if (! hasStudentShell()) {
        return;
    }

    clearStudentActionOverlayTimeout();
    setStudentActionOverlayLabel(label);
    document.documentElement.dataset.studentBusy = '1';
};

const hideStudentActionOverlay = () => {
    clearStudentActionOverlayTimeout();
    delete document.documentElement.dataset.studentBusy;
    setStudentActionOverlayLabel(STUDENT_ACTION_OVERLAY_DEFAULT_LABEL);
};

const queueStudentActionOverlayHide = (delayMs = 3200) => {
    clearStudentActionOverlayTimeout();
    studentActionHideTimeoutId = window.setTimeout(() => {
        hideStudentActionOverlay();
    }, delayMs);
};

const queueStudentNavigationOverlay = () => {
    if (! hasStudentShell()) {
        stopStudentNavigationOverlay();

        return;
    }

    if (studentNavigationDelayId !== null || studentNavigationVisible) {
        return;
    }

    studentNavigationDelayId = window.setTimeout(() => {
        showStudentNavigationOverlay();
    }, STUDENT_NAVIGATION_DELAY_MS);
};

let livewireHooksRegistered = false;

const registerLivewireHooks = () => {
    if (livewireHooksRegistered || !window.Livewire?.hook) {
        return;
    }

    livewireHooksRegistered = true;

    window.Livewire.hook('commit', ({ succeed }) => {
        succeed(() => {
            setupIntlPhoneInputs();
            setupHomeCourseVacancies();
            initCertificateShare();
        });
    });
};

document.addEventListener('livewire:init', () => {
    registerLivewireHooks();
});

document.addEventListener('livewire:initialized', () => {
    setupIntlPhoneInputs();
    setupHomeCourseVacancies();
    initCertificateShare();
});

document.addEventListener('livewire:navigated', () => {
    stopStudentNavigationOverlay();
    hideStudentActionOverlay();
    setupIntlPhoneInputs();
    setupHomeCourseVacancies();
    initCertificateShare();
});

document.addEventListener('livewire:navigate', () => {
    queueStudentNavigationOverlay();
});

document.addEventListener('DOMContentLoaded', () => {
    stopStudentNavigationOverlay();
    hideStudentActionOverlay();

    if (!window.Livewire) {
        setupIntlPhoneInputs();
    }
    setupHomeCourseVacancies();
    initCertificateShare();
});

document.addEventListener('edux:student-busy:start', (event) => {
    const label = event instanceof CustomEvent ? event.detail?.label : null;

    showStudentActionOverlay(label || STUDENT_ACTION_OVERLAY_DEFAULT_LABEL);
});

document.addEventListener('edux:student-busy:stop', () => {
    hideStudentActionOverlay();
});

window.addEventListener('pageshow', () => {
    hideStudentActionOverlay();
});

window.addEventListener('focus', () => {
    if (document.documentElement.dataset.studentBusy === '1') {
        queueStudentActionOverlayHide(250);
    }
});

window.EduxStudentBusy = {
    show: showStudentActionOverlay,
    hide: hideStudentActionOverlay,
    queueHide: queueStudentActionOverlayHide,
};
