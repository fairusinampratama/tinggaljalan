@php
    $googleAdsId = trim((string) config('services.google_ads.id'));
    $googleAdsId = config('services.google_ads.consent_enabled') ? $googleAdsId : '';
    $gtmId = config('services.google_tag_manager.enabled')
        ? trim((string) config('services.google_tag_manager.id')) : '';
    $ga4Id = config('services.google_analytics.enabled')
        ? trim((string) config('services.google_analytics.id')) : '';
    $ga4Id = preg_match('/^G-[A-Z0-9]+$/', $ga4Id) ? $ga4Id : '';
    $gtmId = preg_match('/^GTM-[A-Z0-9]+$/', $gtmId) ? $gtmId : '';
@endphp
@if ($googleAdsId !== '' || $gtmId !== '' || $ga4Id !== '')
    <script data-google-ads-consent="{{ $googleAdsId }}" data-google-tag-manager="{{ $gtmId }}" data-google-analytics="{{ $ga4Id }}">
        (function (window, document, adsId, gtmId, ga4Id) {
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

                if (gtmId && !document.getElementById('tinggaljalan-gtm')) {
                    window.dataLayer.push({ 'gtm.start': new Date().getTime(), event: 'gtm.js' });
                    var container = document.createElement('script');
                    container.async = true;
                    container.id = 'tinggaljalan-gtm';
                    container.src = 'https://www.googletagmanager.com/gtm.js?id=' + encodeURIComponent(gtmId);
                    document.head.appendChild(container);
                }

                if (!adsId && !ga4Id) return;
                window.gtag('js', new Date());
                if (adsId) window.gtag('config', adsId);
                if (ga4Id) window.gtag('config', ga4Id);

                if (!document.getElementById(scriptId)) {
                    var script = document.createElement('script');
                    script.async = true;
                    script.id = scriptId;
                    script.src = 'https://www.googletagmanager.com/gtag/js?id=' + encodeURIComponent(adsId || ga4Id);
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
        })(window, document, {{ Illuminate\Support\Js::from($googleAdsId) }}, {{ Illuminate\Support\Js::from($gtmId) }}, {{ Illuminate\Support\Js::from($ga4Id) }});
    </script>
@endif
