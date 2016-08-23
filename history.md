Yii2 CDN History
=======================

## v0.2.0 (purple)

 * (New) Component and Section custom attributes.
 * (New) `Basepath` property and related methods.
 * (New) Traits (`Url`, `File` and `Attributes`)
 * (New) Replaceable tags (Filename only)
 ```
 @thisComponentUrl , @thisSectionUrl
 ```
 * (New) Inheritable attributes (in attribute tags)

 `@sectionsAttrs` and `@filesAttrs`
 
 ```php
'component-id' => [
		'@attributes' => [
			// ...
			'@sectionsAttrs' => [
			    // ...
				'@filesAttrs' => [
		        	// ...
				],
			]
		],
		// sections
	],
```
 * (New) Functional file attributes
 ```php
 // Appends file modified time at the end of url.
 // ...file.js?v=############
'timestamp'=>true, 
// Set false to disable registring file as asset.
// For JavaScript and CSS files only
'registrable'=>false, 
```
 * Various issues resolved and major code improvements.

## v0.1.4
 * Fixed PHP version issue (now supports PHP 7).
 * Minor code issues fixed.
 * Bug fixed: File '' not found.

## v0.1.3
 * Bug fixed: Callback wasn't triggering.

## v0.1.2 (critical fix)
 * Bug fixed: Undefined component id given.
 
## v0.1.1
 * (bug fixed) Wrong section name in Component.
 * Wiki and Documentation added.
 
## v0.1.0 (green)
 * First release