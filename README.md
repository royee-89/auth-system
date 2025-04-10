# Authelia认证系统

## 目录结构

```
auth/
├── config/                 # 配置文件目录
│   ├── authelia-config.yml # Authelia主配置文件
│   └── users_database.yml  # 用户数据库配置
├── nginx/                  # Nginx配置文件目录
│   ├── auth-nginx.conf     # 认证服务器Nginx配置
│   ├── n8n-auth-nginx.conf # N8N服务器Nginx配置
│   └── mirror.conf         # 镜像配置
├── public/                 # Web公共文件
│   ├── index.php           # 主认证页面(优化版)
│   ├── auth-direct.php     # 直接认证页面
│   ├── auth-fixed.php      # 修复版认证页面
│   ├── auth.php            # 初始认证PHP脚本
│   └── auth.html           # 初始认证HTML页面
└── README.md               # 说明文档
```

# Authelia 认证系统配置清单

## 准备工作

- [ ] 确认服务器环境
  - [ ] 检查 Docker 安装状态：`docker --version`
  - [ ] 检查 Nginx 安装状态：`nginx -v`
  - [ ] 确认服务器 IP：`175.27.254.35`
  - [ ] 确认主域名：`royeenote.online`

## 1. 目录结构创建

- [ ] 创建 Authelia 配置目录
  ```bash
  mkdir -p /opt/authelia/config
  ```

- [ ] 创建 Nginx 配置目录
  ```bash
  mkdir -p /etc/nginx/snippets
  ```

## 2. Authelia 配置

- [ ] 创建 Authelia 配置文件
  ```bash
  nano /opt/authelia/config/configuration.yml
  ```
  
  内容参考：
  ```yaml
  ---
  theme: dark
  jwt_secret: REPLACE_WITH_RANDOM_STRING  # 替换为随机字符串
  default_redirection_url: https://royeenote.online

  server:
    host: 0.0.0.0
    port: 9091

  log:
    level: info

  totp:
    issuer: royeenote.online
    period: 30
    skew: 1

  authentication_backend:
    file:
      path: /config/users_database.yml

  access_control:
    default_policy: deny
    rules:
      # 主域名无需认证
      - domain: royeenote.online
        policy: bypass
      - domain: www.royeenote.online
        policy: bypass
      
      # 认证门户无需认证
      - domain: auth.royeenote.online
        policy: bypass
      
      # 需要两因素认证的服务
      - domain: 
          - n8n.royeenote.online
          - clash.royeenote.online
        policy: two_factor
        subject:
          - "group:admins"

  session:
    name: authelia_session
    secret: REPLACE_WITH_RANDOM_STRING  # 替换为另一个随机字符串
    expiration: 3600
    inactivity: 300
    domain: royeenote.online

  regulation:
    max_retries: 3
    find_time: 2m
    ban_time: 5m

  storage:
    local:
      path: /config/db.sqlite3

  notifier:
    filesystem:
      filename: /config/notification.txt
  ```

- [ ] 生成密码哈希
  ```bash
  docker run --rm authelia/authelia:latest authelia hash-password -- YOUR_PASSWORD
  ```
  [记下生成的哈希值]

- [ ] 创建用户数据库
  ```bash
  nano /opt/authelia/config/users_database.yml
  ```
  
  内容：
  ```yaml
  ---
  users:
    admin:
      displayname: "管理员"
      password: "PASTE_HASH_HERE"  # 粘贴生成的密码哈希
      email: your@email.com
      groups:
        - admins
  ```

- [ ] 启动 Authelia 容器
  ```bash
  docker run -d \
    --name authelia \
    --restart always \
    -p 127.0.0.1:9091:9091 \
    -v /opt/authelia/config:/config \
    -e TZ=Asia/Shanghai \
    authelia/authelia
  ```

## 3. Nginx 配置

- [ ] 创建 SSL 通用配置
  ```bash
  nano /etc/nginx/snippets/ssl.conf
  ```
  
  内容：
  ```nginx
  ssl_certificate /etc/nginx/ssl/royeenote.online_bundle.crt;
  ssl_certificate_key /etc/nginx/ssl/royeenote.online.key;
  ssl_protocols TLSv1.2 TLSv1.3;
  ssl_ciphers ECDHE-ECDSA-AES128-GCM-SHA256:ECDHE-RSA-AES128-GCM-SHA256:ECDHE-ECDSA-AES256-GCM-SHA384:ECDHE-RSA-AES256-GCM-SHA384:ECDHE-ECDSA-CHACHA20-POLY1305:ECDHE-RSA-CHACHA20-POLY1305:DHE-RSA-AES128-GCM-SHA256:DHE-RSA-AES256-GCM-SHA384;
  ssl_prefer_server_ciphers off;
  ssl_session_timeout 1d;
  ssl_session_cache shared:SSL:50m;
  ssl_session_tickets off;

  # HSTS 配置
  add_header Strict-Transport-Security "max-age=63072000" always;

  # 安全相关头部
  add_header X-Frame-Options "SAMEORIGIN" always;
  add_header X-XSS-Protection "1; mode=block" always;
  add_header X-Content-Type-Options "nosniff" always;
  add_header Referrer-Policy "no-referrer-when-downgrade" always;
  ```

- [ ] 创建代理通用配置
  ```bash
  nano /etc/nginx/snippets/proxy.conf
  ```
  
  内容：
  ```nginx
  proxy_set_header Host $host;
  proxy_set_header X-Real-IP $remote_addr;
  proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
  proxy_set_header X-Forwarded-Proto $scheme;
  proxy_http_version 1.1;
  proxy_set_header Connection "upgrade";
  proxy_set_header Upgrade $http_upgrade;
  proxy_buffering off;
  ```

- [ ] 创建 Authelia 认证配置
  ```bash
  nano /etc/nginx/snippets/authelia.conf
  ```
  
  内容：
  ```nginx
  # Authelia 认证配置
  auth_request /authelia;
  auth_request_set $target_url $scheme://$http_host$request_uri;
  auth_request_set $user $upstream_http_remote_user;
  auth_request_set $groups $upstream_http_remote_groups;
  auth_request_set $name $upstream_http_remote_name;
  auth_request_set $email $upstream_http_remote_email;
  proxy_set_header Remote-User $user;
  proxy_set_header Remote-Groups $groups;
  proxy_set_header Remote-Name $name;
  proxy_set_header Remote-Email $email;

  # 认证处理
  location = /authelia {
      internal;
      proxy_pass http://127.0.0.1:9091/api/verify;
      proxy_pass_request_body off;
      proxy_set_header Content-Length "";
      proxy_set_header X-Original-URL $scheme://$http_host$request_uri;
      proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
      proxy_set_header X-Forwarded-Proto $scheme;
      proxy_set_header X-Forwarded-Method $request_method;
      proxy_set_header X-Forwarded-Host $http_host;
      
      # 保持认证会话
      proxy_set_header Cookie $http_cookie;
  }

  # 认证失败处理
  error_page 401 =302 https://auth.royeenote.online/?rd=$target_url;
  ```

- [ ] 配置认证服务网站
  ```bash
  nano /etc/nginx/conf.d/auth.conf
  ```
  
  内容：
  ```nginx
  server {
      listen 80;
      server_name auth.royeenote.online;
      return 301 https://$host$request_uri;
  }

  server {
      listen 443 ssl;
      server_name auth.royeenote.online;
      
      include /etc/nginx/snippets/ssl.conf;
      
      location / {
          proxy_pass http://127.0.0.1:9091;
          include /etc/nginx/snippets/proxy.conf;
          
          proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
          proxy_set_header X-Forwarded-Proto $scheme;
          proxy_set_header X-Forwarded-Host $http_host;
      }
  }
  ```

- [ ] 配置 n8n 服务（受保护）
  ```bash
  nano /etc/nginx/conf.d/n8n.conf
  ```
  
  内容：
  ```nginx
  server {
      listen 80;
      server_name n8n.royeenote.online;
      return 301 https://$host$request_uri;
  }

  server {
      listen 443 ssl;
      server_name n8n.royeenote.online;
      
      include /etc/nginx/snippets/ssl.conf;
      
      # Authelia 认证
      include /etc/nginx/snippets/authelia.conf;
      
      location / {
          proxy_pass http://127.0.0.1:5678;
          include /etc/nginx/snippets/proxy.conf;
          
          # 传递认证信息给后端
          proxy_set_header Remote-User $user;
          proxy_set_header Remote-Groups $groups;
          proxy_set_header Remote-Name $name;
          proxy_set_header Remote-Email $email;
      }
  }
  ```

- [ ] 配置 Clash 控制面板（受保护）
  ```bash
  nano /etc/nginx/conf.d/clash.conf
  ```
  
  内容：
  ```nginx
  server {
      listen 80;
      server_name clash.royeenote.online;
      return 301 https://$host$request_uri;
  }

  server {
      listen 443 ssl;
      server_name clash.royeenote.online;
      
      include /etc/nginx/snippets/ssl.conf;
      
      # Authelia 认证
      include /etc/nginx/snippets/authelia.conf;
      
      location / {
          proxy_pass http://127.0.0.1:9090;
          include /etc/nginx/snippets/proxy.conf;
          
          # 传递认证信息给后端
          proxy_set_header Remote-User $user;
          proxy_set_header Remote-Groups $groups;
          proxy_set_header Remote-Name $name;
          proxy_set_header Remote-Email $email;
      }
  }
  ```

- [ ] 测试 Nginx 配置
  ```bash
  nginx -t
  ```

- [ ] 重启 Nginx
  ```bash
  systemctl restart nginx
  ```

## 4. DNS 配置

- [ ] 登录腾讯云 DNS 控制台
- [ ] 添加 DNS 记录：
  - [ ] `auth.royeenote.online` → A 记录 → `175.27.254.35`
  - [ ] `n8n.royeenote.online` → A 记录 → `175.27.254.35`
  - [ ] `clash.royeenote.online` → A 记录 → `175.27.254.35`

## 5. 验证与测试

- [ ] 检查 Authelia 容器是否运行
  ```bash
  docker ps | grep authelia
  docker logs authelia
  ```

- [ ] 访问认证门户
  - [ ] 浏览器打开 `https://auth.royeenote.online`
  - [ ] 确认能看到 Authelia 登录页面

- [ ] 测试登录流程
  - [ ] 输入用户名和密码
  - [ ] 配置和测试两因素认证 (TOTP)
  - [ ] 确认认证会话有效

- [ ] 测试受保护服务
  - [ ] 访问 `https://n8n.royeenote.online`
  - [ ] 确认被重定向到 auth 页面（如果未登录）
  - [ ] 确认登录后可以访问 n8n
  - [ ] 测试 `https://clash.royeenote.online`

## 6. 维护与备份

- [ ] 创建 Authelia 配置备份
  ```bash
  mkdir -p /opt/backups
  tar -czf /opt/backups/authelia-config-$(date +%Y%m%d).tar.gz /opt/authelia/config
  ```

- [ ] 配置备份脚本
  ```bash
  nano /usr/local/bin/backup-authelia.sh
  ```
  
  内容：
  ```bash
  #!/bin/bash
  BACKUP_DIR="/opt/backups"
  mkdir -p $BACKUP_DIR
  tar -czf $BACKUP_DIR/authelia-config-$(date +%Y%m%d).tar.gz /opt/authelia/config
  find $BACKUP_DIR -name "authelia-config-*.tar.gz" -mtime +30 -delete
  ```

- [ ] 添加执行权限
  ```bash
  chmod +x /usr/local/bin/backup-authelia.sh
  ```

- [ ] 配置定期执行
  ```bash
  crontab -e
  ```
  
  添加：
  ```
  0 2 * * * /usr/local/bin/backup-authelia.sh
  ```

## 7. 其他配置（可选）

- [ ] 设置自动更新 Authelia
  ```bash
  nano /usr/local/bin/update-authelia.sh
  ```
  
  内容：
  ```bash
  #!/bin/bash
  # 备份当前配置
  tar -czf /opt/backups/authelia-pre-update-$(date +%Y%m%d%H%M%S).tar.gz /opt/authelia/config
  
  # 更新 Authelia
  docker pull authelia/authelia:latest
  docker stop authelia
  docker rm authelia
  docker run -d \
    --name authelia \
    --restart always \
    -p 127.0.0.1:9091:9091 \
    -v /opt/authelia/config:/config \
    -e TZ=Asia/Shanghai \
    authelia/authelia
  ```

- [ ] 添加执行权限
  ```bash
  chmod +x /usr/local/bin/update-authelia.sh
  ```

## 完成后检查清单

- [ ] Authelia 容器正常运行
- [ ] Nginx 配置无错误
- [ ] DNS 记录已配置
- [ ] 所有受保护服务可通过认证访问
- [ ] 两因素认证功能正常
- [ ] 备份策略已配置
- [ ] 文档已更新，包含操作说明

## 注意事项

- 请确保生成安全的随机密钥用于 `jwt_secret` 和 `session.secret`
- 确保 SSL 证书配置正确且未过期
- 定期备份 Authelia 配置和数据库
- 启用两因素认证后，请妥善保管恢复代码 

# 创建存放证书的目录
ssh 用户名@175.27.254.35 "sudo mkdir -p /etc/nginx/ssl/ && sudo chmod 755 /etc/nginx/ssl/"

# 上传证书文件
sudo scp /etc/letsencrypt/archive/royeenote.online/fullchain1.pem 用户名@175.27.254.35:/etc/nginx/ssl/royeenote.online_bundle.crt
sudo scp /etc/letsencrypt/archive/royeenote.online/privkey1.pem 用户名@175.27.254.35:/etc/nginx/ssl/royeenote.online.key

# 设置适当的权限
ssh 用户名@175.27.254.35 "sudo chmod 644 /etc/nginx/ssl/royeenote.online_bundle.crt && sudo chmod 600 /etc/nginx/ssl/royeenote.online.key" 