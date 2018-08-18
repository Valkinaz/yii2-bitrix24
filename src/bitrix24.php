<?php
	use yii\helpers\{ArrayHelper, Json};

	$path = __DIR__ . '/bitrix24.json';

	$config = file_get_contents($path);
	$config = Json::decode($config);

	return ArrayHelper::merge($config, ['config_path' => $path]);
