## Chapter 01 - Load from a Config file

**Info:** Before you start, make sure you have read the `Quick Start` guide. *You can find that part in the respository's `README.md` file.*

### I. Create a config file
--------------------------
1. Create a new file: `@app/config/cdn-config.php` and open it in code editor.
2. Now paste the following code:

```php
return [
	'font-awesome' => [
    	'css' => [
        	[
            	'css/font-awesome.min.css',
                '@cdn' => '//cdnjs.cloudflare.com/ajax/libs/font-awesome/4.5.0/css/font-awesome.min.css',
            ]
        ]
    ]
];
```

### II. Make use of config file
---------------------
1. Open `@app/config/main.php` in code editor.
2. Add a new propery `configs` under `cdn` component like the following code:

```php
// ...
'components' => [
	// ...
	'cdn' => [
		//...
		'configs' => [
        	// -------------------------
        	// List of config files
            // -------------------------

            // You can use Yii Alias too
			'@app/config/cdn-config.php',
            
            // or an absolute path
            // dirname(__FILE__) .'/cdn-config.php'
		],
        //...
	],
  // ...
],
// ...
```

### III. Access component
-------------------
```php
/*
  ~ Register assets
*/
$cdn->get('font-awesome')->register();

```