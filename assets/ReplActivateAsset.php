<?php

namespace app\assets;


use yii\web\AssetBundle;

class ReplActivateAsset extends AssetBundle {
    public $basePath = '@webroot';
    public $baseUrl = '@web';
    public $css = [
        'css/on-off-switch.css',
    ];
    public $js = [
        'js/clipboard.min.js',
        'js/on-off-switch.js',
        'js/repl-activate.js',
    ];
    public $depends = [
        'app\assets\AppAsset',
    ];
}