<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ainstein API Documentation</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 0;
            background-color: #f8fafc;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }
        h1, h2, h3 {
            color: #2d3748;
        }
        .endpoint {
            background: white;
            border-radius: 8px;
            padding: 1.5rem;
            margin: 1rem 0;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .method {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 4px;
            font-weight: bold;
            font-size: 0.875rem;
            margin-right: 1rem;
        }
        .get { background-color: #10b981; color: white; }
        .post { background-color: #3b82f6; color: white; }
        .put { background-color: #f59e0b; color: white; }
        .delete { background-color: #ef4444; color: white; }
        .url {
            font-family: monospace;
            background-color: #f3f4f6;
            padding: 0.5rem;
            border-radius: 4px;
            margin: 0.5rem 0;
        }
        .description {
            color: #6b7280;
            margin: 0.5rem 0;
        }
        .auth-required {
            background-color: #fef3c7;
            color: #92400e;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.875rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Ainstein API Documentation</h1>
        <p>Version 1.0.0 - RESTful API for content generation and management</p>

        <h2>Authentication</h2>
        <p>The API uses Laravel Sanctum for authentication. Include the Bearer token in the Authorization header:</p>
        <div class="url">Authorization: Bearer {your-api-token}</div>

        <h2>Base URL</h2>
        <div class="url">{{ url('/api/v1') }}</div>

        <h2>Authentication Endpoints</h2>

        <div class="endpoint">
            <span class="method post">POST</span>
            <strong>/auth/login</strong>
            <div class="description">Authenticate user and return token</div>
            <div class="url">Body: { "email": "user@example.com", "password": "password" }</div>
        </div>

        <div class="endpoint">
            <span class="method post">POST</span>
            <strong>/auth/register</strong>
            <div class="description">Register new user</div>
            <div class="url">Body: { "name": "User Name", "email": "user@example.com", "password": "password", "password_confirmation": "password", "tenant_id": "tenant-id" }</div>
        </div>

        <div class="endpoint">
            <span class="method post">POST</span>
            <strong>/auth/logout</strong>
            <span class="auth-required">Auth Required</span>
            <div class="description">Revoke current token</div>
        </div>

        <div class="endpoint">
            <span class="method get">GET</span>
            <strong>/auth/me</strong>
            <span class="auth-required">Auth Required</span>
            <div class="description">Get current user details</div>
        </div>

        <h2>Tenant Management</h2>

        <div class="endpoint">
            <span class="method get">GET</span>
            <strong>/tenants</strong>
            <span class="auth-required">Auth Required</span>
            <div class="description">List tenants (super admin sees all, users see only their own)</div>
        </div>

        <div class="endpoint">
            <span class="method get">GET</span>
            <strong>/tenants/{id}</strong>
            <span class="auth-required">Auth Required</span>
            <div class="description">Show tenant details</div>
        </div>

        <div class="endpoint">
            <span class="method post">POST</span>
            <strong>/tenants</strong>
            <span class="auth-required">Super Admin Only</span>
            <div class="description">Create new tenant</div>
        </div>

        <div class="endpoint">
            <span class="method put">PUT</span>
            <strong>/tenants/{id}</strong>
            <span class="auth-required">Auth Required</span>
            <div class="description">Update tenant (super admin or tenant admin)</div>
        </div>

        <div class="endpoint">
            <span class="method delete">DELETE</span>
            <strong>/tenants/{id}</strong>
            <span class="auth-required">Super Admin Only</span>
            <div class="description">Delete tenant</div>
        </div>

        <h2>Page Management</h2>

        <div class="endpoint">
            <span class="method get">GET</span>
            <strong>/pages</strong>
            <span class="auth-required">Auth Required</span>
            <div class="description">List pages for current tenant</div>
            <div class="url">Query params: status, category, language, search, sort_by, sort_direction, per_page</div>
        </div>

        <div class="endpoint">
            <span class="method get">GET</span>
            <strong>/pages/{id}</strong>
            <span class="auth-required">Auth Required</span>
            <div class="description">Show page details</div>
        </div>

        <div class="endpoint">
            <span class="method post">POST</span>
            <strong>/pages</strong>
            <span class="auth-required">Auth Required</span>
            <div class="description">Create new page</div>
        </div>

        <div class="endpoint">
            <span class="method put">PUT</span>
            <strong>/pages/{id}</strong>
            <span class="auth-required">Auth Required</span>
            <div class="description">Update page</div>
        </div>

        <div class="endpoint">
            <span class="method delete">DELETE</span>
            <strong>/pages/{id}</strong>
            <span class="auth-required">Auth Required</span>
            <div class="description">Delete page</div>
        </div>

        <h2>Prompt Management</h2>

        <div class="endpoint">
            <span class="method get">GET</span>
            <strong>/prompts</strong>
            <span class="auth-required">Auth Required</span>
            <div class="description">List prompts for current tenant</div>
            <div class="url">Query params: category, is_active, search, include_system, sort_by, sort_direction, per_page</div>
        </div>

        <div class="endpoint">
            <span class="method get">GET</span>
            <strong>/prompts/{id}</strong>
            <span class="auth-required">Auth Required</span>
            <div class="description">Show prompt details</div>
        </div>

        <div class="endpoint">
            <span class="method post">POST</span>
            <strong>/prompts</strong>
            <span class="auth-required">Auth Required</span>
            <div class="description">Create new prompt</div>
        </div>

        <div class="endpoint">
            <span class="method put">PUT</span>
            <strong>/prompts/{id}</strong>
            <span class="auth-required">Auth Required</span>
            <div class="description">Update prompt</div>
        </div>

        <div class="endpoint">
            <span class="method delete">DELETE</span>
            <strong>/prompts/{id}</strong>
            <span class="auth-required">Auth Required</span>
            <div class="description">Delete prompt (cannot delete system prompts)</div>
        </div>

        <h2>Content Generation</h2>

        <div class="endpoint">
            <span class="method get">GET</span>
            <strong>/content-generations</strong>
            <span class="auth-required">Auth Required</span>
            <div class="description">List content generations for current tenant</div>
            <div class="url">Query params: status, prompt_type, ai_model, page_id, date_from, date_to, sort_by, sort_direction, per_page</div>
        </div>

        <div class="endpoint">
            <span class="method get">GET</span>
            <strong>/content-generations/{id}</strong>
            <span class="auth-required">Auth Required</span>
            <div class="description">Show content generation details</div>
        </div>

        <div class="endpoint">
            <span class="method post">POST</span>
            <strong>/content-generations</strong>
            <span class="auth-required">Auth Required</span>
            <div class="description">Create new content generation</div>
        </div>

        <div class="endpoint">
            <span class="method put">PUT</span>
            <strong>/content-generations/{id}</strong>
            <span class="auth-required">Auth Required</span>
            <div class="description">Update content generation</div>
        </div>

        <div class="endpoint">
            <span class="method delete">DELETE</span>
            <strong>/content-generations/{id}</strong>
            <span class="auth-required">Auth Required</span>
            <div class="description">Delete content generation (cannot delete published)</div>
        </div>

        <h2>Utility Endpoints</h2>

        <div class="endpoint">
            <span class="method get">GET</span>
            <strong>/utils/tenant</strong>
            <span class="auth-required">Auth Required</span>
            <div class="description">Get current user's tenant information</div>
        </div>

        <div class="endpoint">
            <span class="method get">GET</span>
            <strong>/utils/stats</strong>
            <span class="auth-required">Auth Required</span>
            <div class="description">Get statistics for current tenant</div>
        </div>

        <div class="endpoint">
            <span class="method get">GET</span>
            <strong>/utils/health</strong>
            <span class="auth-required">Auth Required</span>
            <div class="description">API health check</div>
        </div>

        <h2>Admin Endpoints</h2>

        <div class="endpoint">
            <span class="method get">GET</span>
            <strong>/admin/stats</strong>
            <span class="auth-required">Super Admin Only</span>
            <div class="description">Get system-wide statistics</div>
        </div>

        <h2>Response Format</h2>
        <p>All API responses follow this consistent format:</p>
        <div class="url">
{<br>
&nbsp;&nbsp;"success": true,<br>
&nbsp;&nbsp;"message": "Operation successful",<br>
&nbsp;&nbsp;"data": { ... }<br>
}
        </div>

        <p>Paginated responses include additional meta information:</p>
        <div class="url">
{<br>
&nbsp;&nbsp;"success": true,<br>
&nbsp;&nbsp;"message": "Data retrieved successfully",<br>
&nbsp;&nbsp;"data": [...],<br>
&nbsp;&nbsp;"meta": {<br>
&nbsp;&nbsp;&nbsp;&nbsp;"current_page": 1,<br>
&nbsp;&nbsp;&nbsp;&nbsp;"last_page": 5,<br>
&nbsp;&nbsp;&nbsp;&nbsp;"per_page": 15,<br>
&nbsp;&nbsp;&nbsp;&nbsp;"total": 73<br>
&nbsp;&nbsp;}<br>
}
        </div>

        <h2>Error Responses</h2>
        <p>Error responses return appropriate HTTP status codes with error details:</p>
        <div class="url">
{<br>
&nbsp;&nbsp;"success": false,<br>
&nbsp;&nbsp;"message": "Error message",<br>
&nbsp;&nbsp;"errors": { ... } // For validation errors<br>
}
        </div>

        <h2>Status Codes</h2>
        <ul>
            <li><strong>200 OK</strong> - Successful GET, PUT requests</li>
            <li><strong>201 Created</strong> - Successful POST requests</li>
            <li><strong>400 Bad Request</strong> - Invalid request data</li>
            <li><strong>401 Unauthorized</strong> - Authentication required or failed</li>
            <li><strong>403 Forbidden</strong> - Insufficient permissions</li>
            <li><strong>404 Not Found</strong> - Resource not found</li>
            <li><strong>422 Unprocessable Entity</strong> - Validation errors</li>
            <li><strong>500 Internal Server Error</strong> - Server error</li>
        </ul>
    </div>
</body>
</html>