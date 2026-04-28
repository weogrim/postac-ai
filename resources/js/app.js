import htmx from 'htmx.org';
import * as Sentry from '@sentry/browser';
import Alpine from 'alpinejs';
import intersect from '@alpinejs/intersect';

const dsn = document.querySelector('meta[name="sentry-dsn"]')?.content;

if (dsn) {
    Sentry.init({
        dsn,
        environment: document.querySelector('meta[name="sentry-environment"]')?.content ?? 'production',
        release: document.querySelector('meta[name="sentry-release"]')?.content ?? undefined,
        tracesSampleRate: 0,
    });
}

window.htmx = htmx;
window.Sentry = Sentry;
window.Alpine = Alpine;

htmx.config.transitions = true;

Alpine.plugin(intersect);
Alpine.start();

const TOAST_DISMISS_MS = 5000;

const scheduleToastDismiss = (root) => {
    const toasts = root.querySelectorAll('#toasts > [role="alert"]:not([data-dismiss-scheduled])');
    toasts.forEach((toast) => {
        toast.dataset.dismissScheduled = '1';
        setTimeout(() => {
            toast.style.transition = 'opacity 300ms ease, transform 300ms ease';
            toast.style.opacity = '0';
            toast.style.transform = 'translateY(-8px)';
            setTimeout(() => toast.remove(), 320);
        }, TOAST_DISMISS_MS);
    });
};

document.addEventListener('DOMContentLoaded', () => scheduleToastDismiss(document));
document.addEventListener('htmx:after:swap', (e) => scheduleToastDismiss(document));

const wireBirthdatePicker = (root) => {
    const fieldsets = root.querySelectorAll('[data-birthdate-picker]:not([data-bd-wired])');
    fieldsets.forEach((fieldset) => {
        fieldset.dataset.bdWired = '1';
        const target = fieldset.querySelector('[data-birthdate-target]');
        const day = fieldset.querySelector('[data-birthdate-part="day"]');
        const month = fieldset.querySelector('[data-birthdate-part="month"]');
        const year = fieldset.querySelector('[data-birthdate-part="year"]');
        if (!target || !day || !month || !year) return;
        const sync = () => {
            target.value = day.value && month.value && year.value
                ? `${year.value}-${month.value}-${day.value}`
                : '';
        };
        [day, month, year].forEach((el) => el.addEventListener('change', sync));
        sync();
    });
};

document.addEventListener('DOMContentLoaded', () => wireBirthdatePicker(document));
document.addEventListener('htmx:after:swap', () => wireBirthdatePicker(document));
