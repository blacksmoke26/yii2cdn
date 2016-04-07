# yii2cdn
A Yii Framework 2 component for using assets in different environments (Local/CDNs)

### Quick usage

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
1. Create a new action `test` in your current app's `Site/Controller`.
```php
	// ...
    
    public function actionTest () {
    	return $this->render('test');
    }
    
    // ...
```
2. Now create a file `@app/views/site/test.php` and open into code editor.
3. Paste the following code:

```php
<?php

/*
  Uncomment this line for in development environment
  This line force to use offline files url
  > TRUE will include all the offline config-files/components/sections/files
     and will be skipped @cdn tags
  > FALSE will skip all the offline config-files/components/sections/files
     and will use @cdn tags (for registering urls)
*/
// define('YII2CDN_OFFLINE', true);

// Turn on the code editor autocomplete
/** @var \yii2cdn\Cdn $cdn */
$cdn = Yii::$app->cdn;

/*
  ~ Register all css files
  @see \yii2cdn\Component::get()
  @see \yii2cdn\Component::registerCssFiles()
  @see \yii2cdn\Component::registerJsFiles()
*/
$cdn->get('font-awesome')->register();
```

#### IV. Final moment
1. Open that `test` action in your browser and check the view souce.

> Now it's time to play around, See ya!