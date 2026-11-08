<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Unauthorized - {{ config('app.name') }}</title>
    
    <link rel="stylesheet" href="{{ asset('css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/bootstrap-icons/font/bootstrap-icons.css') }}">
    
    <style>
        :root {
            --primary-green: #27AE60;
            --primary-orange: #FF8C42;
            --light-green: #E8F8F5;
            --dark-green: #1a4d2e;
            --warning-yellow: #F39C12;
            --light-warning: #FEF5E7;
        }
        
        body {
            background: linear-gradient(135deg, var(--light-warning) 0%, #f8f9fa 100%);
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
            color: var(--warning-yellow);
            margin-bottom: 2rem;
            animation: bounce 2s infinite;
        }
        
        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-20px); }
        }
        
        .error-code {
            font-size: 8rem;
            font-weight: 900;
            background: linear-gradient(135deg, var(--warning-yellow) 0%, #F79646 100%);
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
            background: linear-gradient(135deg, var(--primary-green) 0%, var(--primary-green) 100%);
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
            box-shadow: 0 10px 30px rgba(243, 156, 18, 0.1);
            border-top: 4px solid var(--warning-yellow);
        }
        
        .detail-item {
            text-align: left;
            margin: 1rem 0;
            padding: 1rem;
            background: var(--light-warning);
            border-left: 4px solid var(--warning-yellow);
            border-radius: 4px;
        }
        
        .detail-label {
            font-weight: 600;
            color: var(--warning-yellow);
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
                <i class="bi bi-lock"></i>
            </div>
            
            <div class="error-code">401</div>
            
            <h1 class="error-title">Unauthorized</h1>
            
            <p class="error-message">
                You need to be logged in to access this page. 
                Please log in with your credentials to continue.
            </p>
            
            <div class="error-actions">
                <a href="{{ route('login') }}" class="btn-home">
                    <i class="bi bi-box-arrow-in-right"></i> Login
                </a>
                <a href="{{ route('dashboard') }}" class="btn-back">
                    <i class="bi bi-house"></i> Dashboard
                </a>
            </div>
            
            <div class="error-details">
                <div class="detail-item">
                    <div class="detail-label">Status Code</div>
                    <div class="detail-value">401 Unauthorized</div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Requested Resource</div>
                    <div class="detail-value">{{ request()->path() }}</div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Action</div>
                    <div class="detail-value">Please log in to access this resource</div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
