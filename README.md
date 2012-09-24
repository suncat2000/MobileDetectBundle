MobileDetectBundle
=============

Symfony2 bundle for detect mobile devices, manage mobile view and redirect to the mobile and tablet version.

Introduction
============

This Bundle use [php-mobile-detect](https://github.com/suncat2000/php-mobile-detect) (fork of [Mobile_Detect](http://code.google.com/p/php-mobile-detect/) class) and provides the following features:

* Detect the various mobile devices by Name, OS, browser User-Agent
* Manages site views for the variuos mobile devices (`mobile`, `tablet`, `full`, `not_mobile`)
* Redirects to mobile and tablet sites


Switch device view
============

For switch device view, use `device_view` GET parameter:

````
http://site.com?device_view={full/mobile/tablet}
````

Installation
============

### Composer

Add to `composer.json` in your project to `require` section:

````
...
    {
        "suncat/php-mobile-detect": "2.0.9",
        "suncat/mobile-detect-bundle": "dev-master"
    }
...
````

Run command:
`php composer.phar install`

### Submodules

Add this bundle and `php-mobile-detect` library to your project as Git submodules:
````
$ git submodule add git://github.com/suncat2000/php-mobile-detect.git vendor/php-mobile-detect
$ git submodule add git://github.com/suncat2000/MobileDetectBundle.git vendor/bundles/SunCat/MobileDetectBundle
````

Register the namespace `SunCat` to your project's autoloader bootstrap script:

``` php
//app/autoload.php
$loader->registerNamespaces(array(
    // ...
    'SunCat' => __DIR__.'/../vendor/bundles',
    // ...
));

$loader->registerPreﬁxes(array(
    // ...
    'Mobile_' => __DIR__.'/../vendor/php-mobile-detect/lib',
    // ...
));
```
### Add this bundle to your application's kernel
``` php
//app/AppKernel.php
public function registerBundles()
{
    return array(
         // ...
        new SunCat\MobileDetectBundle\MobileDetectBundle(),
        // ...
    );
}
```
### Conﬁgure service in your YAML conﬁguration
````
#app/conﬁg/conﬁg.yml
mobile_detect:
    redirect:
        mobile: ~
        tablet: ~
    switch_device_view: ~
````

### Full conﬁguration

````
#app/conﬁg/conﬁg.yml
mobile_detect:
    redirect:
        mobile:
            is_enabled: true            # default false
            host: http://m.site.com     # with scheme (http|https), default null, url validate
            status_code: 301            # default 302
        tablet:
            is_enabled: true            # default false
            host: http://t.site.com     # with scheme (http|https), default null, url validate
            status_code: 301            # default 302
    switch_device_view:
        save_referer_path: false        # default true
                                        # true  redirectUrl = http://site.com/current/path
                                        # false redirectUrl = http://site.com
                                    
````

PHP examples
============

### Check type device
``` php
$mobileDetector = $this->get('mobile_detect.mobile_detector');
$mobileDetector->isMobile();
$mobileDetector->isTablet()
```

### Check phone
**is[iPhone|HTC|Nexus|Dell|Motorola|Samsung|Sony|Asus|Palm|Vertu|GenericPhone]**

``` php
$mobileDetector->isIphone();
$mobileDetector->isHTC();
etc.
```

### Check tablet
**is[BlackBerryTablet|iPad|Kindle|SamsungTablet|HTCtablet|MotorolaTablet|AsusTablet|NookTablet|AcerTablet|
YarvikTablet|GenericTablet]**

```php
$mobileDetector->isIpad();
$mobileDetector->isMotorolaTablet();
etc.
```

### Check mobile OS
**is[AndroidOS|BlackBerryOS|PalmOS|SymbianOS|WindowsMobileOS|iOS|badaOS]**

```php
$mobileDetector->isAndroidOS();
$mobileDetector->isIOS();
```
### Check mobile browser User-Agent
**is[Chrome|Dolfin|Opera|Skyfire|IE|Firefox|Bolt|TeaShark|Blazer|Safari|Midori|GenericBrowser]**

```php
$mobileDetector->isChrome();
$mobileDetector->isSafari();
```

Twig Helper
============
````
{% if is_mobile() %}
{% if is_tablet() %}
{% if is_device('iphone') %} # magic methods is[...]
````
````
{% if is_full_view() %}
{% if is_mobile_view() %}
{% if is_tablet_view() %}
{% if is_not_mobile_view() %}
````

Twig examples
============

````
{% extends is_mobile() ? "MyBundle:Layout:mobile.html.twig" : "MyBundle:Layout:full.html.twig" %}
````

````
{% if is_mobile_view() %}
    {% extends "MyBundle:Layout:mobile.html.twig" %}
{% else if is_tablet_view() %}
    {% extends "MyBundle:Layout:tablet.html.twig" %}
{% else if is_full_view() or is_not_mobile_view() %}
    {% extends "MyBundle:Layout:full.html.twig" %}
{% endif %}
````

````
{% if is_device('iphone') %}
    <link rel="stylesheet" href="{{ asset('css/iphone.css') }}" type="text/css" />
{% endif %}
````

TODO
---------

* Add conﬁg param `detect_tablet_how_mobile` (default false) and ﬁx detect functional for support this param
* Add twig function `url_for_switch_view('mobile')` for generation switch view url's