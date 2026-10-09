import { init, feedbackIntegration, setUser } from '@sentry/browser';

const dsn = document.querySelector('meta[name="sentry-dsn"]')?.content;

if (dsn) {
    let feedbackLabels = {};
    try {
        feedbackLabels = JSON.parse(document.querySelector('meta[name="sentry-feedback-labels"]')?.content ?? '{}');
    } catch {
        // unparseable meta — fall back to the widget's English defaults
    }

    init({
        dsn,
        integrations: [feedbackIntegration({ colorScheme: 'light', autoInject: true, ...feedbackLabels })],
    });

    const userMeta = document.querySelector('meta[name="sentry-user"]');
    if (userMeta) {
        try {
            const user = JSON.parse(userMeta.content);
            setUser(user);
        } catch {
            // unparseable meta — skip
        }
    }
}
