# Auth System 待办事项

## 已完成
- [x] 设置GitHub仓库
- [x] 将默认分支从main改为master
- [x] 配置GitHub Actions自动部署
- [x] 测试部署流程并成功部署

## 明日开发计划（优先级排序）
- [ ] 管理后台基础框架开发
  - [ ] 创建基本目录结构
  - [ ] 设计基础类库和辅助函数
  - [ ] 实现主布局和导航菜单
  - [ ] 集成认证机制和权限控制
  - [ ] 开发仪表盘首页

- [ ] Clash节点管理功能
  - [ ] 研究Clash API接口和交互方式
  - [ ] 开发节点列表与状态显示
  - [ ] 实现节点切换功能
  - [ ] 添加节点延迟测试
  - [ ] 开发连接日志查看功能

- [ ] n8n系统集成
  - [ ] 学习n8n基本概念和API
  - [ ] 创建n8n管理界面
  - [ ] 测试通过不同节点访问Google
  - [ ] 开发工作流监控功能

- [ ] MFA账户管理
  - [ ] 设计用户数据结构
  - [ ] 开发用户管理界面
  - [ ] 实现MFA设备添加/移除功能
  - [ ] 添加权限管理和日志查看

## 技术选型
- **后端框架**: PHP + Slim Framework 4 (轻量级路由和中间件)
- **数据存储**: SQLite (简单配置) + JSON文件 (配置存储)
- **前端框架**: Bootstrap 5 (UI组件) + Alpine.js (交互)
- **API交互**: Guzzle HTTP (API请求)
- **其他工具**:
  - dotenv (环境配置管理)
  - monolog (日志记录)
  - Twig (模板引擎)
  - PHPUnit (单元测试，可选)

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