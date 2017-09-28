yii2-multifile-upload
=====================
multifile upload

Installation
------------

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```
php composer.phar require --prefer-dist lobzenko/yii2-multifile-upload "*"
```

or add

```
"lobzenko/yii2-multifile-upload": "*"
```

to the require section of your `composer.json` file.


Usage
-----

Once the extension is installed, simply use it in your code by  :

```php
public function actions()
{
    return [
        'upload' => [
            'class' => 'lobzenko\multifile\UploadAction',
        ],
    ];
}
```

```php
public function behaviors()
{
    return [
        'multiupload' => [
            'class' => \lobzenko\multifile\MultiFileBehavior::className(),
            'relations' => [
                'file' => [
                    'model' => 'lobzenko\multifile\models\Media',
                ],
            ],
        ],
    ];
}
```

```php
<?= \lobzenko\multifile\MultiFileWidget::widget([
    'model' => $model,
    'relation' => 'file',
    'grouptype' => 1,
]) ?>```
