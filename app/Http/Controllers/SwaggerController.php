<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;
use Illuminate\Support\Facades\File;
use Illuminate\View\View;

class SwaggerController extends Controller
{
    public function index(): View
    {
        return view('swagger.index');
    }

    public function spec(): Response
    {
        $path = storage_path('api-docs/openapi.yaml');

        if (! File::exists($path)) {
            abort(404, 'API documentation file not found');
        }

        return response(File::get($path), 200, [
            'Content-Type' => 'application/yaml',
        ]);
    }
}
