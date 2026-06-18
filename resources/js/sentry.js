import { init, feedbackIntegration, setUser } from '@sentry/browser';

const dsn = document.querySelector('meta[name="sentry-dsn"]')?.content;

if (dsn) {
    init({
        dsn,
        integrations: [feedbackIntegration({ colorScheme: 'light', autoInject: true })],
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
