<?php

declare(strict_types=1);

return [
    // App ID to expose to Facebook crawlers (fb:app_id)
    'fb_app_id' => env('FACEBOOK_APP_ID', null),

    // When true, the service will not render channel-level fallback meta
    // if explicit page/product meta has been set via the listener.
    'suppress_channel_when_explicit' => true,

    // When true, render fallback channel meta when no explicit meta set.
    'render_fallback' => true,
];
