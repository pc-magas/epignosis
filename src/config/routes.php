<?php

return [
    '/'=>[
        'http_method'=>App\Application::HTTP_GET,
        'controller'=>App\Controllers\BaseController::class."@hello",
    ]
];