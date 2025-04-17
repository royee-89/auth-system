# Auth System 待办事项

## 最高优先级任务 🔴
- [ ] 安装支持Clash的代理服务
  - [ ] 安装Shadowsocks或V2Ray
  - [ ] 配置Clash直接支持的协议
  - [ ] 生成完整的Clash配置文件
  - [ ] 设置自动更新订阅链接

- [ ] 绕开VPN检查
  - [ ] 修改HTTP请求头部
  - [ ] 使用更像住宅IP的服务器
  - [ ] 随机化客户端指纹
  - [ ] 减少WebRTC泄露
  - [ ] 添加浏览器插件阻止指纹识别

- [ ] 绕开Claude地区检查
  - [ ] 设置更准确的地理位置信息
  - [ ] 修改Accept-Language头
  - [ ] 使用美国/支持地区的IP
  - [ ] 考虑添加更多的伪装层

## 已完成
- [x] 设置GitHub仓库
- [x] 将默认分支从main改为master
- [x] 配置GitHub Actions自动部署
- [x] 测试部署流程并成功部署
- [x] 规划管理后台架构
- [x] 创建基础目录结构

## 暂停的任务
- [ ] 管理后台开发（已暂停）
  - [ ] 前端React开发
    - [ ] 登录页面
    - [ ] 仪表盘
    - [ ] Clash节点管理
    - [ ] n8n管理
    - [ ] 用户管理
  - [ ] 后端API开发
    - [ ] 认证系统
    - [ ] Clash节点管理
    - [ ] n8n集成
    - [ ] 用户管理

## 新任务：代理服务中转节点搭建

### 项目背景
使用 LightNode（香港）Ubuntu 22.04 服务器，通过 WireGuard + GOST 构建科学上网的桥接代理，支持：
- 本地设备（macOS + Stash）出网访问
- 云服务器（腾讯云 n8n 节点）出网访问

### 当前进度
- [x] LightNode 云服务器配置
  - [x] 地区：香港
  - [x] 系统：Ubuntu 22.04
  - [x] 公网 IP：38.54.23.220
  - [x] 安全组配置（UDP 51820 端口）
- [x] WireGuard 服务
  - [x] 安装并运行成功
  - [x] wg0 接口配置正常
  - [x] Mac 客户端连接成功
  - [x] Stash 集成完成
  - [x] UDP 端口 51820 开放
- [x] LightNode 配置
  - [x] 开启 BGP 国际线路
  - [x] 适配中国大陆访问
- [x] n8n 需求确认
  - [x] 云服务器通过代理访问 Google 等服务

### 待完成任务
1. [ ] GOST 安装与配置
   - [ ] 修复安装问题（404错误）
     ```bash
     GOST_LATEST=$(curl -s https://api.github.com/repos/go-gost/gost/releases/latest | grep "browser_download_url" | grep "linux-amd64" | cut -d '"' -f 4)
     wget $GOST_LATEST -O gost && chmod +x gost && mv gost /usr/local/bin/
     ```
   - [ ] 验证安装：`gost -V`
   - [ ] 配置桥接服务
     ```yaml
     services:
       - name: wireguard-bridge
         addr: :1080
         handler:
           type: socks5
         listener:
           type: tcp
     ```
   - [ ] 启动服务：`nohup gost -C /etc/gost/config.yaml &`
   - [ ] 配置 systemd 服务（可选）

2. [ ] 云服务端（n8n）接入代理
   - [ ] 安装代理工具（Clash/sing-box/redsocks）
   - [ ] 配置代理参数：`socks5://10.8.0.1:1080`
   - [ ] 配置 WireGuard 客户端
   - [ ] 测试外部访问

### 最终目标
- [ ] 本地设备代理
  - [ ] Mac/iPhone 连接 WireGuard
  - [ ] Stash 自动切换、智能分流
- [ ] 云服务器代理
  - [ ] 腾讯云服务器接入香港节点
  - [ ] 作为科学上网出口节点

### 优化建议（可选）
- [ ] 使用 sing-box 替代 gost
- [ ] 增加 udp:// 和 http(s):// 监听
- [ ] 配置 systemd 服务守护
- [ ] 添加备用节点策略（东京、新加坡）

## 技术选型
- **前端框架**: React + TypeScript
  - Ant Design 组件库
  - Redux Toolkit 状态管理
  - React Router 路由管理
- **后端框架**: PHP + Slim Framework 4
  - RESTful API
  - SQLite 数据库
  - JWT 认证
- **其他工具**:
  - dotenv (环境配置管理)
  - monolog (日志记录)
  - Guzzle (HTTP客户端)

## 待处理（后续）
- [ ] 优化用户界面设计
- [ ] 完善用户认证功能
- [ ] 添加用户管理页面
- [ ] 实现密码重置功能
- [ ] 加强安全措施
- [ ] 添加日志记录功能
- [ ] 设置自动备份
- [ ] 添加单元测试

## 集成与扩展
- [ ] 后台管理系统开发
  - [ ] 配置admin.royeenote.online子域名
  - [ ] 设置Nginx反向代理
  - [ ] 集成auth认证保护
  - [ ] 开发后台管理界面框架
  - [ ] 添加访问控制和权限管理
  - [ ] 测试与调试

- [ ] n8n与Clash节点切换功能
  - [ ] 在后台管理系统中集成
  - [ ] 实现节点切换功能
  - [ ] 添加节点状态监控
  - [ ] 日志记录与审计

- [ ] MFA用户管理
  - [ ] 在后台管理系统中集成
  - [ ] 添加MFA设备管理
  - [ ] 用户权限配置
  - [ ] 登录日志与安全审计

## 配置与部署
- [ ] 子域名配置
  - [ ] 配置admin.royeenote.online DNS记录
  - [ ] 为admin子域名申请并配置SSL证书
  - [ ] 设置Nginx虚拟主机配置
  - [ ] 配置HTTPS重定向
  - [ ] 测试子域名访问

## 未来计划
- [ ] 支持多因素认证
- [ ] 集成OAuth登录
- [ ] 开发API接口
- [ ] 移动端适配优化

## 紧急任务 (P0)

- [ ] **修复Nginx配置问题**: 
  - 错误: `nginx: [emerg] open() "/etc/nginx/snippets/fastcgi-php.conf" failed (2: No such file or directory)`
  - 解决方案: 在服务器上创建fastcgi-php.conf配置文件或修改auth.tinotools.cn.conf以移除对此文件的依赖
  - 背景: 部署时Nginx配置测试失败，导致整个部署流程失败
  - 时间估计: 1小时 