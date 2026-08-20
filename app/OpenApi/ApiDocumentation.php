<?php

namespace App\OpenApi;

use OpenApi\Attributes as OA;

#[OA\Info(
    title: 'LMS App API',
    version: '1.0.0',
    description: 'Library Management System App API. Dashboard endpoints are documented in storage/api-docs/dashboard.yaml.'
)]
#[OA\Server(
    url: '/api',
    description: 'API Server'
)]
#[OA\SecurityScheme(
    securityScheme: 'bearerAuth',
    type: 'http',
    scheme: 'bearer',
    bearerFormat: 'Sanctum'
)]
class ApiDocumentation
{
}
