@php
    $landingMetaPixelId = trim((string) config('services.meta.landing_pixel_id'));
@endphp

@if($landingMetaPixelId !== '' && preg_match('/^[0-9]+$/', $landingMetaPixelId))
    <script>
        !function(f,b,e,v,n,t,s)
        {if(f.fbq)return;n=f.fbq=function(){n.callMethod?
        n.callMethod.apply(n,arguments):n.queue.push(arguments)};
        if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
        n.queue=[];t=b.createElement(e);t.async=!0;
        t.src=v;s=b.getElementsByTagName(e)[0];
        s.parentNode.insertBefore(t,s)}(window, document,'script',
        'https://connect.facebook.net/en_US/fbevents.js');

        fbq('init', @js($landingMetaPixelId));
        fbq('track', 'PageView');

        document.addEventListener('DOMContentLoaded', function () {
            document.addEventListener('click', function (event) {
                var target = event.target.closest('a, button');

                if (!target || typeof window.fbq !== 'function') {
                    return;
                }

                var eventName = target.dataset.metaEvent || '';
                var href = target.getAttribute('href') || '';
                var label = (target.textContent || '').toLowerCase();

                if (!eventName && (href.indexOf('wa.me') !== -1 || href.indexOf('whatsapp') !== -1 || label.indexOf('whatsapp') !== -1)) {
                    eventName = 'Contact';
                }

                if (!eventName && (href.indexOf('crear-tienda-gratis') !== -1 || label.indexOf('crear mi tienda') !== -1 || label.indexOf('comenzar gratis') !== -1 || label.indexOf('probar pro') !== -1 || label.indexOf('probar premium') !== -1)) {
                    eventName = 'Lead';
                }

                if (eventName) {
                    fbq('track', eventName);
                }
            });
        });
    </script>
@endif
