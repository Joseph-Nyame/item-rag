<!DOCTYPE html>
<html>
<head>
    <title>{{ $branch }} Branch</title>
    <style>
        body {
            background: {{ $color }};
            color: white;
            text-align: center;
            padding: 50px;
            font-family: Arial, sans-serif;
        }
        h1 { font-size: 48px; margin-bottom: 20px; }
        p { font-size: 24px; }
        .info { background: rgba(0,0,0,0.2); padding: 20px; border-radius: 10px; display: inline-block; margin-top: 20px; }
    </style>
</head>
<body>
    <h1>🔵 {{ $branch }} Branch</h1>
    <p>This is the {{ strtolower($branch) }} environment</p>
    <div class="info">
        <p>Environment: {{ $env }}</p>
        <p>Laravel Version: {{ app()->version() }}</p>
    </div>
</body>
</html>
