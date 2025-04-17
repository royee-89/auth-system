<?php
/**
 * 系统配置文件
 * 
 * 这个文件集中管理所有系统配置，包括域名、路径、认证设置等
 */

return [
    // 域名设置
    'domain' => [
        'base' => 'tinotools.cn',
        'cookie_domain' => '.tinotools.cn',
        'auth' => 'auth.tinotools.cn',
        'admin' => 'admin.tinotools.cn',
        'n8n' => 'n8n.tinotools.cn',
        'clash' => 'clash.tinotools.cn',
    ],
    
    // Cookie设置
    'cookie' => [
        'name' => 'auth_token',
        'secure' => true,
        'httponly' => true,
        'lifetime' => 3600, // 1小时
    ],
    
    // 认证设置
    'auth' => [
        'username' => 'admin',
        'password' => 'Kevin2189..', // 实际环境应存储哈希值，而非明文
        'totp_required' => true,
    ],
    
    // 路径设置
    'paths' => [
        'root' => '/www/wwwroot/tinotools',
        'auth' => '/www/wwwroot/tinotools/auth/public_html',
        'admin' => '/www/wwwroot/tinotools/admin/public_html',
        'logs' => '/var/log/auth',
    ],
    
    // 日志设置
    'logging' => [
        'enabled' => true,
        'level' => 'debug', // debug, info, warning, error
    ],
]; 