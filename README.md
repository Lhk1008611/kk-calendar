- # 行事历系统 (Calendar System)

  一个基于 Laravel + Vue 3 的现代化行事历管理系统，支持个人日历管理、事件管理、重复事件、拖拽调整、懒加载等丰富功能。界面简洁美观，响应式设计，适合日常工作任务记录与时间管理。

  ## 📸 项目截图

  > - 登录/注册页面
  > - 行事历主界面（月视图/周视图）
  > - 新增/编辑事件模态框
  > - 日历管理列表（个人日历管理）
  > - 事件管理列表
  > - 重复事件设置界面

  ## ✨ 主要功能

  ### 🔐 用户认证

  - 基于 Laravel Sanctum SPA 认证（Cookie-based）
  - 登录 / 注册 / 登出
  - 自动刷新保持登录状态
  - 个人信息查看与修改（用户名、邮箱、密码）
  - 账号注销功能

  ### 📅 行事历核心

  - **多日历支持**：每个用户可以创建多个行事历（如工作、个人、家庭），并可设置默认日历。

  - **事件管理**：

    - 新增、编辑、删除事件
    - 支持全天事件与时间段事件
    - 自定义事件颜色
    - 事件描述、地点、优先级、状态（**待完成**）

  - **重复事件**：

    - 支持每日（已完成）、每周（**待完成**）、每月（**待完成**）、每年（**待完成**）

    - 可设置重复结束日期（UNTIL）

    - 删除单个实例或整个重复系列

    - 排除特定实例（EXDATE）

    - 后端使用 json 格式存储 RRULE 规则，前端集成 `@fullcalendar/rrule` 显示

  - **拖拽与调整**：

    - 支持拖拽移动事件（普通事件和重复事件）
    - 支持调整事件起止时间（拉伸）

  - **懒加载**：FullCalendar 只请求当前视图时间范围内的事件，性能优化

  - **多视图**：周视图、月视图、年视图

  ### 🗂️ 日历管理页面

  - 左侧导航：个人日历管理、日历事件管理
  - 右侧表格：
    - 支持搜索、分页（每页10条）
    - 批量删除（复选框）
    - 固定表头与滚动容器
    - 默认日历不可删除（特殊样式）
  - 新增/编辑日历模态框（名称、描述、颜色、可见性等）

  ### 📋 事件管理页面

  - 类似日历管理，展示所有事件列表
  - 支持按标题搜索、分页、批量删除

  ### 🎨 界面风格

  - Bootstrap 5 响应式布局
  - 圆角卡片、柔和阴影、渐变背景
  - 自定义 FullCalendar 主题（中文、12小时制、半小时间隔）
  - 统一模态框风格，防止遮罩残留

  ## 🛠️ 技术栈

  ### 后端

  - **Laravel 11** (PHP 8.2+)
  - **Laravel Sanctum** – SPA 认证
  - **MySQL** – 数据库
  - **Redis** – 缓存/会话（待完成）

  ### 前端

  - **Vue 3** (Composition API)
  - **Vite** – 构建工具
  - **Bootstrap 5** + **Bootstrap Icons**
  - **FullCalendar** – 日历组件
    - `@fullcalendar/vue3`
    - `@fullcalendar/daygrid`
    - `@fullcalendar/timegrid`
    - `@fullcalendar/interaction`
    - `@fullcalendar/rrule`
  - **Pinia** – 状态管理
  - **Axios** – HTTP 请求（withCredentials）
  - **Vue Router** – 前端路由

  ### 开发与部署

  - **Git** 版本控制
  - **Composer** / **npm** 依赖管理
  - **Laravel Vite** 集成

  ## 📁 数据库设计

  主要数据表：

  - `users` – 用户信息
  - `calendars` – 行事历（id, user_id, name, description, color, is_default, visibility）
  - `calendar_events` – 事件（id, calendar_id, title, description, start_time, end_time, all_day, status, priority, location, color, rrule, exdates, rrule_until）

  关系：一个用户拥有多个日历，一个日历包含多个事件。

  > 详细迁移文件请查看 `database/migrations` 目录。

  ## 🚀 快速开始

  ### 环境要求

  - PHP >= 8.2
  - Composer
  - Node.js >= 18
  - MySQL >= 5.7

  ### 安装步骤

  bash

  ```
  # 1. 克隆项目
  git clone https://github.com/Lhk1008611/kk-calendar.git
  cd kk-calendar
  
  # 2. 安装后端依赖
  composer install
  
  # 3. 配置环境变量
  cp .env.example .env
  php artisan key:generate
  # 编辑 .env 文件，设置数据库连接、Redis 等
  
  # 4. 运行数据库迁移
  php artisan migrate
  
  # 5. 安装前端依赖
  npm install
  
  # 6. 编译前端资源（开发模式）
  npm run dev
  
  # 7. 启动 Laravel 开发服务器
  php artisan serve
  
  # 8. 访问 http://localhost:8000
  ```

  

  ### 注意事项

  - 确保 `.env` 中 `SESSION_DOMAIN` 为空或与前端域名一致（开发环境通常设为 `null`）。
  - 如果使用 Vite 代理，请通过 `http://localhost:5173` 访问前端页面，以获得热更新。

  ## 📄 API 接口概览

  | 方法   | URI                                | 说明                               |
  | :----- | :--------------------------------- | :--------------------------------- |
  | POST   | `/api/login`                       | 登录                               |
  | POST   | `/api/register`                    | 注册                               |
  | POST   | `/api/logout`                      | 登出                               |
  | GET    | `/api/user`                        | 获取当前用户                       |
  | GET    | `/api/calendars`                   | 获取日历列表（支持分页、搜索）     |
  | POST   | `/api/calendar`                    | 新增日历                           |
  | DELETE | `/api/calendars`                   | 删除日历（批量）                   |
  | GET    | `/api/calendar_event/range`        | 获取指定时间范围内的事件（懒加载） |
  | POST   | `/api/calendar_event`              | 新增事件                           |
  | PATCH  | `/api/calendar_event/{id}`         | 编辑事件                           |
  | DELETE | `/api/calendar_event`              | 删除事件（批量）                   |
  | PATCH  | `/api/calendar_event/{id}/exclude` | 排除重复事件的某个实例             |

  > 详细请求/响应格式请查看代码中的 FormRequest 或控制器注释。

  ## 🤝 贡献指南

  欢迎提交 Issue 和 Pull Request。请确保代码风格符合 PSR-12 和 ESLint 规则。

  ## 📝 开源协议

  MIT License

  ## 👨‍💻 作者

  [HankLay] – [邮箱](lhk1008611@gmail.com)

  ------

  **感谢使用行事历系统！** 如果喜欢这个项目，请给个 ⭐️ 支持一下。