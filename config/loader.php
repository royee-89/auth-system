<?php
/**
 * 配置加载器
 * 
 * 这个文件提供了加载和使用配置的功能
 */

class Config {
    private static $instance = null;
    private $config = [];
    
    /**
     * 私有构造函数，防止直接创建实例
     */
    private function __construct() {
        $configPath = __DIR__ . '/config.php';
        
        if (file_exists($configPath)) {
            $this->config = require $configPath;
        } else {
            die('配置文件不存在');
        }
        
        // 加载环境变量覆盖配置（如果存在）
        $envConfig = $this->loadEnvConfig();
        if (!empty($envConfig)) {
            $this->config = array_merge_recursive($this->config, $envConfig);
        }
    }
    
    /**
     * 获取配置实例（单例模式）
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        
        return self::$instance;
    }
    
    /**
     * 获取配置项，支持点符号访问嵌套属性
     * 
     * @param string $key      配置键，如 'domain.base'
     * @param mixed  $default  默认值，若配置不存在则返回此值
     * @return mixed
     */
    public function get($key, $default = null) {
        $parts = explode('.', $key);
        $value = $this->config;
        
        foreach ($parts as $part) {
            if (!isset($value[$part])) {
                return $default;
            }
            
            $value = $value[$part];
        }
        
        return $value;
    }
    
    /**
     * 设置配置项，支持点符号设置嵌套属性
     * 
     * @param string $key    配置键
     * @param mixed  $value  配置值
     */
    public function set($key, $value) {
        $parts = explode('.', $key);
        $config = &$this->config;
        
        foreach ($parts as $i => $part) {
            if ($i === count($parts) - 1) {
                $config[$part] = $value;
            } else {
                if (!isset($config[$part]) || !is_array($config[$part])) {
                    $config[$part] = [];
                }
                
                $config = &$config[$part];
            }
        }
    }
    
    /**
     * 获取整个配置数组
     */
    public function all() {
        return $this->config;
    }
    
    /**
     * 从环境变量加载配置（覆盖默认配置）
     */
    private function loadEnvConfig() {
        $envConfig = [];
        
        // 尝试加载.env文件
        if (file_exists(__DIR__ . '/../.env')) {
            $lines = file(__DIR__ . '/../.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            
            foreach ($lines as $line) {
                if (strpos($line, '=') !== false && substr($line, 0, 1) !== '#') {
                    list($key, $value) = explode('=', $line, 2);
                    putenv(trim($key) . '=' . trim($value));
                }
            }
        }
        
        // 从环境变量加载域名配置
        if (getenv('DOMAIN_BASE')) {
            $envConfig['domain']['base'] = getenv('DOMAIN_BASE');
            $envConfig['domain']['cookie_domain'] = '.' . getenv('DOMAIN_BASE');
            $envConfig['domain']['auth'] = 'auth.' . getenv('DOMAIN_BASE');
            $envConfig['domain']['admin'] = 'admin.' . getenv('DOMAIN_BASE');
            $envConfig['domain']['n8n'] = 'n8n.' . getenv('DOMAIN_BASE');
        }
        
        return $envConfig;
    }
}

// 创建一个全局配置访问函数
if (!function_exists('config')) {
    /**
     * 获取配置值
     * 
     * @param string $key      配置键，支持点符号
     * @param mixed  $default  默认值
     * @return mixed
     */
    function config($key = null, $default = null) {
        $config = Config::getInstance();
        
        if ($key === null) {
            return $config->all();
        }
        
        return $config->get($key, $default);
    }
} 