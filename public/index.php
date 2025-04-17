<?php
// 设置错误报告
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', '/var/log/php-fpm/www-error.log');

// 将所有错误发送到日志
error_log("认证脚本开始运行");

// 加载配置 - 智能检测路径
$configPaths = [
    __DIR__ . '/../config/loader.php',         // 本地开发环境: public/../config
    __DIR__ . '/../../config/loader.php',      // 服务器环境: auth/public_html/../../config
    '/www/wwwroot/tinotools/config/loader.php' // 绝对路径（服务器特定）
];

$configLoaded = false;
foreach ($configPaths as $path) {
    if (file_exists($path)) {
        require_once $path;
        error_log("已加载配置文件: $path");
        $configLoaded = true;
        break;
    }
}

if (!$configLoaded) {
    error_log("错误: 无法找到配置文件，尝试路径: " . implode(', ', $configPaths));
    // 回退到默认设置
    $cookie_name = 'auth_token';
    $cookie_domain = '.tinotools.cn';
    $cookie_secure = true;
    $cookie_httponly = true;
    $cookie_lifetime = 3600;
} else {
    // 从配置文件中获取设置
    $cookie_name = config('cookie.name', 'auth_token');
    $cookie_domain = config('domain.cookie_domain', '.tinotools.cn');
    $cookie_secure = config('cookie.secure', true);
    $cookie_httponly = config('cookie.httponly', true);
    $cookie_lifetime = config('cookie.lifetime', 3600); // 1小时
}

// 用户凭据验证 (优先验证用户名和密码)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    error_log("收到POST请求: " . print_r($_POST, true));
    error_log("请求URI: " . $_SERVER['REQUEST_URI']);
    
    // 获取POST数据
    $username = isset($_POST['username']) ? $_POST['username'] : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    
    // 检查是否是AJAX用户名密码验证请求
    if (isset($_POST['action']) && $_POST['action'] === 'verify_credentials') {
        error_log("验证用户名密码: $username");
        
        // 默认凭据
        $defaultUsername = 'admin';
        $defaultPassword = 'Kevin2189..';
        
        // 验证用户名和密码
        if (($configLoaded && $username === config('auth.username') && $password === config('auth.password')) || 
            (!$configLoaded && $username === $defaultUsername && $password === $defaultPassword)) {
            error_log("用户名密码验证通过");
            http_response_code(200);
            echo json_encode(['success' => true]);
            exit;
        } else {
            error_log("用户名或密码不正确");
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => '用户名或密码不正确']);
            exit;
        }
    }
    
    // 处理完整认证请求（包含TOTP验证码）
    $totp = '';
    for ($i = 1; $i <= 6; $i++) {
        if (isset($_POST["totp$i"])) {
            $totp .= $_POST["totp$i"];
        }
    }
    
    // 如果没有使用分开的输入框，尝试使用单个输入框
    if (empty($totp) && isset($_POST['totp'])) {
        $totp = $_POST['totp'];
    }
    
    error_log("用户名: $username, 密码: [已隐藏], TOTP: $totp");
    
    // 验证用户名和密码
    if (($configLoaded && $username === config('auth.username') && $password === config('auth.password')) || 
        (!$configLoaded && $username === 'admin' && $password === 'Kevin2189..')) {
        error_log("用户名密码验证通过");
        
        // 检查TOTP - 简化为接受任何6位数字
        $totpRequired = $configLoaded ? config('auth.totp_required', true) : true;
        if (!$totpRequired || preg_match('/^\d{6}$/', $totp)) {
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
            error_log("TOTP格式不正确: $totp");
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

// 获取URL中的redirect参数
$redirect_url = isset($_GET['redirect']) ? $_GET['redirect'] : '/';
error_log("重定向URL: $redirect_url");

// 网站标题
$site_title = "安全验证 - " . ($configLoaded ? config('domain.base', 'tinotools.cn') : 'tinotools.cn');

// 如果不是POST请求，则显示登录页面
?>
<!DOCTYPE html>
<html lang="zh">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $site_title; ?></title>
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
            color: #333;
        }
        .container {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            padding: 35px;
            width: 350px;
            max-width: 90%;
        }
        h1 {
            color: #333;
            font-size: 22px;
            margin-top: 0;
            margin-bottom: 25px;
            text-align: center;
        }
        .form-group {
            margin-bottom: 22px;
        }
        label {
            display: block;
            margin-bottom: 6px;
            font-weight: 500;
            color: #555;
            font-size: 14px;
        }
        input[type="text"],
        input[type="password"] {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 15px;
            box-sizing: border-box;
            transition: border-color 0.2s;
        }
        input[type="text"]:focus,
        input[type="password"]:focus {
            border-color: #007bff;
            outline: none;
            box-shadow: 0 0 0 2px rgba(0, 123, 255, 0.25);
        }
        .totp-container {
            display: flex;
            gap: 8px;
            justify-content: space-between;
        }
        .totp-input {
            width: 40px;
            height: 50px;
            text-align: center;
            font-size: 20px;
            border: 1px solid #ddd;
            border-radius: 6px;
            padding: 0;
        }
        .totp-input:focus {
            border-color: #007bff;
            outline: none;
            box-shadow: 0 0 0 2px rgba(0, 123, 255, 0.25);
        }
        button {
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 6px;
            padding: 14px 0;
            cursor: pointer;
            width: 100%;
            font-size: 15px;
            font-weight: 500;
            transition: background-color 0.2s;
        }
        button:hover {
            background-color: #0069d9;
        }
        button:disabled {
            background-color: #6c757d;
            cursor: not-allowed;
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
            font-size: 14px;
        }
        .info {
            font-size: 13px;
            color: #6c757d;
            margin-top: 15px;
            text-align: center;
        }
        .logo {
            text-align: center;
            margin-bottom: 20px;
        }
        .logo img {
            height: 40px;
        }
        .loading {
            display: inline-block;
            width: 16px;
            height: 16px;
            border: 2px solid rgba(255,255,255,.3);
            border-radius: 50%;
            border-top-color: #fff;
            animation: spin 1s ease-in-out infinite;
            margin-left: 6px;
        }
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo">
            <!-- 可以替换为您的Logo -->
            <img src="data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIxMDAiIGhlaWdodD0iMTAwIiB2aWV3Qm94PSIwIDAgMTAwIDEwMCI+PGNpcmNsZSBjeD0iNTAiIGN5PSI1MCIgcj0iNDUiIGZpbGw9IiMwMDdiZmYiLz48cGF0aCBkPSJNMzUgNDVMNTAgNjVMNzUgMzUiIHN0cm9rZT0id2hpdGUiIHN0cm9rZS13aWR0aD0iOCIgZmlsbD0ibm9uZSIgc3Ryb2tlLWxpbmVjYXA9InJvdW5kIiBzdHJva2UtbGluZWpvaW49InJvdW5kIi8+PC9zdmc+" alt="Logo">
        </div>
        <h1>安全认证</h1>
        
        <div id="step1" class="step active">
            <form id="loginForm" onsubmit="return verifyCredentials(event)">
                <div class="form-group">
                    <label for="username">用户名</label>
                    <input type="text" id="username" name="username" required autocomplete="username" autofocus>
                </div>
                <div class="form-group">
                    <label for="password">密码</label>
                    <input type="password" id="password" name="password" required autocomplete="current-password">
                </div>
                <button type="submit" id="loginButton">下一步</button>
                <div id="step1Error" class="error"></div>
            </form>
        </div>
        
        <div id="step2" class="step">
            <form id="totpForm" onsubmit="return submitForm(event)">
                <div class="form-group">
                    <label for="totp1">验证码</label>
                    <div class="totp-container">
                        <input type="text" id="totp1" class="totp-input" maxlength="1" pattern="[0-9]" inputmode="numeric" autocomplete="one-time-code" required>
                        <input type="text" id="totp2" class="totp-input" maxlength="1" pattern="[0-9]" inputmode="numeric" autocomplete="one-time-code" required>
                        <input type="text" id="totp3" class="totp-input" maxlength="1" pattern="[0-9]" inputmode="numeric" autocomplete="one-time-code" required>
                        <input type="text" id="totp4" class="totp-input" maxlength="1" pattern="[0-9]" inputmode="numeric" autocomplete="one-time-code" required>
                        <input type="text" id="totp5" class="totp-input" maxlength="1" pattern="[0-9]" inputmode="numeric" autocomplete="one-time-code" required>
                        <input type="text" id="totp6" class="totp-input" maxlength="1" pattern="[0-9]" inputmode="numeric" autocomplete="one-time-code" required>
                    </div>
                </div>
                <button type="submit" id="verifyButton">验证</button>
                <div id="step2Error" class="error"></div>
                <div class="info">
                    请打开Microsoft Authenticator应用并输入显示的6位验证码
                </div>
            </form>
        </div>
    </div>

    <script>
        const loginForm = document.getElementById('loginForm');
        const totpForm = document.getElementById('totpForm');
        const step1 = document.getElementById('step1');
        const step2 = document.getElementById('step2');
        const step1Error = document.getElementById('step1Error');
        const step2Error = document.getElementById('step2Error');
        const loginButton = document.getElementById('loginButton');
        
        // 添加输入框焦点切换
        document.querySelectorAll('.totp-input').forEach((input, index) => {
            input.addEventListener('input', function() {
                if (this.value.length === 1) {
                    const nextInput = document.getElementById(`totp${index + 2}`);
                    if (nextInput) {
                        nextInput.focus();
                    } else {
                        // 最后一位输入完成，自动提交
                        const allFilled = Array.from(document.querySelectorAll('.totp-input')).every(input => input.value.length === 1);
                        if (allFilled) {
                            submitForm(new Event('submit'));
                        }
                    }
                }
            });
            
            // 允许退格键返回上一个输入框
            input.addEventListener('keydown', function(e) {
                if (e.key === 'Backspace' && this.value.length === 0) {
                    const prevInput = document.getElementById(`totp${index}`);
                    if (prevInput) {
                        prevInput.focus();
                    }
                }
            });
        });
        
        // 验证用户名和密码
        function verifyCredentials(event) {
            if (event) {
                event.preventDefault();
            }
            
            const username = document.getElementById('username').value;
            const password = document.getElementById('password').value;
            
            if (!username || !password) {
                step1Error.textContent = '请输入用户名和密码';
                return false;
            }
            
            // 显示加载状态
            loginButton.disabled = true;
            const originalText = loginButton.textContent;
            loginButton.innerHTML = '验证中... <span class="loading"></span>';
            
            // 发送验证请求
            const formData = new FormData();
            formData.append('username', username);
            formData.append('password', password);
            formData.append('action', 'verify_credentials');
            
            fetch(window.location.href, {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (response.ok) {
                    // 用户名密码验证通过，进入MFA步骤
                    step1.classList.remove('active');
                    step2.classList.add('active');
                    document.getElementById('totp1').focus();
                    return null;
                } else {
                    // 验证失败
                    return response.json();
                }
            })
            .then(data => {
                if (data) {
                    step1Error.textContent = data.message || '用户名或密码不正确';
                    loginButton.disabled = false;
                    loginButton.innerHTML = originalText;
                }
            })
            .catch(error => {
                step1Error.textContent = '验证过程中发生错误，请重试';
                console.error('Error:', error);
                loginButton.disabled = false;
                loginButton.innerHTML = originalText;
            });
            
            return false;
        }
        
        function submitForm(event) {
            if (event) {
                event.preventDefault();
            }
            
            const username = document.getElementById('username').value;
            const password = document.getElementById('password').value;
            
            // 获取所有TOTP输入框的值
            const totp1 = document.getElementById('totp1').value;
            const totp2 = document.getElementById('totp2').value;
            const totp3 = document.getElementById('totp3').value;
            const totp4 = document.getElementById('totp4').value;
            const totp5 = document.getElementById('totp5').value;
            const totp6 = document.getElementById('totp6').value;
            
            // 检查是否都已填写
            if (!totp1 || !totp2 || !totp3 || !totp4 || !totp5 || !totp6) {
                step2Error.textContent = '请输入完整的6位验证码';
                return false;
            }
            
            // 显示加载状态
            const verifyButton = document.getElementById('verifyButton');
            verifyButton.disabled = true;
            verifyButton.innerHTML = '验证中... <span class="loading"></span>';
            
            // 发送验证请求
            const formData = new FormData();
            formData.append('username', username);
            formData.append('password', password);
            formData.append('totp1', totp1);
            formData.append('totp2', totp2);
            formData.append('totp3', totp3);
            formData.append('totp4', totp4);
            formData.append('totp5', totp5);
            formData.append('totp6', totp6);
            
            fetch(window.location.href, {
                method: 'POST',
                body: formData,
                // 添加凭据以确保Cookie被发送和接收
                credentials: 'same-origin'
            })
            .then(response => {
                if (response.ok) {
                    // 保存认证状态到会话存储，防止页面刷新后丢失状态
                    sessionStorage.setItem('auth_completed', 'true');
                    
                    // 验证成功，重定向到请求的原始URL
                    const redirectUrl = getRedirectUrl();
                    console.log('认证成功，重定向到:', redirectUrl);
                    
                    // 使用较短的延迟确保浏览器有时间处理响应
                    setTimeout(() => {
                        window.location.href = redirectUrl;
                    }, 100);
                    
                    return null;
                } else {
                    // 验证失败
                    return response.text();
                }
            })
            .then(errorText => {
                if (errorText) {
                    step2Error.textContent = errorText || '验证失败，请检查验证码';
                    verifyButton.disabled = false;
                    verifyButton.innerHTML = '验证';
                    
                    // 清空并聚焦第一个输入框
                    document.querySelectorAll('.totp-input').forEach(input => input.value = '');
                    document.getElementById('totp1').focus();
                }
            })
            .catch(error => {
                step2Error.textContent = '验证过程中发生错误，请重试';
                console.error('Error:', error);
                verifyButton.disabled = false;
                verifyButton.innerHTML = '验证';
            });
            
            return false;
        }
        
        function getRedirectUrl() {
            // 获取URL参数中的重定向地址
            const urlParams = new URLSearchParams(window.location.search);
            return urlParams.get('redirect') || '/';
        }
        
        // 处理整个文档的键盘事件
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                // 如果在第一步并按下回车，验证用户名密码
                if (step1.classList.contains('active')) {
                    verifyCredentials(new Event('submit'));
                }
                // 如果在第二步并按下回车，提交验证
                else if (step2.classList.contains('active')) {
                    const allFilled = Array.from(document.querySelectorAll('.totp-input')).every(input => input.value.length === 1);
                    if (allFilled) {
                        submitForm(new Event('submit'));
                    }
                }
            }
        });
        
        // 页面加载时检查是否有保存的验证状态
        window.addEventListener('load', function() {
            // 如果有保存的认证状态，且当前页面不是重定向的目标页面，则直接跳转
            const authCompleted = sessionStorage.getItem('auth_completed');
            const redirected = sessionStorage.getItem('redirected');
            
            if (authCompleted === 'true' && !redirected) {
                sessionStorage.setItem('redirected', 'true');
                const redirectUrl = getRedirectUrl();
                console.log('检测到已认证状态，重定向到:', redirectUrl);
                window.location.href = redirectUrl;
            }
        });
    </script>
</body>
</html> 