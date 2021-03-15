<?php
namespace app\assets;

use yii\web\AssetBundle;

class AccountAsset extends AssetBundle {
    public $basePath = '@webroot';
    public $baseUrl = '@web';
    public $css = [
    ];
    public $js = [
        'js/account.js',
    ];
    public $depends = [
        'app\assets\AppAsset',
    ];
}
