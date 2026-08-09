<?php

use App\Providers\AppServiceProvider;
use App\Providers\AssistantServiceProvider;
use App\Providers\AuthServiceProvider;
use App\Providers\FortifyServiceProvider;

return [
    AppServiceProvider::class,
    AssistantServiceProvider::class,
    AuthServiceProvider::class,
    FortifyServiceProvider::class,
];
