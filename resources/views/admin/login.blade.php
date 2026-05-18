<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Admin Login - Cemetery Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #2d5f2e 0%, #1e4620 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .login-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
            overflow: hidden;
            max-width: 400px;
            width: 100%;
        }

        .login-header {
            background: linear-gradient(to right, #2d5f2e 0%, #3a7d3c 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }

        .login-header h2 {
            margin: 0;
            font-size: 1.8rem;
            font-weight: 600;
        }

        .login-header p {
            margin: 5px 0 0 0;
            opacity: 0.9;
            font-size: 0.9rem;
        }

        .login-body {
            padding: 40px 30px;
        }

        .form-label {
            font-weight: 600;
            color: #2d5f2e;
        }

        .form-control:focus {
            border-color: #3a7d3c;
            box-shadow: 0 0 0 0.2rem rgba(45, 95, 46, 0.25);
        }

        .btn-login {
            background: linear-gradient(to right, #2d5f2e 0%, #3a7d3c 100%);
            border: none;
            color: white;
            padding: 12px;
            font-weight: 600;
            transition: all 0.3s;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(45, 95, 46, 0.4);
            color: white;
        }

        .back-link {
            text-align: center;
            margin-top: 20px;
        }

        .back-link a {
            color: #2d5f2e;
            text-decoration: none;
            font-weight: 600;
        }

        .back-link a:hover {
            color: #3a7d3c;
            text-decoration: underline;
        }

        .alert {
            border-radius: 8px;
        }
    </style>
</head>

<body>
    <div class="login-container">
        <div class="login-header">
            <h2>Login</h2>
            <p>St. John Memorial Garden</p>
        </div>
        <div class="login-body">
            @if ($errors->any())
            <div class="alert alert-danger">
                <strong>Error!</strong> {{ $errors->first() }}
            </div>
            @endif

            @if (session('error'))
            <div class="alert alert-danger">
                {{ session('error') }}
            </div>
            @endif

            <form method="POST" action="{{ route('admin.login.post') }}">
                @csrf
                <div class="mb-3">
                    <label for="username" class="form-label">Username</label>
                    <input type="text" class="form-control" id="username" name="username" required autofocus placeholder="Enter your username">
                </div>
                <div class="mb-4">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" class="form-control" id="password" name="password" required placeholder="Enter your password">
                </div>
                <button type="submit" class="btn btn-login w-100">Login</button>
            </form>

            <div class="back-link">
                <a href="{{ route('homepage') }}">← Back to Public Map</a>
            </div>
        </div>
    </div>
</body>

</html>