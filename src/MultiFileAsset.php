<?php

namespace lobzenko\multifile;

use yii\web\AssetBundle;

class MultiFileAsset extends AssetBundle
{
    public $sourcePath = '@lobzenko/multifile/assets';
    public $css = [
        'css/fileuploader.css',
    ];
    public $js = [
        'js/fileuploader.js',
        'js/multiupload.js',
    ];
    public $depends = [
        'yii\jui\JuiAsset',
        'yii\bootstrap\BootstrapAsset',
    ];
}