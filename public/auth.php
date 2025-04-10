<?php
// 设置错误报告
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 定义基本变量
$htpasswd_file = '/etc/nginx/auth/htpasswd';
$totp_secret = 'C4Q3WENWQXGSRK7U7ND2J5U5LE'; // 从Google Authenticator生成的密钥
$cookie_name = 'auth_token';
$cookie_domain = '.royeenote.online'; // 应用于所有子域
$cookie_secure = true; // 仅HTTPS
$cookie_httponly = true; // 不允许JavaScript访问
$cookie_lifetime = 3600; // 1小时

// 处理API请求
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_SERVER['REQUEST_URI'] === '/auth/validate') {
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
    if (!verify_totp($totp, $totp_secret)) {
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
        return false;
    }
    
    $lines = file($htpasswd_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    
    foreach ($lines as $line) {
        list($file_username, $file_hash) = explode(':', $line, 2);
        
        if ($file_username === $username) {
            return password_verify($password, $file_hash);
        }
    }
    
    return false;
}

// 验证TOTP码
function verify_totp($totp, $secret) {
    // 此函数需要PHP TOTP库，这里简化为检查是否为6位数字
    if (!preg_match('/^\d{6}$/', $totp)) {
        return false;
    }
    
    // 调用Google Authenticator命令行验证TOTP
    $command = sprintf(
        'echo "%s" | google-authenticator -t -d -f -r 3 -R 30 -w 3 -s /etc/nginx/totp/admin.totp -v',
        escapeshellarg($totp)
    );
    
    exec($command, $output, $return_code);
    
    // 如果命令成功执行并返回0，则验证成功
    return $return_code === 0;
}

// 生成认证令牌
function generate_token($username) {
    $secret = 'change_this_to_a_random_secret_key'; // 在生产环境中使用随机生成的密钥
    $expiry = time() + 3600; // 1小时后过期
    $data = $username . '|' . $expiry;
    $signature = hash_hmac('sha256', $data, $secret);
    
    return base64_encode($data . '|' . $signature);
}

// 验证令牌
function verify_token($token) {
    $secret = 'change_this_to_a_random_secret_key'; // 必须与生成令牌时使用的相同
    
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