import htmx from 'htmx.org';
import * as Sentry from '@sentry/browser';

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

htmx.config.transitions = true;
