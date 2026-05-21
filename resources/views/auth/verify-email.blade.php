<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>SmartCart - Verify Email</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .verify-container {
            background: white;
            border-radius: 30px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            max-width: 500px;
            width: 100%;
            padding: 50px 40px;
            text-align: center;
        }
        .logo {
            margin-bottom: 20px;
        }
        .logo h2 {
            font-size: 2rem;
            font-weight: 800;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .verify-icon {
            font-size: 4rem;
            margin-bottom: 20px;
        }
        h2 {
            font-size: 1.6rem;
            margin-bottom: 15px;
            color: #1f2937;
        }
        p {
            color: #6b7280;
            margin-bottom: 25px;
            line-height: 1.6;
        }
        .btn-primary {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
        }
        .btn-secondary {
            width: 100%;
            padding: 12px;
            background: #f3f4f6;
            color: #4b5563;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            margin-top: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        .alert-success {
            background: #d1fae5;
            color: #065f46;
            padding: 12px;
            border-radius: 12px;
            margin-bottom: 20px;
            text-align: left;
        }
        .footer-links {
            margin-top: 25px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
        }
        .footer-links a {
            color: #667eea;
            text-decoration: none;
            font-size: 0.85rem;
            margin: 0 10px;
        }
        .footer-links a:hover {
            color: #764ba2;
            text-decoration: underline;
        }
    </style>
</head>
<body>

@auth
    {{-- Auto redirect for users who are already verified --}}
    @if(Auth::user()->hasVerifiedEmail())
        <script>
            @if(Auth::user()->isAdmin())
                window.location.href = "{{ route('admin.dashboard') }}";
            @elseif(Auth::user()->isSeller())
                window.location.href = "{{ route('seller.dashboard') }}";
            @else
                window.location.href = "{{ route('dashboard') }}";
            @endif
        </script>
    @endif
@endauth

<div class="verify-container">
    <div class="logo">
        <h2>🛒 SmartCart</h2>
    </div>
    
    <div class="verify-icon">
        📧
    </div>
    
    <h2>Verify Your Email Address</h2>
    
    <p>Thanks for signing up! Before getting started, could you verify your email address by clicking on the link we just emailed to you?</p>
    
    @if (session('status') == 'verification-link-sent')
        <div class="alert-success">
            <i class="fas fa-check-circle"></i> A new verification link has been sent to your email address.
        </div>
    @endif
    
    <form method="POST" action="{{ route('verification.send') }}">
        @csrf
        <button type="submit" class="btn-primary">
            <i class="fas fa-envelope"></i> Resend Verification Email
        </button>
    </form>
    
    <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button type="submit" class="btn-secondary">
            <i class="fas fa-sign-out-alt"></i> Log Out
        </button>
    </form>
    
    <div class="footer-links">
        <a href="{{ url('/') }}">
            <i class="fas fa-home"></i> Home
        </a>
        <a href="{{ route('login') }}">
            <i class="fas fa-sign-in-alt"></i> Login
        </a>
    </div>
    
    <p style="margin-top: 20px; font-size: 0.7rem; color: #9ca3af;">
        Didn't receive email? Check your spam folder.
    </p>
</div>
</body>
</html>