# yii2cdn
A Yii Framework 2 component for using assets in different environments (Local/CDNs)

**Notice:** *This is an experimental release. You may face difficulties, bugs or strange issues.*

Wiki: [https://github.com/blacksmoke26/yii2cdn/wiki](https://github.com/blacksmoke26/yii2cdn/wiki)

### Quick Setup

**Info:** *This tutorial will demonstrate how to use [`Font-Awesome`](https://github.com/FortAwesome/Font-Awesome) library in a <code>production</code> (online/CDN) or <code>development</code> (local/offline) environment.*

#### I. Installing a library
--------------------------
1. Create a <code>cdn</code> directory under your root folder.
2. Install or download [`FortAwesome/Font-Awesome`](https://github.com/FortAwesome/Font-Awesome) library under <code>cdn</code> directory.
  * Path should be `/cdn/font-awesome`.

#### II. Add a component
---------------------
1. Open `@app/config/main.php` in your code editor.
2. Add a new propery `cdn` under `components` section like following code:

```php
// ...
'components' => [
	// ...
	'cdn' => [
		'class' => '\yii2cdn\Cdn',
		'baseUrl' => '/cdn',
		'basePath' => dirname(dirname(__DIR__)) . '/cdn',
		'components' => [
        	'font-awesome' => [
            	'css' => [
                	'css/font-awesome.min.css', // offline version
                    '@cdn' => '//cdnjs.cloudflare.com/ajax/libs/font-awesome/'
                     		. '4.5.0/css/font-awesome.min.css', // online version
                ]
            ]
		],
	],
  // ...
],
// ...
```

#### III. Registering assets
-------------------
1. Open any view file and paste the following line:

```php
//...
Yii::$app->cdn->get('font-awesome')->register();
//...
```

#### IV. Final moment
1. Browse the action url in your browser and check the view souce.

> Now it's time to play around, See ya!