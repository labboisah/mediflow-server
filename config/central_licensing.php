<?php

return [
    // Client installations require activation by default; standalone deployments must opt out explicitly.
    'enabled' => strtolower(trim((string) env('APP_MODE', 'client'))) === 'client'
        || (bool) env('MEDIFLOW_CENTRAL_LICENSING', false),
];
