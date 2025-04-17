<?php
require __DIR__ . '/../../vendor/autoload.php';

use DI\Container;
use Slim\Factory\AppFactory;
use Slim\Views\Twig;
use Slim\Views\TwigMiddleware;
use Dotenv\Dotenv;

// 加载环境变量
$dotenv = Dotenv::createImmutable(__DIR__ . '/../..');
$dotenv->load();

// 创建DI容器
$container = new Container();
$dependencies = require __DIR__ . '/../src/dependencies.php';
$container = $dependencies($container);

// 创建应用
AppFactory::setContainer($container);
$app = AppFactory::create();

// 添加错误中间件
$app->addErrorMiddleware(true, true, true);

// 设置视图中间件
$app->add(TwigMiddleware::createFromContainer($app));

// 添加路由
require __DIR__ . '/../src/routes.php';

// 运行应用
$app->run(); 