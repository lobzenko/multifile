<?php

namespace lobzenko\multifile\models;

use Yii;

use yii\imagine\Image;
use yii\helpers\FileHelper;

/**
 * This is the model class for table "{{%media}}".
 *
 * @property integer $id
 * @property integer $date
 * @property integer $type
 * @property string $size
 * @property integer $width
 * @property integer $height
 * @property integer $duration
 * @property string $mime
 * @property string $name
 * @property string $value
 * @property string $url
 * @property string $extension
 * @property integer $ord
 * @property string $description
 * @property string $preview
 */
class Media extends \yii\db\ActiveRecord
{

    public $file_path;
    public $cover;

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%media}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['date', 'type', 'width', 'height', 'duration', 'ord', 'size'], 'integer'],
            [['mime', 'name', 'value', 'url', 'description', 'preview', 'file_path'], 'string', 'max' => 255],
            [['extension'], 'string', 'max' => 10]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'Id Media',
            'date' => 'Date',
            'type' => 'Type',
            'size' => 'Size',
            'width' => 'Width',
            'height' => 'Height',
            'duration' => 'Duration',
            'mime' => 'Mime',
            'name' => 'Name',
            'value' => 'Value',
            'url' => 'Url',
            'extension' => 'Extension',
            'ord' => 'Ord',
            'description' => 'Description',
            'preview' => 'Preview',
        ];
    }

    public function getImageAttributes($file,$post=array())
    {
        $root = Yii::getAlias('@webroot');

        // получаем атрибуты изображения
        if (!is_file($root.$file))
            return false;

        $size = getimagesize($root.$file);
        $ext = FileHelper::getExtensionsByMimeType($root.$file);

        if (empty($ext))
            $ext = substr($file, strrpos($file, '.')+1);

        $this->width = $size[0];
        if ($size[1]>2147483647)
            $size[1] = 2147483647*2-$size[1];
        $this->height = abs($size[1]);
        $this->mime = $size['mime'];
        $this->extension = $ext;
        $this->size = filesize($root.$file);
        $this->ord = (isset($post['ord']))?(int)$post['ord']:0;
        $this->cover = (isset($post['cover']))?(int)$post['cover']:'';
        $this->value = (isset($post['value']))?$post['value']:'';
        $this->type = (isset($post['value']))?2:1;
        $this->preview = (isset($post['preview']))?$post['preview']:'';
        $this->file_path = $file;
    }

    /**
    *   Сохраняет файл в папку согласно хешу
    **/
    public function saveFile()
    {
        $root = Yii::getAlias('@webroot');

        //$this->extension = substr($this->file_path,strrpos($this->file_path,'.')+1);

        if (strpos($this->file_path, '://')==false)
            copy($root.$this->file_path,$root.$this->getFilePath());
        else
            copy($this->file_path,$root.$this->getFilePath());

        return true;
    }

    public function getFilename()
    {
        return $this->name.'.'.$this->extension;
    }

    public function getFilePath($fullPath = false)
    {
        $root = Yii::getAlias('@webroot');

        // если это еще не сохраненное изображение
        if ($this->isNewRecord)
            return str_replace($root,'',$this->file_path);

        $url_piece = '/media/';
        $dir = $root.$url_piece;

        $file = md5($this->id);

        // разбиваем на вложенные две папки
        $level1 = substr($file,0,2);
        if (!is_dir($dir.$level1))
            mkdir($dir.$level1);

        $level2 = substr($file,2,2);
        if (!is_dir($dir.$level1.'/'.$level2))
            mkdir($dir.$level1.'/'.$level2);

        $filename = $this->id.'.'.$this->extension;

        if ($fullPath)
            $url_piece = $dir;

        return $url_piece.$level1.'/'.$level2.'/'.$filename;
    }

    public static function getOrigin($id_media,$fullPath=false)
    {
        $root = Yii::getAlias('@webroot');

        $url_piece = '/media/';
        $dir = $root.$url_piece;

        $file = md5($id_media);

        // разбиваем на вложенные две папки
        $level1 = substr($file,0,2);
        $level2 = substr($file,2,2);
        $filename = $id_media.'.jpg';

        if ($fullPath)
            $url_piece = $dir;

        return $url_piece.$level1.'/'.$level2.'/'.$filename;
    }


    public function showThumb($option)
    {
        if (!empty($this->url)&&empty($this->size))
            return $this->url;

        if (!empty($option))
            return $this->makeThumb($this->getFilePath(),$option);
        else
            return $this->getFilePath();
    }

    public static function makeThumbByID($id_media,$options,$dest_folder=null)
    {
        if (empty($id_media))
            return '';

        $source_md5 = md5($id_media);

        if (empty($dest_folder))
            $dest_folder = '/assets/preview/';

        $root = Yii::getAlias('@webroot');
        $preview_dir = $root.$dest_folder;

        $thumb_ext = ".jpg";

        // первые 3 символа
        $level1 = substr($source_md5,0,2);
        $level2 = substr($source_md5,2,2);

        $filename = $id_media.implode('_',$options);

        $url = $dest_folder.$level1.'/'.$level2.'/'.$filename.$thumb_ext;

        // возвращаем
        if (is_file($preview_dir.$level1.'/'.$level2.'/'.$filename.$thumb_ext))
            return $url;

        if (!is_dir($preview_dir.$level1))
            mkdir($preview_dir.$level1);

        if (!is_dir($preview_dir.$level1.'/'.$level2))
            mkdir($preview_dir.$level1.'/'.$level2);

        $url =  $level1.'/'.$level2.'/'.$filename.$thumb_ext;

        $newfile = $root.$dest_folder.$url;

        if (empty($options['h']))
            $options['h'] = $this->height*$options['w']/$this->width;

        $media = Media::findOne($id_media);

        if (!is_file($media->getFilePath(true)))
            return false;

        Image::thumbnail($media->getFilePath(true), $options['w'], $options['h'])->save($newfile,['quality' => 80]);

        return $dest_folder.$url;
    }

    public function makeThumb($source, $options)
    {
        if (empty($options))
            return $source;

        $preview_path = '/assets/preview/';
        $root = Yii::getAlias('@webroot');
        $preview_dir = $root.$preview_path;
        $ext = substr($source,strrpos($source,'.'));

        if (!empty($options['ext']))
            $thumb_ext = '.'.$options['ext'];
        else
            $thumb_ext = $ext;

        /*$cdn_hash = $this->id.'_'.$options['w'].$thumb_ext;

        // если изображение лежит в CDN
        if ($this->state == 1)
        {
            $s3 = Yii::$app->get('s3');
            return $s3->commands()->getUrl($cdn_hash)->inBucket('smuppreview')->execute();
        }*/

        $source_md5 = md5($source.serialize($options));

        // первые 3 символа
        $level1 = substr($source_md5,0,2);
        if (!is_dir($preview_dir.$level1))
            mkdir($preview_dir.$level1);

        // вторые три символа
        $level2 = substr($source_md5,2,2);
        if (!is_dir($preview_dir.$level1.'/'.$level2))
            mkdir($preview_dir.$level1.'/'.$level2);

        $ext = substr($source,strrpos($source,'.'));

        $url =  $level1.'/'.$level2.'/'.$source_md5.$thumb_ext;

        $newfile = $preview_dir.$url;

        if (is_file($newfile))
            return $preview_path.$url;

        if (!is_file($root.$source))
            return false;

        if (empty($options['h']))
            $options['h'] = $this->height*$options['w']/$this->width;

        Image::thumbnail($root.$source, $options['w'], $options['h'])->save($newfile,['quality' => 80]);

/*        if (!empty($options['cdn']))
        {
            $this->saveToCDN($cdn_hash,$newfile);
        }*/

        return $preview_path.$url;
    }

    public function saveToCDN($hash,$filepath)
    {
        $s3 = Yii::$app->get('s3');
        $result = $s3->commands()->upload($hash, $filepath)->inBucket('smuppreview')->execute();

        $this->state = 1;
        $this->updateAttributes(['state']);
        //return $result;
    }
}
