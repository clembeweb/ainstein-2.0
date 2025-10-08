<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Ainstein Platform')</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            background-color: #f8fafc;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            line-height: 1.6;
            color: #374151;
        }
        .email-container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .email-header {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
            padding: 40px 30px;
            text-align: center;
        }
        .email-header h1 {
            color: #ffffff;
            margin: 0;
            font-size: 28px;
            font-weight: 700;
        }
        .email-header .logo {
            width: 60px;
            height: 60px;
            background-color: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 16px;
            font-size: 24px;
        }
        .email-body {
            padding: 40px 30px;
        }
        .email-body h2 {
            color: #1f2937;
            font-size: 24px;
            margin-bottom: 16px;
            font-weight: 600;
        }
        .email-body p {
            margin-bottom: 16px;
            line-height: 1.7;
        }
        .button {
            display: inline-block;
            background-color: #f59e0b;
            color: #ffffff;
            padding: 12px 24px;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
            margin: 16px 0;
            transition: background-color 0.3s;
        }
        .button:hover {
            background-color: #d97706;
        }
        .button-secondary {
            background-color: #6b7280;
        }
        .button-secondary:hover {
            background-color: #4b5563;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 16px;
            margin: 24px 0;
        }
        .stat-item {
            text-align: center;
            padding: 16px;
            background-color: #f9fafb;
            border-radius: 6px;
        }
        .stat-number {
            font-size: 24px;
            font-weight: 700;
            color: #f59e0b;
            display: block;
        }
        .stat-label {
            font-size: 12px;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .email-footer {
            background-color: #f9fafb;
            padding: 30px;
            text-align: center;
            border-top: 1px solid #e5e7eb;
        }
        .email-footer p {
            font-size: 14px;
            color: #6b7280;
            margin: 8px 0;
        }
        .social-links {
            margin: 16px 0;
        }
        .social-links a {
            display: inline-block;
            margin: 0 8px;
            padding: 8px;
            color: #6b7280;
            text-decoration: none;
        }
        @media only screen and (max-width: 600px) {
            .email-container {
                margin: 0;
                border-radius: 0;
            }
            .email-header, .email-body {
                padding: 30px 20px;
            }
            .stats-grid {
                grid-template-columns: 1fr 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="email-header">
            <div class="logo">🧠</div>
            <h1>@yield('header', 'Ainstein Platform')</h1>
        </div>

        <div class="email-body">
            @yield('content')
        </div>

        <div class="email-footer">
            <p><strong>Ainstein Platform</strong> - AI-Powered Content Generation</p>
            <p>This email was sent to {{ $user->email ?? 'you' }}.</p>
            <div class="social-links">
                <a href="#">📧 Support</a>
                <a href="#">📖 Documentation</a>
                <a href="#">🌐 Website</a>
            </div>
            <p style="font-size: 12px; color: #9ca3af;">
                © {{ date('Y') }} Ainstein Platform. All rights reserved.
            </p>
        </div>
    </div>
</body>
</html>