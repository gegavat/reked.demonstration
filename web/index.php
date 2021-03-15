<?php

// comment out the following two lines when deployed to production
// defined('YII_DEBUG') or define('YII_DEBUG', true);
// defined('YII_ENV') or define('YII_ENV', 'dev');

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../vendor/yiisoft/yii2/Yii.php';
require __DIR__ . '/../functions.php';

$config = require __DIR__ . '/../config/web.php';

// my aliases
Yii::setAlias('@image_path', dirname(__DIR__) . '/web/uploads/images');
Yii::setAlias('@image_url', '/web/uploads/images');

(new yii\web\Application($config))->run();
