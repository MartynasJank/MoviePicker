import * as CookieConsent from 'vanilla-cookieconsent';

CookieConsent.run({
    guiOptions: {
        consentModal: {
            layout: 'bar',
            position: 'bottom',
            equalWeightButtons: true,
        },
        preferencesModal: {
            layout: 'box',
        },
    },

    onConsent: ({ cookie }) => {
        if (CookieConsent.acceptedCategory('analytics')) {
            gtag('consent', 'update', { analytics_storage: 'granted' });
        }
    },

    onChange: ({ changedCategories }) => {
        if (changedCategories.includes('analytics')) {
            if (CookieConsent.acceptedCategory('analytics')) {
                gtag('consent', 'update', { analytics_storage: 'granted' });
            } else {
                gtag('consent', 'update', { analytics_storage: 'denied' });
            }
        }
    },

    categories: {
        necessary: {
            enabled: true,
            readOnly: true,
        },
        analytics: {
            enabled: false,
            autoClear: {
                cookies: [
                    { name: /^_ga/ },
                    { name: '_gid' },
                ],
            },
        },
    },

    language: {
        default: 'en',
        translations: {
            en: {
                consentModal: {
                    title: 'We use cookies',
                    description: 'We use analytics cookies to understand how people use MoviePickr and improve the experience. You can choose to decline if you prefer.',
                    acceptAllBtn: 'Accept',
                    acceptNecessaryBtn: 'Decline',
                    showPreferencesBtn: 'Manage',
                },
                preferencesModal: {
                    title: 'Cookie preferences',
                    acceptAllBtn: 'Accept all',
                    acceptNecessaryBtn: 'Decline all',
                    savePreferencesBtn: 'Save',
                    sections: [
                        {
                            title: 'Necessary cookies',
                            description: 'Required for the site to work — login sessions, CSRF protection, and theme preference.',
                            linkedCategory: 'necessary',
                        },
                        {
                            title: 'Analytics cookies',
                            description: 'Google Analytics (GA4) helps us understand which features are used and how people navigate the site. No personal data is sold or shared.',
                            linkedCategory: 'analytics',
                        },
                    ],
                },
            },
        },
    },
});
