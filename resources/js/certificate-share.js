import { Capacitor } from '@capacitor/core';
import { Directory, Filesystem } from '@capacitor/filesystem';
import { Share } from '@capacitor/share';

const TRIGGER_SELECTOR = '[data-certificate-share-trigger="1"]';
const LABEL_SELECTOR = '[data-certificate-share-label]';

let listenerBound = false;

const isNativeCapacitor = () => {
    try {
        return Capacitor.isNativePlatform();
    } catch (_) {
        return false;
    }
};

const normalizeFileName = (value) => {
    const candidate = String(value ?? '').trim();
    const sanitized = candidate
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .replace(/[^A-Za-z0-9._-]+/g, '-')
        .replace(/-+/g, '-')
        .replace(/^-|-$/g, '');

    if (sanitized === '') {
        return 'certificado.pdf';
    }

    return sanitized.toLowerCase().endsWith('.pdf')
        ? sanitized
        : `${sanitized}.pdf`;
};

const blobToBase64 = async (blob) => {
    const buffer = await blob.arrayBuffer();
    const bytes = new Uint8Array(buffer);
    const chunkSize = 0x8000;
    let binary = '';

    for (let index = 0; index < bytes.length; index += chunkSize) {
        const chunk = bytes.subarray(index, index + chunkSize);
        binary += String.fromCharCode(...chunk);
    }

    return window.btoa(binary);
};

const updateNativeLabels = () => {
    const native = isNativeCapacitor();

    document.documentElement.dataset.capacitorNative = native ? '1' : '0';

    document.querySelectorAll(LABEL_SELECTOR).forEach((label) => {
        const webLabel = label.dataset.webLabel || label.textContent?.trim() || 'Baixar PDF';
        const nativeLabel = label.dataset.nativeLabel || webLabel;

        label.textContent = native ? nativeLabel : webLabel;
    });
};

const fallbackToWebDownload = (trigger) => {
    const href = trigger.getAttribute('href')
        || trigger.dataset.certificateDownloadUrl
        || trigger.dataset.certificatePublicUrl;

    if (! href) {
        return;
    }

    window.location.assign(href);
};

const fetchCertificatePdf = async (downloadUrl) => {
    const absoluteUrl = new URL(downloadUrl, window.location.href).toString();
    const response = await window.fetch(absoluteUrl, {
        credentials: 'include',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
        },
    });

    if (! response.ok) {
        throw new Error(`Certificate download failed with status ${response.status}`);
    }

    const contentType = (response.headers.get('content-type') || '').toLowerCase();
    if (! contentType.includes('application/pdf')) {
        throw new Error('Certificate endpoint did not return a PDF.');
    }

    return response.blob();
};

const shareCertificatePdf = async (trigger) => {
    const downloadUrl = trigger.dataset.certificateDownloadUrl || trigger.getAttribute('href') || '';
    const publicUrl = trigger.dataset.certificatePublicUrl || '';
    const title = trigger.dataset.certificateTitle || 'Certificado';
    const fileName = normalizeFileName(trigger.dataset.certificateFilename || 'certificado.pdf');

    if (downloadUrl === '') {
        throw new Error('Certificate download URL is missing.');
    }

    const blob = await fetchCertificatePdf(downloadUrl);
    const data = await blobToBase64(blob);
    const file = await Filesystem.writeFile({
        path: `certificates/${Date.now()}-${fileName}`,
        data,
        directory: Directory.Cache,
        recursive: true,
    });

    if (typeof Share.canShare === 'function') {
        const availability = await Share.canShare();
        if (availability?.value === false) {
            throw new Error('Native share is not available.');
        }
    }

    await Share.share({
        title,
        dialogTitle: title,
        text: publicUrl ? `Validar certificado: ${publicUrl}` : title,
        url: file.uri,
    });
};

const handleCertificateShareClick = async (event) => {
    const target = event.target instanceof Element ? event.target.closest(TRIGGER_SELECTOR) : null;

    if (! target || ! isNativeCapacitor()) {
        return;
    }

    event.preventDefault();

    if (target.dataset.shareBusy === '1') {
        return;
    }

    const label = target.querySelector(LABEL_SELECTOR);
    const busyLabel = target.dataset.certificateSharingLabel || 'Preparando PDF...';

    target.dataset.shareBusy = '1';
    target.setAttribute('aria-busy', 'true');

    if (label) {
        label.textContent = busyLabel;
    }

    try {
        await shareCertificatePdf(target);
    } catch (error) {
        console.error('[certificate-share] native share failed', error);
        fallbackToWebDownload(target);
    } finally {
        delete target.dataset.shareBusy;
        target.removeAttribute('aria-busy');
        updateNativeLabels();
    }
};

export const initCertificateShare = () => {
    updateNativeLabels();

    if (listenerBound) {
        return;
    }

    listenerBound = true;
    document.addEventListener('click', (event) => {
        void handleCertificateShareClick(event);
    });
};
