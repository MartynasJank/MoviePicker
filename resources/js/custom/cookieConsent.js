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
        if (window.showAdConsent && CookieConsent.acceptedCategory('advertising')) {
            gtag('consent', 'update', { ad_storage: 'granted', ad_user_data: 'granted', ad_personalization: 'granted' });
        }
    },

    onChange: ({ changedCategories }) => {
        if (changedCategories.includes('analytics')) {
            gtag('consent', 'update', {
                analytics_storage: CookieConsent.acceptedCategory('analytics') ? 'granted' : 'denied',
            });
        }
        if (window.showAdConsent && changedCategories.includes('advertising')) {
            const granted = CookieConsent.acceptedCategory('advertising');
            gtag('consent', 'update', {
                ad_storage:          granted ? 'granted' : 'denied',
                ad_user_data:        granted ? 'granted' : 'denied',
                ad_personalization:  granted ? 'granted' : 'denied',
            });
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
        ...(window.showAdConsent ? {
            advertising: {
                enabled: false,
                autoClear: {
                    cookies: [{ name: /^_gcl/ }],
                },
            },
        } : {}),
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
                        ...(window.showAdConsent ? [{
                            title: 'Advertising cookies',
                            description: 'Used to show relevant ads via Google AdSense. Declining means you may still see ads but they won\'t be personalised.',
                            linkedCategory: 'advertising',
                        }] : []),
                    ],
                },
            },
        },
    },
});
