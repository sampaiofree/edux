@php
    $firstPartyTrackingEnabled = (bool) config('tracking.enabled', true);
    $firstPartyTrackingEndpoint = route('api.tracking.events');
    $firstPartyTrackingBatchMax = max(1, (int) config('tracking.batch_max', 40));
@endphp

<script>
    (() => {
        if (typeof window.eduxFirstPartyTrack === 'function') {
            return;
        }

        const enabled = @js($firstPartyTrackingEnabled);
        const endpoint = @js($firstPartyTrackingEndpoint);
        const batchMax = @js($firstPartyTrackingBatchMax);

        const noop = () => {};
        if (!enabled || !endpoint) {
            window.eduxFirstPartyTrack = noop;
            window.eduxFirstPartyFlush = noop;
            return;
        }

        const queue = [];
        let flushTimer = null;
        let flushInFlight = false;
        const visitorCookieName = @js((string) config('tracking.visitor_cookie', 'edux_vid'));
        const sessionCookieName = @js((string) config('tracking.session_cookie', 'edux_sid'));

        const nowMs = () => Date.now();

        const limitString = (value, max = 255) => {
            if (value === null || value === undefined) return null;
            const text = String(value).trim();
            if (!text) return null;
            return text.slice(0, max);
        };

        const positiveInt = (value) => {
            const num = Number(value);
            return Number.isFinite(num) && num > 0 ? Math.trunc(num) : null;
        };

        const positiveValue = (value) => {
            const num = Number(value);
            return Number.isFinite(num) && num > 0 ? Number(num.toFixed(2)) : null;
        };

        const isPlainObject = (value) => Object.prototype.toString.call(value) === '[object Object]';

        const sanitizeAny = (value, depth = 0) => {
            if (depth >= 4) return null;
            if (value === null || value === undefined) return null;
            if (typeof value === 'string') return value.slice(0, 500);
            if (typeof value === 'number' || typeof value === 'boolean') return value;
            if (Array.isArray(value)) {
                return value.slice(0, 50).map((item) => sanitizeAny(item, depth + 1));
            }
            if (isPlainObject(value)) {
                const out = {};
                let count = 0;
                for (const [key, item] of Object.entries(value)) {
                    if (count >= 100) break;
                    const safeKey = limitString(key, 80);
                    if (!safeKey) continue;
                    out[safeKey] = sanitizeAny(item, depth + 1);
                    count += 1;
                }
                return out;
            }

            return limitString(value, 500);
        };

        const sanitizePayload = (payload) => {
            if (!isPlainObject(payload)) return {};
            return sanitizeAny(payload, 0) || {};
        };

        const makeId = () => {
            try {
                if (window.crypto && typeof window.crypto.randomUUID === 'function') {
                    return window.crypto.randomUUID();
                }
            } catch (_) {}

            return `evt_${nowMs()}_${Math.random().toString(36).slice(2, 10)}`;
        };

        const inferCategory = (eventName) => {
            const value = String(eventName || '').toLowerCase();
            if (value.includes('checkout')) return 'checkout';
            if (value.includes('lead')) return 'lead';
            if (value.includes('click')) return 'cta';
            if (value.includes('view')) return 'view';
            return 'event';
        };

        const pickString = (payload, keys, max = 255) => {
            for (const key of keys) {
                const value = limitString(payload?.[key], max);
                if (value) return value;
            }
            return null;
        };

        const pickInt = (payload, keys) => {
            for (const key of keys) {
                const value = positiveInt(payload?.[key]);
                if (value) return value;
            }
            return null;
        };

        const pickValue = (payload, keys) => {
            for (const key of keys) {
                const value = positiveValue(payload?.[key]);
                if (value) return value;
            }
            return null;
        };

        const buildEvent = (eventName, payload = {}, options = {}) => {
            const properties = sanitizePayload(payload);
            const pageType = limitString(options.pageType || properties.page_type, 120);
            const value = pickValue(properties, ['value', 'course_price', 'checkout_price']);
            const currency = limitString(options.currency || properties.currency, 12) || (value ? 'BRL' : null);

            return {
                event_uuid: limitString(options.eventUuid || makeId(), 64),
                event_name: limitString(eventName, 120),
                event_source: limitString(options.source || 'internal', 64),
                event_category: limitString(options.category || inferCategory(eventName), 64),
                occurred_at_ms: nowMs(),
                page_url: limitString(window.location.href, 2000),
                page_path: limitString(window.location.pathname, 255),
                page_type: pageType,
                referrer: limitString(document.referrer || '', 2000),
                course_id: pickInt(properties, ['course_id']),
                checkout_id: pickInt(properties, ['checkout_id']),
                course_slug: pickString(properties, ['course_slug', 'edux_course_slug']),
                city_slug: pickString(properties, ['city_slug']),
                city_name: pickString(properties, ['city_name']),
                cta_source: pickString(properties, ['cta_source', 'checkout_source']),
                value,
                currency,
                properties,
            };
        };

        const scheduleFlush = () => {
            if (flushTimer !== null) return;
            flushTimer = window.setTimeout(() => {
                flushTimer = null;
                flush(false);
            }, 700);
        };

        const readCookie = (name) => {
            if (!name) return null;
            const escaped = name.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
            const match = document.cookie.match(new RegExp(`(?:^|; )${escaped}=([^;]*)`));
            return match ? decodeURIComponent(match[1]) : null;
        };

        const sendBatch = (batch, preferBeacon = false) => {
            if (!batch.length) return Promise.resolve(true);

            const body = JSON.stringify({ events: batch });
            if (preferBeacon && typeof navigator.sendBeacon === 'function') {
                try {
                    const ok = navigator.sendBeacon(endpoint, new Blob([body], { type: 'application/json' }));
                    if (ok) return Promise.resolve(true);
                } catch (_) {}
            }

            return fetch(endpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                },
                credentials: 'same-origin',
                keepalive: true,
                cache: 'no-store',
                body,
            }).then(() => true).catch(() => false);
        };

        const prependBatch = (batch) => {
            if (!batch.length) return;
            for (let i = batch.length - 1; i >= 0; i -= 1) {
                queue.unshift(batch[i]);
            }
        };

        const flush = (preferBeacon = false) => {
            if (!queue.length) return;
            if (flushInFlight && !preferBeacon) return;

            const batch = queue.splice(0, Math.min(batchMax, queue.length));
            if (!batch.length) return;

            flushInFlight = true;
            sendBatch(batch, preferBeacon)
                .then((ok) => {
                    if (!ok && !preferBeacon) {
                        prependBatch(batch);
                    }
                })
                .finally(() => {
                    flushInFlight = false;
                    if (queue.length) {
                        scheduleFlush();
                    }
                });
        };

        window.eduxFirstPartyTrack = (eventName, payload = {}, options = {}) => {
            const name = limitString(eventName, 120);
            if (!name) return;

            const event = buildEvent(name, payload, options);
            if (!event.event_name) return;

            queue.push(event);

            if (queue.length >= batchMax) {
                flush(false);
                return;
            }

            scheduleFlush();
        };

        window.eduxFirstPartyFlush = () => flush(false);
        window.eduxFirstPartyIds = () => ({
            visitorId: limitString(readCookie(visitorCookieName), 64),
            sessionId: limitString(readCookie(sessionCookieName), 64),
        });

        document.addEventListener('visibilitychange', () => {
            if (document.visibilityState === 'hidden') {
                flush(true);
            }
        });

        window.addEventListener('pagehide', () => {
            flush(true);
        });
    })();
</script>
