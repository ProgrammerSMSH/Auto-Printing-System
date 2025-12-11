<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;
use CodeIgniter\Filters\CSRF;
use CodeIgniter\Filters\DebugToolbar;
use CodeIgniter\Filters\Honeypot;

class Filters extends BaseConfig
{
    public $aliases = [
        'csrf'          => CSRF::class,
        'toolbar'       => DebugToolbar::class,
        'honeypot'      => Honeypot::class,
        'apiAuth'       => \App\Filters\ApiAuthFilter::class,
        'rateLimit'     => \App\Filters\RateLimitFilter::class,
        'ipWhitelist'   => \App\Filters\IPWhitelistFilter::class,
    ];

    public $globals = [
        'before' => [
            'honeypot',
            'csrf' => ['except' => ['api/*']],
        ],
        'after' => [
            'toolbar',
        ],
    ];

    public $methods = [];

    public $filters = [
        'apiAuth' => [
            'before' => ['api/*'],
        ],
        'rateLimit' => [
            'before' => ['api/print/upload'],
        ],
        'ipWhitelist' => [
            'before' => ['api/print/update/*', 'api/print/pending'],
        ],
    ];
}
