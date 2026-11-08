<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'MediFlow installation')</title>
    <style>
        body{margin:0;background:#eef5f8;color:#173248;font:16px/1.55 system-ui,sans-serif}main{max-width:1000px;margin:48px auto;padding:0 20px}.card{background:white;border:1px solid #d5e3ea;border-radius:16px;padding:28px;margin-bottom:20px}h1{font-size:30px;margin:8px 0}h2{font-size:21px}h3{margin-bottom:4px}p{margin:8px 0;color:#4a6578}label{display:block;font-weight:600;margin:12px 0 4px}input,select,textarea{box-sizing:border-box;width:100%;border:1px solid #abc0cd;border-radius:8px;padding:11px;font:inherit}button,.button{display:inline-block;border:0;background:#087e8b;color:white;border-radius:8px;padding:12px 22px;font:inherit;font-weight:700;cursor:pointer;text-decoration:none}a{color:#086c7c}.grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:20px}.error{background:#fff1f0;color:#a52525;border-radius:8px;padding:12px}.steps{font-size:13px;letter-spacing:.04em;color:#087e8b;font-weight:700}.package{border:1px solid #d5e3ea;border-radius:10px;padding:16px;margin-bottom:12px}.package p,.feature-description{font-size:14px}summary{cursor:pointer;font-weight:600}li{margin:10px 0}.muted{font-size:13px;color:#4a6578}.actions{margin-top:24px}.badge{font-size:12px;background:#e6f5f2;padding:2px 8px;border-radius:10px}
    </style>
</head>
<body><main>
    <p class="steps">MEDIFLOW ? ACTIVATE ? INSTALLATION SETUP ? WELCOME</p>
    @if($errors->any())<div role="alert" class="error"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    @yield('content')
</main></body>
</html>
