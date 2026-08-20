<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LMS API — Swagger</title>
    <link rel="stylesheet" href="https://unpkg.com/swagger-ui-dist@5.17.14/swagger-ui.css">
    <style>
        html { box-sizing: border-box; overflow-y: scroll; }
        *, *:before, *:after { box-sizing: inherit; }
        body { margin: 0; background: #fafafa; }
        .swagger-ui .topbar { background: #1b1b1b; }
        .swagger-ui .topbar-wrapper img { display: none; }
        .swagger-ui .topbar .download-url-wrapper { margin: 0; }
    </style>
</head>
<body>
    <div id="swagger-ui"></div>

    <script src="https://unpkg.com/swagger-ui-dist@5.17.14/swagger-ui-bundle.js"></script>
    <script src="https://unpkg.com/swagger-ui-dist@5.17.14/swagger-ui-standalone-preset.js"></script>
    <script>
        window.onload = function () {
            SwaggerUIBundle({
                urls: [
                    { url: "{{ url('/api/documentation/dashboard.yaml') }}", name: "Dashboard API" },
                    { url: "{{ url('/api/documentation/openapi.yaml') }}", name: "App API" }
                ],
                "urls.primaryName": "Dashboard API",
                dom_id: '#swagger-ui',
                deepLinking: true,
                presets: [
                    SwaggerUIBundle.presets.apis,
                    SwaggerUIStandalonePreset
                ],
                plugins: [SwaggerUIBundle.plugins.DownloadUrl],
                layout: "StandaloneLayout",
                persistAuthorization: true,
                tryItOutEnabled: true,
            });
        };
    </script>
</body>
</html>
