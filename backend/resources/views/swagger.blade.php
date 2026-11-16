<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WearDay E-Commerce REST API - Interactive Documentation</title>
    <link rel="stylesheet" href="https://unpkg.com/swagger-ui-dist@5/swagger-ui.css" />
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>👔</text></svg>">
    <style>
        body {
            margin: 0;
            padding: 0;
            background: #fafafa;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
        }
        .topbar {
            display: none !important;
        }
        .custom-header {
            background: #0f172a;
            color: #ffffff;
            padding: 16px 32px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }
        .custom-header .brand {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .custom-header .logo-badge {
            background: #4f46e5;
            color: white;
            padding: 6px 12px;
            font-weight: 800;
            border-radius: 6px;
            letter-spacing: 0.05em;
            font-size: 14px;
        }
        .custom-header h1 {
            font-size: 16px;
            margin: 0;
            font-weight: 600;
            color: #f8fafc;
        }
        .custom-header .links {
            display: flex;
            gap: 16px;
            font-size: 13px;
        }
        .custom-header a {
            color: #94a3b8;
            text-decoration: none;
            transition: color 0.15s;
        }
        .custom-header a:hover {
            color: #ffffff;
        }
        #swagger-ui {
            max-width: 1300px;
            margin: 0 auto;
            padding: 20px;
        }
        .swagger-ui .info {
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <header class="custom-header">
        <div class="brand">
            <span class="logo-badge">WEARDAY</span>
            <h1>REST API Documentation (v1)</h1>
        </div>
        <div class="links">
            <a href="/admin" target="_blank">Admin Dashboard &rarr;</a>
            <a href="/openapi.json" target="_blank">OpenAPI Spec (JSON)</a>
        </div>
    </header>

    <div id="swagger-ui"></div>

    <script src="https://unpkg.com/swagger-ui-dist@5/swagger-ui-bundle.js"></script>
    <script src="https://unpkg.com/swagger-ui-dist@5/swagger-ui-standalone-preset.js"></script>
    <script>
        window.onload = function() {
            SwaggerUIBundle({
                url: "/openapi.json",
                dom_id: '#swagger-ui',
                deepLinking: true,
                presets: [
                    SwaggerUIBundle.presets.apis,
                    SwaggerUIStandalonePreset
                ],
                plugins: [
                    SwaggerUIBundle.plugins.DownloadUrl
                ],
                layout: "StandaloneLayout",
                persistAuthorization: true
            });
        };
    </script>
</body>
</html>

