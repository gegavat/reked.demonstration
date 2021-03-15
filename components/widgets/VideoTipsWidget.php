<?php

namespace app\components\widgets;

use yii\base\Widget;

class VideoTipsWidget extends Widget {
    public $videoUrl;

    public function run() {
        return $this->render('video-tips', ['videoUrl' => $this->videoUrl]);
    }

}