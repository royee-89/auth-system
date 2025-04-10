#!/bin/bash
# clash.royeenote.online 子域名配置脚本

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
DOMAIN="clash.royeenote.online"
NGINX_CONF_DIR="/etc/nginx/conf.d"
NGINX_CONF_FILE="${NGINX_CONF_DIR}/${DOMAIN}.conf"
EMAIL="royeeee@outlook.com" # 用于Let's Encrypt证书申请

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
cp /var/www/auth.royeenote.online/public_html/nginx/clash.royeenote.online.conf $NGINX_CONF_FILE
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

# 完成
print_message "============================================="
print_message "clash.royeenote.online 子域名配置完成！"
print_message "现在可以通过 https://$DOMAIN 访问服务"
print_message "请确保n8n或clash服务已在本地运行并监听正确端口"
print_message "============================================="

exit 0 