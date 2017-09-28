<?php
/**
 * RelationBehavior class file.
 *
 * @author Lobzenko Mikhail
 */
namespace lobzenko\multifile;

use Yii;
//use app\models;
use yii\db\ActiveRecord;
use yii\base\ErrorException;
use yii\base\Behavior;

class MultiFileBehavior extends Behavior
{
	/**
	 * @var CDbConnection
	 */
	protected $fk;

	// Имя модели изображения
	//public $modelname;

	// ключь для поля обложки по умолчанию ставится первая картинка в очереди
	//public $fk_cover='';

	//public $cover = 'Cover';

	// записи
	public $records = [];

	public $old_pk_models = [];

	// связи
	public $relations = [];

	public $cover=false;

	// имя релейшена
	/* public $relation;

		public $jtable;

		public $required = false;
	*/
	public $index=1;

	public $noimage = '/images/blank.png';

	// принудительная обложка
	//public $selected_cover = 0;

	public function events()
    {
        return [
            ActiveRecord::EVENT_BEFORE_VALIDATE => 'beforeValidate',
            ActiveRecord::EVENT_AFTER_UPDATE => 'afterSave',
            ActiveRecord::EVENT_AFTER_INSERT => 'afterSave',
        ];
    }

	/**
	 * Загрузить записи по имени модели
	 *
	 * @param string $model_name
	 */
	protected function loadMediaRecords($relation_name)
	{
		// если уже загружены
		if (!empty($this->records[$relation_name]))
			return $this->records[$relation_name];

		$relation_name = strtolower($relation_name);

		$settings = $this->relations[$relation_name];

		$this->records[$relation_name] = [];

		if (empty($settings['jtable']))
		{
			$records = $this->owner->$relation_name;

			if (!empty($records))
				$this->records[$relation_name][] = $records;
		}
		else
		{
			$this->records[$relation_name] = $this->owner->$relation_name;

			/*foreach ($this->owner->$relation_name as $key=>$record)
			{
				$pk = $record->getPrimaryKey();

				if (!empty($settings['fk_cover']))
				{
					$fk_cover = $settings['fk_cover'];

					if (empty($this->selected_cover))
						$selected_cover = $this->owner->$fk_cover;
					else
						$selected_cover = $this->selected_cover;

					if (($selected_cover && $pk==$selected_cover)||(!$selected_cover && !$key))
						$record->cover = 1;
				}

				$this->records[$relation_name][] = $record;
			}*/
		}

		return $this->records[$relation_name];
	}

	/**
	 * Возвращает полученные записи
	 *
	 * @param string $model_name
	 */
	public function getMediaRecords($relation_name)
	{
		return $this->loadMediaRecords($relation_name);
	}

	protected function getPOST($relation)
	{
		$class_name = \yii\helpers\StringHelper::basename(get_class($this->owner));

		if (!empty($_POST[$class_name.'_'.$relation][$this->index]))
			return $_POST[$class_name.'_'.$relation][$this->index];

		return false;
	}

	/**
	 * Устанавливает индекс модели если нужно сохранить несколько одинаковых моделей
	 *
	 * @param int $index
	 */
	public function setModelIndex($index)
	{
		return $this->index = (int)$index;
	}


	public function beforeValidate($event)
	{
		// загружаем новые файлы

		foreach ($this->relations as $relation_name => $settings)
		{
			// если модель сохраняеся без фоток
			if (empty($_POST['multiupload_'.\yii\helpers\StringHelper::basename(get_class($this->owner)).'_'.$relation_name]))
				continue;

			// сохраняем старые ID моделей
			$this->old_pk_models[$relation_name] = [];

			if (!empty($this->owner->$relation_name))
			{
				if (!is_object($this->owner->$relation_name))
				{
					foreach ($this->owner->$relation_name as $key=>$record)
					{
						$this->old_pk_models[$relation_name][] = $record->getPrimaryKey();
					}
				}
				else
				{
					$this->old_pk_models[$relation_name][] = $this->owner->$relation_name->getPrimaryKey();
				}
			}

			$POSTs = $this->getPOST($relation_name);

			$this->records[$relation_name] = [];

			if (!empty($POSTs))
			{
				$classModel = "app\models\\".$settings['model'];

				foreach ($POSTs as $key=>$post)
				{
					$mediaModel = new $classModel;
					$media_pk = $mediaModel->tableSchema->primaryKey[0];

					if (!empty($post[$media_pk]))
						$mediaModel = $mediaModel->findOne($post[$media_pk]);

					$mediaModel->attributes = $post;

					// устанавливаем обложку
					/*if ($settings['cover']) так нельзя
					{
						$coverclass = $settings['cover'];
						$this->owner->$coverclass = $mediaModel;
					}*/

					// берем информацию с файла или с модели
					if (!empty($post['file_path']))
						$mediaModel->getImageAttributes($post['file_path'],$post); // загружаем всю информацию c файла в объект
					elseif (!$mediaModel->isNewRecord)
						$mediaModel->getImageAttributes($mediaModel->getImagePath(),$post); // надо переделать

					$this->records[$relation_name][] = $mediaModel;
				}

				// добавляем ошибку у родителя если нет ниодного изображения и оно обязательно
				if (!empty($settings['required']) && empty($this->records[$relation_name]))
					$this->owner->addError($this->owner->primaryKey,'Необходимо добавить изображение');
			}
		}
	}

	public function afterSave($event)
	{
		$Owner = &$this->owner;

		// primary key field
		$pk_field = $Owner->tableSchema->primaryKey[0];

		// primary key value
		$pk_value = $Owner->getPrimaryKey();

		foreach ($this->relations as $relation_name => $settings)
		{
			// если модель сохраняеся без фоток
			if (empty($_POST['multiupload_'.\yii\helpers\StringHelper::basename(get_class($this->owner)).'_'.$relation_name]))
				continue;

			// id моделей
			$ids_models = [];
			$fk_cover = 'NULL';

			foreach ($this->records[$relation_name] as $key=>$mediaModel)
			{
				// проверяем это новый файл или нет
				if ($mediaModel->isNewRecord)
				{
					// сохраняем объект
					if ($mediaModel->save())
					{
						$mediaModel_pk = $mediaModel->getPrimaryKey();

						// если первая в списке то обложка
						if ($mediaModel->cover == 1)
							$fk_cover = $mediaModel_pk;

						// сохраняем само изображение потомучто хранится по хэшу в котором id
						$mediaModel->saveFile();

						// добавляем связочку
						if (!empty($settings['jtable']))
							$this->owner->link($relation_name,$mediaModel);

						$ids_models[] = $mediaModel_pk;
					}
					else
						print_r($mediaModel->Errors);
				}
				else
				{
					if ($mediaModel->save())
					{
						$mediaModel_pk = $mediaModel->getPrimaryKey();

						if ($mediaModel->cover == 1)
							$fk_cover = $mediaModel_pk;

						$ids_models[] = $mediaModel_pk;
					}
				}
			}

			// ID для обложки
			if ($fk_cover=='NULL' || empty($fk_cover))
				$fk_cover = (isset($ids_models[0]))?$ids_models[0]:'NULL';

			$delete_ids = array_diff($this->old_pk_models[$relation_name],$ids_models);

			if (!empty($delete_ids))
			{
				$classModel = "app\models\\".$settings['model'];
				$mediaModel = new $classModel;
				$media_pk = $mediaModel->tableSchema->primaryKey[0];

				$sql = "DELETE FROM ".$mediaModel->tableName()." WHERE `$media_pk` IN (".implode(',', $delete_ids).")";
				Yii::$app->db->createCommand($sql)->execute();
			}

			// удаляем все невошедшие
			/*if (!empty($settings['jtable']))
			{
				$sql = "DELETE FROM ".$model_obj->tableName()."
							WHERE
								$this->fk IN (
									SELECT $this->fk FROM $this->jtable
										WHERE
											$pk_field = $pk_value
										AND $this->fk NOT IN (".implode(',',$ids_models).")
								)";
				Yii::$app->db->createCommand($sql)->execute();
			}*/

			// обновляем обложку
			if (!empty($settings['fk_cover']))
			{
				// ставим NULL если fk Ненашли
				$sql = "UPDATE ".$Owner->tableName()." SET `{$settings['fk_cover']}` = $fk_cover WHERE `$pk_field` = $pk_value";
				Yii::$app->db->createCommand($sql)->execute();

				if ($fk_cover=='NULL')
					$fk_cover = NULL;

				//var_dump($Owner->$settings['fk_cover']);
				$fk_cover_field = $settings['fk_cover'];
				$Owner->$fk_cover_field = $fk_cover;
			}
		}
	}

	public function beforeDelete($event)
	{
		$relation_name = $this->relation;
		$relation = $this->owner->$relation_name;

		if (!is_array($relation) && !empty($relation))
			$relation->delete();
		else
			foreach ($relation as $key => $data)
				$data->delete();
	}

	// сделать превью
	public function makeThumb($option='')
	{
		$relation = strtolower($this->cover);

		if (!empty($this->owner->$relation))
			return $this->owner->$relation->showThumb($option);

		//return Yii::$app->helper->makeThumb($this->noimage,$option);
	}
}