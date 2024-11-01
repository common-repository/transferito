Sentry.init({
    dsn: "https://766e9785220d4ad9a47464a4ce2fc1fc@o4504776680538112.ingest.sentry.io/4504776683945984",
    integrations: [
        new Sentry.Replay({
            maskAllText: true,
            blockAllMedia: true,
        })
    ],
    replaysSessionSampleRate: 0.1,
    replaysOnErrorSampleRate: 1.0,
});
