<?php

namespace app\assets;


use yii\web\AssetBundle;

class InsertCodeAsset extends AssetBundle {
    public $basePath = '@webroot';
    public $baseUrl = '@web';
    public $css = [
    ];
    public $js = [
        'js/clipboard.min.js',
        'js/insert-code.js',
    ];
    public $depends = [
        'app\assets\AppAsset',
    ];
}