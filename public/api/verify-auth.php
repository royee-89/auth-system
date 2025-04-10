<?php
/**
 * 认证验证API
 * 
 * 这个接口用于Nginx的auth_request模块，验证用户是否已登录
 * 返回200表示已认证，401表示未认证
 */

// 启动会话
session_start();

// 允许的源站列表，用于CORS
$allowedOrigins = [
    'https://auth.royeenote.online',
    'https://admin.royeenote.online'
];

// 设置CORS头
$origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '';
if (in_array($origin, $allowedOrigins)) {
    header("Access-Control-Allow-Origin: $origin");
    header('Access-Control-Allow-Credentials: true');
}

// 检查认证状态
function isAuthenticated() {
    // 检查会话中是否存在auth_token
    if (isset($_SESSION['auth_token']) && !empty($_SESSION['auth_token'])) {
        // 检查token是否有效（这里可以添加更多验证逻辑）
        $token = $_SESSION['auth_token'];
        
        // 这里可以添加验证token的逻辑，如检查数据库中是否存在、是否过期等
        // 示例：检查token是否过期
        if (isset($_SESSION['auth_expiry']) && $_SESSION['auth_expiry'] > time()) {
            // Token有效
            return true;
        }
    }
    
    // 检查Cookie中是否有永久登录标记
    if (isset($_COOKIE['auth_remember']) && !empty($_COOKIE['auth_remember'])) {
        $token = $_COOKIE['auth_remember'];
        
        // 验证持久化token的逻辑
        // 这里应该从数据库验证token
        
        // 如果cookie中的token有效，重新设置会话
        // $_SESSION['auth_token'] = $token;
        // $_SESSION['auth_expiry'] = time() + 3600; // 1小时后过期
        // return true;
    }
    
    // 未通过任何认证方式
    return false;
}

// 检查用户是否有指定角色
function hasRole($role) {
    // 这里应该从会话或数据库中获取用户的角色
    // 简单示例：假设管理员角色已保存在会话中
    if (isset($_SESSION['user_roles']) && is_array($_SESSION['user_roles'])) {
        return in_array($role, $_SESSION['user_roles']);
    }
    
    // 如果是管理员测试账号（根据实际情况修改）
    if (isset($_SESSION['username']) && $_SESSION['username'] === 'admin') {
        return true;
    }
    
    return false;
}

// 检查用户是否有权限访问特定的资源
function hasPermission($resource, $requiredRole = null) {
    // 首先检查用户是否已登录
    if (!isAuthenticated()) {
        return false;
    }
    
    // 如果需要特定角色，检查用户是否有该角色
    if ($requiredRole !== null) {
        return hasRole($requiredRole);
    }
    
    // 针对特定资源的权限检查
    switch ($resource) {
        case 'admin':
            return hasRole('admin');
        case 'clash':
        case 'n8n':
            // 可以设定这些资源需要的角色
            return hasRole('admin') || hasRole('power_user');
        case 'mfa':
            // MFA管理可能只允许管理员访问
            return hasRole('admin');
        default:
            // 默认情况，仅验证登录状态
            return true;
    }
}

// 获取请求的原始URI和头信息（由Nginx auth_request模块传递）
$requestUri = isset($_SERVER['HTTP_X_ORIGINAL_URI']) ? $_SERVER['HTTP_X_ORIGINAL_URI'] : '';
$requestHost = isset($_SERVER['HTTP_X_ORIGINAL_HOST']) ? $_SERVER['HTTP_X_ORIGINAL_HOST'] : '';
$requiredRole = isset($_SERVER['HTTP_X_REQUIRED_ROLE']) ? $_SERVER['HTTP_X_REQUIRED_ROLE'] : null;

// 根据请求路径确定所需资源
$resource = 'default';
if (strpos($requestHost, 'admin.royeenote.online') !== false) {
    $resource = 'admin';
    
    // 细分admin子路径
    if (strpos($requestUri, '/clash/') === 0) {
        $resource = 'clash';
    } elseif (strpos($requestUri, '/n8n/') === 0) {
        $resource = 'n8n';
    } elseif (strpos($requestUri, '/mfa/') === 0) {
        $resource = 'mfa';
    }
}

// 检查认证和权限
if (isAuthenticated() && hasPermission($resource, $requiredRole)) {
    // 认证成功，返回200 OK
    http_response_code(200);
    exit;
} else {
    // 认证失败，返回401 Unauthorized
    http_response_code(401);
    exit;
} 