<?php

function debug($var, $end=true) {
    echo "<div class='text-left'><pre>" . print_r ($var, true) . "</pre></div>";
    if ( $end ) exit;
}
function debugToFile($var, $end=true, $fileName='debug-file.txt') {
    $pathToDebugFile = __DIR__ . '/runtime/logs/';
    file_put_contents($pathToDebugFile.$fileName, print_r ($var, true));
    if ( $end ) exit;
}

function getRusDate($created_at) {
    $monthList = [
        'января',
        'февраля',
        'марта',
        'апреля',
        'мая',
        'июня',
        'июля',
        'августа',
        'сентября',
        'октября',
        'ноября',
        'декабря'
    ];
    $currentMonth = date('n', $created_at)-1;
    return ( date('d', $created_at) . ' ' . $monthList[$currentMonth] . ' ' . date('Y', $created_at) );
}

function elapsed_time($timestamp, $precision = 1) {
    $time = time() - $timestamp;
    if ($time < 0) return 'сбой времени на сервере';
    $a = array('десятилетие' => 315576000, 'год.' => 31557600, 'мес.' => 2629800, 'нед.' => 604800, 'дн.' => 86400, 'час.' => 3600, 'мин.' => 60, 'сек.' => 1);
    $i = 0;
    foreach($a as $k => $v) {
        $$k = floor($time/$v);
        if ($$k) $i++;
        $time = $i >= $precision ? 0 : $time - $$k * $v;
        // $s = $$k > 1 ? 's' : '';
        $s = '';
        $$k = $$k ? $$k.' '.$k.$s.' ' : '';
        @$result .= $$k;
    }
    return $result ? $result.'назад' : '1 sec to go';
}

function getColumnSize($marks, $width=100, $procent=80) {
    // количество всех элементов
    $number_all = count($marks);
    // начальная ширина одного элемента
    $start_elem_width = $width/$number_all;
    $imgs = array_filter($marks, function($mark) {
        return $mark->type === 'img';
    });
    $txts = array_filter($marks, function($mark) {
        return $mark->type === 'txt';
    });
    // количество изображений и текст. элементов
    $number_img = count($imgs);
    $number_txt = count($txts);
    // проверка на отсутствие текст. элементов или изображений
    if ($number_img == 0)
        return (object) [
            'img' => 0,
            'txt' => $width / $number_txt
        ];
    if ($number_txt == 0)
        return (object) [
            'img' => $width / $number_img,
            'txt' => 0
        ];
    // ширина группы изображений
    $img_group_width = $start_elem_width * $number_img;
    // урезанная ширина группы изображений
    $crop_img_group_width = $img_group_width * ($procent/100);
    // остаток после урезки
    $residue_after_crop = $img_group_width - $crop_img_group_width;
    // ширина группы текст. элементов + добавка после обрезки группы изображений
    $txt_group_width = $start_elem_width * $number_txt + $residue_after_crop;
    // ширина отдельного текст. элемента и изображения
    $txt_width = $txt_group_width / $number_txt;
    $img_width = $crop_img_group_width / $number_img;
    return (object) [
        'txt' => $txt_width,
        'img' => $img_width
    ];
}

function getUserImagePath() {
    $dir = Yii::getAlias('@image_path/') . Yii::$app->user->getId();
    if ( !file_exists($dir) ) {
        \yii\helpers\FileHelper::createDirectory($dir);
    }
    return $dir . '/';
}
function getUserImageUrl() {
    return Yii::getAlias('@image_url/') . Yii::$app->user->getId() . '/';
}

function getRandomString() {
    return time() . '_' . rand(10000, 99999999);
}

function logFile($var, $pathToFile){
    if ( !file_exists($pathToFile) ) {
        file_put_contents($pathToFile, '');
    }
    file_put_contents(
        $pathToFile,
        print_r ($var, true),
        filesize($pathToFile) < Yii::$app->params['bidderLogFileLimit'] ? FILE_APPEND : null
    );
}

function genPassword ($length=6) {
    $chars="qazxswedcvfrtgbnhyujmkiolp1234567890QAZXSWEDCVFRTGBNHYUJMKIOLP";
    $length = intval($length);
    $size=strlen($chars)-1;
    $password = "";
    while($length--) $password.=$chars[rand(0,$size)];
    return $password;
}

function tsvToArray($file,$args=[]) {
    //key => default
    $fields = array(
        'header_row'=>false,
        'remove_header_row'=>false,
        'trim_headers'=>true, //trim whitespace around header row values
        'trim_values'=>true, //trim whitespace around all non-header row values
        'debug'=>false, //set to true while testing if you run into troubles
        'lb'=>"\n", //line break character
        'tab'=>"\t", //tab character
    );
    foreach ($fields as $key => $default) {
        if (array_key_exists($key,$args)) { $$key = $args[$key]; }
        else { $$key = $default; }
    }
    if (!file_exists($file)) {
        if ($debug) { $error = 'File does not exist: '.htmlspecialchars($file).'.'; }
        else { $error = 'File does not exist.'; }
        custom_die($error);
    }
    if ($debug) { echo '<p>Opening '.htmlspecialchars($file).'…</p>'; }
    $data = array();
    if (($handle = fopen($file,'r')) !== false) {
        $contents = fread($handle, filesize($file));
        fclose($handle);
    } else {
        custom_die('There was an error opening the file.');
    }
    $lines = explode($lb,$contents);
    if ($debug) { echo '<p>Reading '.count($lines).' lines…</p>'; }
    $row = 0;
    foreach ($lines as $line) {
        $row++;
        if (($header_row) && ($row == 1)) { $data['headers'] = array(); }
        else { $data[$row] = array(); }
        $values = explode($tab,$line);
        foreach ($values as $c => $value) {
            if (($header_row) && ($row == 1)) { //if this is part of the header row
                if (in_array($value,$data['headers'])) { custom_die('There are duplicate values in the header row: '.htmlspecialchars($value).'.'); }
                else {
                    if ($trim_headers) { $value = trim($value); }
                    $data['headers'][$c] = $value.''; //the .'' makes sure it's a string
                }
            } elseif ($header_row) { //if this isn't part of the header row, but there is a header row
                $key = $data['headers'][$c];
                if ($trim_values) { $value = trim($value); }
                $data[$row][$key] = $value;
            } else { //if there's not a header row at all
                $data[$row][$c] = $value;
            }
        }
    }
    if ($remove_header_row) {
        unset($data['headers']);
    }
    if ($debug) { echo '<pre>'.print_r($data,true).'</pre>'; }
    return $data;
}