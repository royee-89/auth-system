#!/bin/bash

# 颜色定义
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

# 检查是否以root运行
if [ "$EUID" -ne 0 ]; then
    echo -e "${RED}请以root用户运行此脚本${NC}"
    exit 1
fi

# 设置变量
BASE_DIR="/www/wwwroot/tinotools"
AUTH_DIR="$BASE_DIR/auth"
ADMIN_DIR="$BASE_DIR/admin"
CONFIG_DIR="$BASE_DIR/config"
LOG_DIR="/var/log/auth"

# 设置域名
read -p "请输入您的域名 (默认: tinotools.cn): " DOMAIN
DOMAIN=${DOMAIN:-tinotools.cn}

echo -e "${YELLOW}=== 创建必要的目录 ===${NC}"
mkdir -p $AUTH_DIR/public_html
mkdir -p $ADMIN_DIR/public_html
mkdir -p $CONFIG_DIR
mkdir -p $LOG_DIR

# 修改目录权限
echo -e "${YELLOW}=== 设置目录权限 ===${NC}"
chown -R nginx:nginx $BASE_DIR
chmod -R 755 $BASE_DIR
chmod -R 644 $LOG_DIR

# 复制配置文件
echo -e "${YELLOW}=== 复制配置文件 ===${NC}"
cp /tmp/config.php $CONFIG_DIR/
cp /tmp/loader.php $CONFIG_DIR/

# 如果.env.example存在，创建.env文件
if [ -f "/tmp/.env.example" ]; then
    echo -e "${YELLOW}=== 创建.env文件 ===${NC}"
    cp /tmp/.env.example $BASE_DIR/.env
    sed -i "s/tinotools.cn/$DOMAIN/g" $BASE_DIR/.env
    echo -e "${GREEN}=== .env文件已创建，请编辑 $BASE_DIR/.env 配置您的环境 ===${NC}"
fi

# 更新index.php
if [ -f "/tmp/index.php.update" ]; then
    echo -e "${YELLOW}=== 更新index.php文件 ===${NC}"
    cp /tmp/index.php.update $AUTH_DIR/public_html/index.php
    chown nginx:nginx $AUTH_DIR/public_html/index.php
    chmod 644 $AUTH_DIR/public_html/index.php
    echo -e "${GREEN}=== index.php已更新 ===${NC}"
fi

# 更新Nginx配置
echo -e "${YELLOW}=== 更新Nginx配置 ===${NC}"
sed -i "s/tinotools.cn/$DOMAIN/g" /etc/nginx/conf.d/auth.${DOMAIN}.conf
sed -i "s/tinotools.cn/$DOMAIN/g" /etc/nginx/conf.d/admin.${DOMAIN}.conf
sed -i "s/tinotools.cn/$DOMAIN/g" /etc/nginx/conf.d/n8n.${DOMAIN}.conf

# 重新加载Nginx
echo -e "${YELLOW}=== 重新加载Nginx配置 ===${NC}"
nginx -t
if [ $? -eq 0 ]; then
    systemctl reload nginx
    echo -e "${GREEN}=== Nginx配置已重新加载 ===${NC}"
else
    echo -e "${RED}=== Nginx配置有误，请检查配置文件 ===${NC}"
    exit 1
fi

echo -e "${GREEN}=== 安装完成！ ===${NC}"
echo -e "${YELLOW}请访问以下地址测试您的安装:${NC}"
echo -e "  - 认证系统: https://auth.$DOMAIN"
echo -e "  - 管理后台: https://admin.$DOMAIN"
echo -e "  - N8N服务: https://n8n.$DOMAIN"
echo ""
echo -e "${YELLOW}重要提示:${NC}"
echo -e "1. 默认用户名密码为admin/PASSWORD，请立即修改"
echo -e "2. 请检查并配置 $BASE_DIR/.env 文件"
echo -e "3. 如需更改配置，请编辑 $CONFIG_DIR/config.php 文件" 