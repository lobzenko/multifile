<?php

use yii\helpers\Html;

\lobzenko\multifile\MultiFileAsset::register($this);

$uniq_id = substr(md5(time().rand(0,9999)),0,10);
?>

<?php
	if (!empty($attribute))
		echo Html::activeHiddenInput($model,$attribute)
?>
<div id="uploader<?=$uniq_id?>" class="file-uploader-place">
	<noscript>
		<p>Please enable JavaScript to use file uploader.</p>
	</noscript>
</div>
<input type="hidden" name="multiupload_<?=$POST_relation_name?>" value="1" />
<?php
$records = json_encode($records);

if (!empty($extensions)) {
	$allowedExtensions = "allowedExtensions: ['".implode("','",$extensions)."'],";
}

$script = <<< JS
	$(document).ready(function(){
		$("#uploader$uniq_id").multiupload(
			{
				url: '$url',
				group: $group,
				single: $single,
				relationname: '$POST_relation_name',
				records: $records,
				$allowedExtensions
				showPreview: $showPreview,
				tpl: $template
			}
		);
	});
JS;

$this->registerJs($script, yii\web\View::POS_END);
?>