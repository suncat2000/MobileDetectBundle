MobileDetectBundle
=============

Symfony2 bundle for detect mobile devices, manage mobile view and redirect to the mobile and tablet version.

[![knpbundles.com](http://knpbundles.com/suncat2000/MobileDetectBundle/badge-short)](http://knpbundles.com/suncat2000/MobileDetectBundle)

Introduction
============

This Bundle use [Mobile_Detect](https://github.com/serbanghita/Mobile-Detect) class and provides the following features:

* Detect the various mobile devices by Name, OS, browser User-Agent
* Manages site views for the variuos mobile devices (`mobile`, `tablet`, `full`)
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
        "mobiledetect/mobiledetectlib": "dev-master",
        "suncat/mobile-detect-bundle": "dev-master"
    }
...
````

Run command:
`php composer.phar install`

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

You can change default behaviour of your redirects with action parameter:

- `redirect`: redirects to appropriate host with your current path
- `no_redirect`: no redirection (default behaviour)
- `redirect_without_path`: redirects to appropriate host index page

````
#app/conﬁg/conﬁg.yml
mobile_detect:
    redirect:
        mobile:
            is_enabled: true            # default false
            host: http://m.site.com     # with scheme (http|https), default null, url validate
            status_code: 301            # default 302
            action: redirect            # redirect, no_redirect, redirect_without_path
        tablet:
            is_enabled: true            # default false
            host: http://t.site.com     # with scheme (http|https), default null, url validate
            status_code: 301            # default 302
            action: redirect            # redirect, no_redirect, redirect_without_path
    switch_device_view:
        save_referer_path: false        # default true
                                        # true  redirectUrl = http://site.com/current/path
                                        # false redirectUrl = http://site.com
````

You can also create route specific rules for redirecting in your routing.yml.
Just put appropriate platform to options field and add it redirecting rule.

````
#routing.yml

someaction:
    pattern:  /someaction
    defaults: { _controller: YourBundle:Index:someaction }
    options:  { mobile: redirect, tablet: no_redirect }         # redirect, no_redirect, redirect_without_path
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

Usage Example:
============

### Setting up redirection to mobile site that is the same Symfony 2 instance as your main site.

In this example lets assume that you have a website http://site.com and you wish just to activate
redirection to mobile site http://m.site.com when user is using mobile device.

1. Set up mobile redirection to your config.yml

    ````
    mobile_detect:
        redirect:
            mobile:
                is_enabled: true
                host: http://m.site.com
                status_code: 301
                action: redirect
            tablet: ~
        switch_device_view: ~
    ````

    Now when you hit to http://site.com you are redirected to http://m.site.com.
    At this point if the http://m.site.com is configured to point to your project you will get circular reference error.
    To get rid of the circular reference error we want to disable mobile redirecting when we land on our mobile site.

2. Crete new app.php file that has name for example app_mobile.php and change following:
    ```php
    $kernel = new AppKernel('prod', false);
    ```
    to:
    ```php
    $kernel = new AppKernel('mobile', false);
    ```
    Now your mobile site has its own environment and we can nicely create some custom configuration for it and disable
    mobile redirecting.


3. Create config_mobile.yml next to your config.yml and disable mobile redirecting. This should take care of the circular
    reference errors.

    Also you might  want to define your routing file as mobile specific. If you do, just create new routing_mobile.yml
    file and use it just like the default routing.yml.  This gives you nice opportunity to route requests to
    custom mobile specific controllers that can render views that are designed for mobile. This way you don't need to write
    platform specific conditions to your view files.

    ````
    framework:
        router:
            resource: "%kernel.root_dir%/config/routing_mobile.yml"


    mobile_detect:
        redirect:
            mobile:
                is_enabled: false
            tablet: ~
        switch_device_view: ~
    ````

4. Config your http server. Make sure that in your http server virtual host you make http://m.site.com to use app_mobile.php as its script file
    instead of app.php.

    After you have restarted your http server everything should work.
    Also remember to clear the cache if you do changes to configs or you might end to get frustrated for nothing.



TODO
---------

* Add conﬁg param `detect_tablet_how_mobile` (default false) and ﬁx detect functional for support this param
* Add twig function `url_for_switch_view('mobile')` for generation switch view url's
