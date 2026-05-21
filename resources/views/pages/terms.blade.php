<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SmartCart - Terms & Conditions</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 40px 20px;
        }
        .container {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            border-radius: 30px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 40px;
            text-align: center;
            color: white;
        }
        .header h1 {
            font-size: 2.5rem;
            margin-bottom: 10px;
        }
        .header p {
            opacity: 0.9;
        }
        .content {
            padding: 40px;
        }
        .section {
            margin-bottom: 30px;
        }
        .section h2 {
            color: #1f2937;
            font-size: 1.3rem;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .section h2 i {
            color: #667eea;
        }
        .section p {
            color: #4b5563;
            line-height: 1.6;
            margin-bottom: 10px;
        }
        .section ul {
            margin-left: 20px;
            color: #4b5563;
            line-height: 1.6;
        }
        .section li {
            margin-bottom: 8px;
        }
        .last-updated {
            background: #f3f4f6;
            padding: 15px;
            border-radius: 12px;
            text-align: center;
            font-size: 0.85rem;
            color: #6b7280;
            margin-top: 30px;
        }
        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: #f3f4f6;
            color: #4b5563;
            padding: 10px 20px;
            border-radius: 50px;
            text-decoration: none;
            margin-top: 20px;
            transition: all 0.3s;
        }
        .back-btn:hover {
            background: #e5e7eb;
            color: #667eea;
        }
        @media (max-width: 768px) {
            .content { padding: 25px; }
            .header h1 { font-size: 1.8rem; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-file-contract"></i> Terms & Conditions</h1>
            <p>Please read these terms carefully before using SmartCart</p>
        </div>
        
        <div class="content">
            <div class="section">
                <h2><i class="fas fa-calendar-alt"></i> 1. Acceptance of Terms</h2>
                <p>By accessing and using SmartCart, you accept and agree to be bound by the terms and provision of this agreement. If you do not agree to abide by these terms, please do not use this service.</p>
            </div>

            <div class="section">
                <h2><i class="fas fa-user-circle"></i> 2. Account Registration</h2>
                <p>To use certain features of SmartCart, you must register for an account. You agree to:</p>
                <ul>
                    <li>Provide accurate, current, and complete information</li>
                    <li>Maintain the security of your password</li>
                    <li>Accept responsibility for all activities under your account</li>
                    <li>Notify us immediately of any unauthorized use</li>
                </ul>
            </div>

            <div class="section">
                <h2><i class="fas fa-shopping-cart"></i> 3. Shopping & Payments</h2>
                <p>SmartCart facilitates online shopping transactions. By placing an order, you agree to:</p>
                <ul>
                    <li>Provide accurate payment information</li>
                    <li>Pay all charges incurred under your account</li>
                    <li>Comply with all applicable laws and regulations</li>
                    <li>Responsibility for any taxes applicable to your purchases</li>
                </ul>
            </div>

            <div class="section">
                <h2><i class="fas fa-truck"></i> 4. Shipping & Delivery</h2>
                <p>Delivery estimates are provided for reference only. Actual delivery times may vary based on location and circumstances. SmartCart is not responsible for delays caused by third-party shipping carriers.</p>
            </div>

            <div class="section">
                <h2><i class="fas fa-undo-alt"></i> 5. Returns & Refunds</h2>
                <p>Products may be returned within 7 days of delivery if they are unused and in original packaging. Refunds will be processed within 5-7 business days after return approval. Certain items may not be eligible for return.</p>
            </div>

            <div class="section">
                <h2><i class="fas fa-chart-line"></i> 6. Budget Tracking Feature</h2>
                <p>The budget tracking feature is provided as a convenience. SmartCart is not responsible for any financial decisions made based on this feature. You are solely responsible for managing your spending.</p>
            </div>

            <div class="section">
                <h2><i class="fas fa-shield-alt"></i> 7. Privacy & Data Protection</h2>
                <p>Your privacy is important to us. Please review our <a href="{{ route('privacy') }}" style="color: #667eea;">Privacy Policy</a> to understand how we collect, use, and protect your personal information.</p>
            </div>

            <div class="section">
                <h2><i class="fas fa-gavel"></i> 8. Prohibited Activities</h2>
                <p>You agree not to:</p>
                <ul>
                    <li>Use the service for any illegal purpose</li>
                    <li>Attempt to gain unauthorized access to our systems</li>
                    <li>Interfere with or disrupt the service</li>
                    <li>Transmit any viruses or malicious code</li>
                    <li>Harass, abuse, or harm others</li>
                </ul>
            </div>

            <div class="section">
                <h2><i class="fas fa-ban"></i> 9. Account Termination</h2>
                <p>We reserve the right to suspend or terminate your account if you violate these terms. You may delete your account at any time through your profile settings.</p>
            </div>

            <div class="section">
                <h2><i class="fas fa-balance-scale"></i> 10. Limitation of Liability</h2>
                <p>SmartCart shall not be liable for any indirect, incidental, special, consequential, or punitive damages resulting from your use of or inability to use the service.</p>
            </div>

            <div class="section">
                <h2><i class="fas fa-code-branch"></i> 11. Modifications to Terms</h2>
                <p>We may modify these terms at any time. Continued use of the service after changes constitutes acceptance of the new terms.</p>
            </div>

            <div class="section">
                <h2><i class="fas fa-envelope"></i> 12. Contact Us</h2>
                <p>If you have any questions about these Terms, please contact us at: <strong>support@smartcart.com</strong></p>
            </div>

            <div class="last-updated">
                <i class="fas fa-clock"></i> Last Updated: November 30, 2025
            </div>

            <div style="text-align: center; display: flex; gap: 15px; justify-content: center;">
            <a href="{{ url()->previous() ?? url('/') }}" class="back-btn">
                <i class="fas fa-arrow-left"></i> Go Back
            </a>
            <a href="{{ route('register') }}" class="back-btn">
                <i class="fas fa-user-plus"></i> Back to Registration
            </a>
            <a href="{{ url('/') }}" class="back-btn">
                <i class="fas fa-home"></i> Home
            </a>
        </div>
        </div>
    </div>
</body>
</html>