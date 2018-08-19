<p align="center">
    <a href="https://github.com/yiisoft" target="_blank">
        <img src="https://avatars0.githubusercontent.com/u/993323" height="100px">
    </a>
    <h1 align="center">Bitrix24 Extension for Yii 2</h1>
    <br>
</p>

Installation
------------

# Download and install
```
composer require valkinaz/yii2-bitrix24
```

# Preparation

Create 2 files in your config directory. You can see examples at ```src``` folder:

- bitrix24.json
This is a configuration of component. You must set your individual "host", "client_id", "client_secret", "application_uri", "access_token" and "refresh_token". Provide for this file permissions so that the script can overwrite it in the future.

- bitrix24.php
This file using bitrix24.json and prepares component for Yii config.

Then include component at the config:
```
$bitrix24 = require __DIR__ . '/bitrix24.php';

'components' => [
	'bitrix24' => $bitrix24,
]
```

# Usage

Create contact for example. In the $response variable you'll get response from your CRM.
```
$post = [
    'fields' => [
        'NAME' => 'Example_name',
        'PHONE' => [
            [
                'VALUE' => '79999999999',
                'VALUE_TYPE' => 'MOBILE'
            ]
        ],
        'ASSIGNED_BY_ID' => 1
    ]
];

$response = Yii::$app->bitrix24->Send('crm.contact.add', 'POST', $post);
```
