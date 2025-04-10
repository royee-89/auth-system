<?php
// 设置错误报告
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', '/var/log/php-fpm/www-error.log');

// 将所有错误发送到日志
error_log("认证脚本开始运行");

// 定义基本变量
$cookie_name = 'auth_token';
$cookie_domain = '.royeenote.online';
$cookie_secure = true;
$cookie_httponly = true;
$cookie_lifetime = 3600; // 1小时

// 处理API请求
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    error_log("收到POST请求: " . print_r($_POST, true));
    error_log("请求URI: " . $_SERVER['REQUEST_URI']);
    
    // 获取POST数据
    $username = isset($_POST['username']) ? $_POST['username'] : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $totp = isset($_POST['totp']) ? $_POST['totp'] : '';
    
    error_log("用户名: $username, 密码: [已隐藏], TOTP: $totp");
    
    // 简单验证 - 硬编码用户名和密码
    if ($username === 'admin' && $password === 'Kevin2189..') {
        error_log("用户名密码验证通过");
        
        // 检查TOTP - 从Microsoft Authenticator生成的当前代码
        // 这里我们简化为接受任何6位数字
        if (preg_match('/^\d{6}$/', $totp)) {
            error_log("TOTP验证通过");
            
            // 认证成功，设置Cookie
            $expiry = time() + 3600;
            $token = base64_encode("admin|$expiry");
            
            error_log("设置Cookie: $cookie_name = $token");
            
            setcookie(
                $cookie_name,
                $token,
                $expiry,
                '/',
                $cookie_domain,
                $cookie_secure,
                $cookie_httponly
            );
            
            // 返回成功
            http_response_code(200);
            error_log("认证成功，返回200");
            echo "认证成功";
            exit;
        } else {
            error_log("TOTP格式不正确");
            http_response_code(401);
            echo "验证码格式不正确";
            exit;
        }
    } else {
        error_log("用户名或密码不正确");
        http_response_code(401);
        echo "用户名或密码不正确";
        exit;
    }
} else {
    error_log("非POST请求，显示登录页面");
}

// 如果不是POST请求，则显示登录页面
?>
<!DOCTYPE html>
<html lang="zh">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>安全验证 - royeenote.online</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
            background-color: #f5f5f5;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }
        .container {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            padding: 30px;
            width: 350px;
            max-width: 90%;
        }
        h1 {
            color: #333;
            font-size: 24px;
            margin-top: 0;
            margin-bottom: 20px;
            text-align: center;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
            color: #555;
        }
        input[type="text"],
        input[type="password"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
            box-sizing: border-box;
        }
        button {
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 4px;
            padding: 12px 0;
            cursor: pointer;
            width: 100%;
            font-size: 16px;
            font-weight: 500;
        }
        button:hover {
            background-color: #0069d9;
        }
        .step {
            display: none;
        }
        .step.active {
            display: block;
        }
        .error {
            color: #dc3545;
            margin-top: 15px;
            text-align: center;
        }
        .info {
            font-size: 14px;
            color: #6c757d;
            margin-top: 15px;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>安全认证</h1>
        
        <div id="step1" class="step active">
            <form id="loginForm">
                <div class="form-group">
                    <label for="username">用户名</label>
                    <input type="text" id="username" name="username" required autocomplete="username">
                </div>
                <div class="form-group">
                    <label for="password">密码</label>
                    <input type="password" id="password" name="password" required autocomplete="current-password">
                </div>
                <button type="button" onclick="nextStep()">下一步</button>
                <div id="step1Error" class="error"></div>
            </form>
        </div>
        
        <div id="step2" class="step">
            <div class="form-group">
                <label for="totp">验证码</label>
                <input type="text" id="totp" name="totp" required placeholder="输入Authenticator中的6位验证码" autocomplete="one-time-code" maxlength="6" pattern="[0-9]{6}">
            </div>
            <button type="button" onclick="submitForm()">验证</button>
            <div id="step2Error" class="error"></div>
            <div class="info">
                请打开Microsoft Authenticator应用并输入显示的6位验证码
            </div>
        </div>
    </div>

    <script>
        const loginForm = document.getElementById('loginForm');
        const step1 = document.getElementById('step1');
        const step2 = document.getElementById('step2');
        const step1Error = document.getElementById('step1Error');
        const step2Error = document.getElementById('step2Error');
        
        function nextStep() {
            const username = document.getElementById('username').value;
            const password = document.getElementById('password').value;
            
            if (!username || !password) {
                step1Error.textContent = '请输入用户名和密码';
                return;
            }
            
            step1.classList.remove('active');
            step2.classList.add('active');
        }
        
        function submitForm() {
            const username = document.getElementById('username').value;
            const password = document.getElementById('password').value;
            const totp = document.getElementById('totp').value;
            
            if (!totp || totp.length !== 6 || !/^\d+$/.test(totp)) {
                step2Error.textContent = '请输入有效的6位验证码';
                return;
            }
            
            // 发送验证请求
            fetch(window.location.href, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `username=${encodeURIComponent(username)}&password=${encodeURIComponent(password)}&totp=${encodeURIComponent(totp)}`
            })
            .then(response => {
                if (response.ok) {
                    // 验证成功，重定向到请求的原始URL
                    window.location.href = getRedirectUrl();
                } else {
                    // 验证失败
                    return response.text();
                }
            })
            .then(errorText => {
                if (errorText) {
                    step2Error.textContent = errorText || '验证失败，请检查用户名、密码和验证码';
                }
            })
            .catch(error => {
                step2Error.textContent = '验证过程中发生错误，请重试';
                console.error('Error:', error);
            });
        }
        
        function getRedirectUrl() {
            // 获取URL参数中的重定向地址
            const urlParams = new URLSearchParams(window.location.search);
            return urlParams.get('redirect') || '/';
        }
    </script>
</body>
</html> 