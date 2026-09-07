@php($googleAdsId = trim((string) config('services.google_ads.id')))
@if ($googleAdsId !== '')
    <script data-google-ads-consent="{{ $googleAdsId }}">
        (function (window, document, adsId) {
            if (window.TinggalJalanConsent) return;

            var storageKey = 'tinggaljalan-consent-v1';
            var eventName = 'tinggaljalan:consent-change';
            var settingsEventName = 'tinggaljalan:consent-settings';
            var scriptId = 'tinggaljalan-google-ads';
            var configured = false;

            window.dataLayer = window.dataLayer || [];
            window.gtag = window.gtag || function () { window.dataLayer.push(arguments); };

            function consentValues(value) {
                return {
                    ad_storage: value,
                    ad_user_data: value,
                    ad_personalization: value,
                    analytics_storage: value
                };
            }

            function read() {
                try {
                    var value = window.localStorage.getItem(storageKey);
                    return value === 'granted' || value === 'denied' ? value : 'unknown';
                } catch (error) {
                    return 'unknown';
                }
            }

            function write(value) {
                try {
                    window.localStorage.setItem(storageKey, value);
                } catch (error) {
                    // Consent still applies to this page when storage is unavailable.
                }
            }

            function notify(value) {
                window.dispatchEvent(new CustomEvent(eventName, { detail: { status: value } }));
            }

            function loadGoogleTag() {
                if (configured) return;
                configured = true;

                window.gtag('js', new Date());
                window.gtag('config', adsId);

                if (!document.getElementById(scriptId)) {
                    var script = document.createElement('script');
                    script.async = true;
                    script.id = scriptId;
                    script.src = 'https://www.googletagmanager.com/gtag/js?id=' + encodeURIComponent(adsId);
                    document.head.appendChild(script);
                }
            }

            function grant() {
                write('granted');
                window.gtag('consent', 'update', consentValues('granted'));
                loadGoogleTag();
                notify('granted');
            }

            function deny() {
                write('denied');
                window.gtag('consent', 'update', consentValues('denied'));
                notify('denied');
            }

            window.gtag('consent', 'default', consentValues('denied'));

            window.TinggalJalanConsent = Object.freeze({
                storageKey: storageKey,
                status: read,
                grant: grant,
                deny: deny,
                openSettings: function () {
                    window.dispatchEvent(new CustomEvent(settingsEventName));
                }
            });

            if (read() === 'granted') {
                window.gtag('consent', 'update', consentValues('granted'));
                loadGoogleTag();
            }
        })(window, document, {{ Illuminate\Support\Js::from($googleAdsId) }});
    </script>
@endif
