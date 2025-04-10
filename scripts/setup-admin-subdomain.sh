#!/bin/bash
# admin.royeenote.online 后台管理子域名配置脚本

# 确保脚本在出错时停止
set -e

# 颜色定义
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

# 打印带颜色的消息
print_message() {
  echo -e "${GREEN}[INFO]${NC} $1"
}

print_warning() {
  echo -e "${YELLOW}[WARNING]${NC} $1"
}

print_error() {
  echo -e "${RED}[ERROR]${NC} $1"
}

# 检查是否以root用户运行
if [ "$(id -u)" != "0" ]; then
  print_error "此脚本必须以root用户运行"
  exit 1
fi

# 配置变量
DOMAIN="admin.royeenote.online"
NGINX_CONF_DIR="/etc/nginx/conf.d"
NGINX_CONF_FILE="${NGINX_CONF_DIR}/${DOMAIN}.conf"
EMAIL="royeeee@outlook.com" # 用于Let's Encrypt证书申请
ADMIN_PORT=8080  # 后台管理系统端口

# 1. 确保Nginx已安装
print_message "检查Nginx是否已安装..."
if ! command -v nginx &> /dev/null; then
  print_warning "Nginx未安装，正在安装..."
  apt update && apt install -y nginx
  systemctl enable nginx
  systemctl start nginx
else
  print_message "Nginx已安装"
fi

# 2. 确保Certbot已安装
print_message "检查Certbot是否已安装..."
if ! command -v certbot &> /dev/null; then
  print_warning "Certbot未安装，正在安装..."
  apt update && apt install -y certbot python3-certbot-nginx
else
  print_message "Certbot已安装"
fi

# 3. 创建Nginx配置文件
print_message "创建Nginx配置文件..."
cp /var/www/auth.royeenote.online/public_html/nginx/admin.royeenote.online.conf $NGINX_CONF_FILE
print_message "已创建Nginx配置文件: $NGINX_CONF_FILE"

# 4. 配置DNS（用户需手动操作）
print_warning "请确保已为 $DOMAIN 创建DNS A记录，指向服务器IP"
read -p "DNS记录已配置？(y/n): " dns_configured
if [ "$dns_configured" != "y" ]; then
  print_error "请先配置DNS记录，然后再运行此脚本"
  exit 1
fi

# 5. 申请Let's Encrypt SSL证书
print_message "正在申请Let's Encrypt SSL证书..."
certbot --nginx -d $DOMAIN --non-interactive --agree-tos -m $EMAIL

# 6. 测试Nginx配置
print_message "测试Nginx配置..."
nginx -t

# 7. 重启Nginx
print_message "重启Nginx服务..."
systemctl restart nginx

# 8. 设置证书自动续期
print_message "设置证书自动续期..."
(crontab -l 2>/dev/null; echo "0 3 * * * /usr/bin/certbot renew --quiet") | crontab -

# 9. 修改文件权限
print_message "修改API目录权限..."
chown -R www-data:www-data /var/www/auth.royeenote.online/public_html/api
chmod -R 755 /var/www/auth.royeenote.online/public_html/api

# 10. 创建后台管理系统初始结构
print_message "创建后台管理系统基础结构..."
mkdir -p /var/www/admin.royeenote.online/public_html
cat > /var/www/admin.royeenote.online/public_html/index.html << 'EOF'
<!DOCTYPE html>
<html lang="zh">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>管理后台 - royeenote.online</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen, Ubuntu, sans-serif;
            margin: 0;
            padding: 0;
            color: #333;
            background-color: #f8f9fa;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        header {
            background-color: #343a40;
            color: white;
            padding: 20px 0;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        header .container {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .brand {
            font-size: 24px;
            font-weight: bold;
        }
        .user-info {
            display: flex;
            align-items: center;
        }
        .user-info img {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            margin-right: 10px;
        }
        .modules {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            grid-gap: 20px;
            margin-top: 30px;
        }
        .module-card {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .module-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.15);
        }
        .module-header {
            padding: 20px;
            background-color: #007bff;
            color: white;
            font-weight: bold;
        }
        .module-body {
            padding: 20px;
        }
        .module-body p {
            margin: 0 0 15px;
            color: #6c757d;
        }
        .btn {
            display: inline-block;
            background-color: #007bff;
            color: white;
            text-decoration: none;
            padding: 8px 16px;
            border-radius: 4px;
            transition: background-color 0.2s;
        }
        .btn:hover {
            background-color: #0069d9;
        }
    </style>
</head>
<body>
    <header>
        <div class="container">
            <div class="brand">管理后台</div>
            <div class="user-info">
                <img src="data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIyNCIgaGVpZ2h0PSIyNCIgdmlld0JveD0iMCAwIDI0IDI0IiBmaWxsPSJub25lIiBzdHJva2U9ImN1cnJlbnRDb2xvciIgc3Ryb2tlLXdpZHRoPSIyIiBzdHJva2UtbGluZWNhcD0icm91bmQiIHN0cm9rZS1saW5lam9pbj0icm91bmQiIGNsYXNzPSJmZWF0aGVyIGZlYXRoZXItdXNlciI+PHBhdGggZD0iTTIwIDIxdi0yYTQgNCAwIDAgMC00LTRINGE0IDQgMCAwIDAtNCA0djIiPjwvcGF0aD48Y2lyY2xlIGN4PSIxMiIgY3k9IjciIHI9IjQiPjwvY2lyY2xlPjwvc3ZnPg==" alt="User">
                <span>管理员</span>
            </div>
        </div>
    </header>
    
    <div class="container">
        <div class="modules">
            <div class="module-card">
                <div class="module-header">Clash 节点管理</div>
                <div class="module-body">
                    <p>管理和切换Clash节点，监控节点状态，查看连接日志</p>
                    <a href="/clash/" class="btn">进入管理</a>
                </div>
            </div>
            
            <div class="module-card">
                <div class="module-header">n8n 工作流</div>
                <div class="module-body">
                    <p>管理自动化工作流，创建和监控n8n任务执行</p>
                    <a href="/n8n/" class="btn">进入管理</a>
                </div>
            </div>
            
            <div class="module-card">
                <div class="module-header">MFA 用户管理</div>
                <div class="module-body">
                    <p>管理多因素认证设备，用户权限设置，查看登录日志</p>
                    <a href="/mfa/" class="btn">进入管理</a>
                </div>
            </div>
            
            <div class="module-card">
                <div class="module-header">系统设置</div>
                <div class="module-body">
                    <p>配置系统参数，备份与恢复，日志管理</p>
                    <a href="/settings/" class="btn">进入设置</a>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // 简单前端脚本，可以在此处添加交互功能
        document.addEventListener('DOMContentLoaded', function() {
            console.log('管理后台已加载');
        });
    </script>
</body>
</html>
EOF

# 设置权限
chown -R www-data:www-data /var/www/admin.royeenote.online/public_html
chmod -R 755 /var/www/admin.royeenote.online/public_html

# 11. 创建临时后台服务
print_message "创建临时后台服务以测试配置..."
cat > /etc/systemd/system/admin-dashboard.service << EOF
[Unit]
Description=Temporary Admin Dashboard Service
After=network.target

[Service]
Type=simple
User=www-data
WorkingDirectory=/var/www/admin.royeenote.online/public_html
ExecStart=$(which python3) -m http.server ${ADMIN_PORT}
Restart=on-failure

[Install]
WantedBy=multi-user.target
EOF

# 启动服务
systemctl daemon-reload
systemctl enable admin-dashboard
systemctl start admin-dashboard

# 完成
print_message "============================================="
print_message "admin.royeenote.online 子域名配置完成！"
print_message "现在可以通过 https://$DOMAIN 访问后台管理系统"
print_message "已创建临时Python服务用于测试，稍后应替换为正式后台应用"
print_message "============================================="

exit 0 