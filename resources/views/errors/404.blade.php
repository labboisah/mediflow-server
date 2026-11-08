<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Page Not Found - {{ config('app.name') }}</title>
    
    <link rel="stylesheet" href="{{ asset('css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/bootstrap-icons/font/bootstrap-icons.css') }}">
    
    <style>
        :root {
            --primary-green: #27AE60;
            --primary-orange: #FF8C42;
            --light-green: #E8F8F5;
            --dark-green: #1a4d2e;
        }
        
        body {
            background: linear-gradient(135deg, var(--light-green) 0%, #f8f9fa 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Figtree', 'Poppins', sans-serif;
        }
        
        .error-container {
            text-align: center;
        }
        
        .error-logo {
            margin-bottom: 2rem;
        }
        
        .error-logo img {
            max-height: 80px;
            margin-bottom: 1rem;
        }
        
        .error-icon {
            font-size: 6rem;
            color: var(--primary-orange);
            margin-bottom: 2rem;
            animation: float 3s ease-in-out infinite;
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-20px); }
        }
        
        .error-code {
            font-size: 8rem;
            font-weight: 900;
            background: linear-gradient(135deg, var(--primary-green) 0%, var(--primary-orange) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            line-height: 1;
            margin: 1rem 0;
        }
        
        .error-title {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--dark-green);
            margin-bottom: 1rem;
        }
        
        .error-message {
            font-size: 1.1rem;
            color: #666;
            margin-bottom: 2rem;
            max-width: 500px;
            margin-left: auto;
            margin-right: auto;
        }
        
        .error-actions {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
            margin-bottom: 3rem;
        }
        
        .btn-home {
            background: linear-gradient(135deg, var(--primary-green) 0%, var(--secondary-green) 100%);
            border: none;
            color: white;
            padding: 0.8rem 2rem;
            font-size: 1.05rem;
            font-weight: 600;
            border-radius: 8px;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .btn-home:hover {
            color: white;
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(39, 174, 96, 0.3);
        }
        
        .btn-back {
            background: transparent;
            border: 2px solid var(--primary-green);
            color: var(--primary-green);
            padding: 0.6rem 1.8rem;
            font-size: 1rem;
            font-weight: 600;
            border-radius: 8px;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .btn-back:hover {
            background: var(--primary-green);
            color: white;
        }
        
        .error-details {
            background: white;
            border-radius: 12px;
            padding: 2rem;
            max-width: 600px;
            margin: 0 auto;
            box-shadow: 0 10px 30px rgba(39, 174, 96, 0.1);
        }
        
        .detail-item {
            text-align: left;
            margin: 1rem 0;
            padding: 1rem;
            background: var(--light-green);
            border-left: 4px solid var(--primary-green);
            border-radius: 4px;
        }
        
        .detail-label {
            font-weight: 600;
            color: var(--dark-green);
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .detail-value {
            color: #555;
            margin-top: 0.3rem;
            font-family: 'Courier New', monospace;
            word-break: break-all;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="error-container">
            <div class="error-logo">
                <img src="{{ asset('images/logo.png') }}" alt="{{ config('app.name') }}">
            </div>
            
            <div class="error-icon">
                <i class="bi bi-exclamation-circle"></i>
            </div>
            
            <div class="error-code">404</div>
            
            <h1 class="error-title">Page Not Found</h1>
            
            <p class="error-message">
                Sorry, the page you're looking for doesn't exist or has been moved. 
                It might have been deleted or the link could be incorrect.
            </p>
            
            <div class="error-actions">
                <a href="{{ route('dashboard') }}" class="btn-home">
                    <i class="bi bi-house"></i> Go to Dashboard
                </a>
                <a href="javascript:history.back()" class="btn-back">
                    <i class="bi bi-arrow-left"></i> Go Back
                </a>
            </div>
            
            <div class="error-details">
                <div class="detail-item">
                    <div class="detail-label">Status Code</div>
                    <div class="detail-value">404 Not Found</div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Requested Path</div>
                    <div class="detail-value">{{ request()->path() }}</div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Timestamp</div>
                    <div class="detail-value">{{ date('Y-m-d H:i:s') }}</div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
