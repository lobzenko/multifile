<?php

namespace lobzenko\multifile;

use Yii;
use yii\helpers\Url;
use yii\base\Widget;

class MultiFileWidget extends Widget
{
    public $uploadAction = '/site/upload';

	public $model;

	//public $attribute;

	public $single = false;

	public $showPreview = false;

	public $relation;

	public $records = [];

	public $grouptype;

	public $template=1;

	public $extensions = [];


	public function run()
	{
		$records = [];


		if (!empty($this->records))
		{
			foreach ($this->records as $key => $data)
				if (!empty($data))
					$records[] = $data->attributes;
		}
		else
		{
			foreach ($this->model->getMediaRecords($this->relation) as $key => $data)
			{
				if (!empty($data))
				{
					$records[$key] = $data->attributes;

					if ($data->isNewRecord)
						$records[$key]['file_path'] = $data->file_path;
					else
					{
						$records[$key]['file_path'] = $data->getFilePath();
						$records[$key]['id'] = $data->getPrimaryKey();
					}
				}
			}
		}

		return $this->render('file_upload', [
			'url' => Url::to(is_array($this->uploadAction) ? $this->uploadAction : [$this->uploadAction]),
			'model' => $this->model,
			'records' => $records,
			'POST_relation_name' => \yii\helpers\StringHelper::basename(get_class($this->model)) . '_' . $this->relation,
			'single' => (int)$this->single,
			'group' => $this->grouptype,
			'extensions' => $this->extensions,
			'showPreview' => (int)$this->showPreview,
			'template' => $this->template,
		]);
	}
}