<?php
// 设置错误报告
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 定义基本变量
$htpasswd_file = '/etc/nginx/auth/htpasswd';
$totp_secret_file = '/etc/nginx/totp/admin.totp';
$cookie_name = 'auth_token';
$cookie_domain = '.royeenote.online'; // 应用于所有子域
$cookie_secure = true; // 仅HTTPS
$cookie_httponly = true; // 不允许JavaScript访问
$cookie_lifetime = 3600; // 1小时

// 处理API请求
if ($_SERVER['REQUEST_METHOD'] === 'POST' && strpos($_SERVER['REQUEST_URI'], '/auth/validate') !== false) {
    // 获取POST数据
    $username = isset($_POST['username']) ? $_POST['username'] : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $totp = isset($_POST['totp']) ? $_POST['totp'] : '';
    
    // 验证必填字段
    if (empty($username) || empty($password) || empty($totp)) {
        http_response_code(400);
        echo "所有字段都是必填的";
        exit;
    }
    
    // 验证用户名和密码（使用htpasswd文件）
    if (!verify_htpasswd($username, $password, $htpasswd_file)) {
        http_response_code(401);
        echo "用户名或密码不正确";
        exit;
    }
    
    // 验证TOTP验证码
    if (!verify_totp($totp)) {
        http_response_code(401);
        echo "验证码不正确";
        exit;
    }
    
    // 认证成功，生成会话令牌
    $token = generate_token($username);
    
    // 设置Cookie
    setcookie(
        $cookie_name,
        $token,
        time() + $cookie_lifetime,
        '/',
        $cookie_domain,
        $cookie_secure,
        $cookie_httponly
    );
    
    // 返回成功
    http_response_code(200);
    echo "认证成功";
    exit;
}

// 验证htpasswd文件中的用户名和密码
function verify_htpasswd($username, $password, $htpasswd_file) {
    if (!file_exists($htpasswd_file)) {
        error_log("htpasswd文件不存在: $htpasswd_file");
        return false;
    }
    
    $lines = file($htpasswd_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    
    foreach ($lines as $line) {
        list($file_username, $file_hash) = explode(':', $line, 2);
        
        if ($file_username === $username) {
            // 检查是否是MD5格式的密码哈希（APR1）
            if (strpos($file_hash, '$apr1$') === 0) {
                // 使用系统命令验证密码
                $cmd = sprintf('echo "%s" | htpasswd -nvbi "$HTPASSWD_EMPTY_VALUE" "%s" | grep -q "%s"',
                    escapeshellarg($password),
                    escapeshellarg($username),
                    escapeshellarg($file_username)
                );
                $result = shell_exec($cmd);
                return !empty($result);
            } else {
                // 如果不是APR1格式，尝试用PHP内置函数验证
                return password_verify($password, $file_hash);
            }
        }
    }
    
    error_log("用户名不存在: $username");
    return false;
}

// 验证TOTP码
function verify_totp($totp) {
    // 简单验证：检查是否是6位数字
    if (!preg_match('/^\d{6}$/', $totp)) {
        error_log("TOTP格式不正确");
        return false;
    }
    
    // 使用命令行工具google-authenticator验证
    $cmd = sprintf('echo "%s" | google-authenticator -v -t -d -s /etc/nginx/totp/admin.totp',
        escapeshellarg($totp)
    );
    
    exec($cmd, $output, $return_code);
    error_log("TOTP验证结果: " . implode("\n", $output) . ", 返回码: $return_code");
    
    // 检查命令执行结果
    foreach ($output as $line) {
        if (strpos($line, 'Code verified successfully') !== false) {
            return true;
        }
    }
    
    return false;
}

// 生成认证令牌
function generate_token($username) {
    $secret = 'g7&kH9!pQ2@zL5$rW3*sT8#'; // 应该改为随机生成的密钥
    $expiry = time() + 3600; // 1小时后过期
    $data = $username . '|' . $expiry;
    $signature = hash_hmac('sha256', $data, $secret);
    
    return base64_encode($data . '|' . $signature);
}

// 验证令牌
function verify_token($token) {
    $secret = 'g7&kH9!pQ2@zL5$rW3*sT8#'; // 必须与生成令牌时使用的相同
    
    $decoded = base64_decode($token);
    list($data, $signature) = explode('|', $decoded, 2);
    
    // 验证签名
    $expected_signature = hash_hmac('sha256', $data, $secret);
    if (!hash_equals($expected_signature, $signature)) {
        return false;
    }
    
    // 检查是否过期
    list($username, $expiry) = explode('|', $data, 2);
    if (time() > $expiry) {
        return false;
    }
    
    return $username;
}

// 如果不是API请求，则显示登录页面
include 'auth.html';
?> 