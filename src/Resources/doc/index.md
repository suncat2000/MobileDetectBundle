MobileDetectBundle
=============

Symfony 3.4.x-6.0.x bundle for detect mobile devices, manage mobile view and redirect to the mobile and tablet version.

Installation
------------

### Composer

#### For Symfony ^5.0 || ^6.0

Run command:
```sh
composer require tattali/mobile-detect-bundle
```

#### For Symfony >= 3.4 || <= 4.4

Run command:
```sh
composer require "tattali/mobile-detect-bundle:2.1.*"
```

Or add to `composer.json` in your project to `require` section:
```json
{
    "tattali/mobile-detect-bundle": "2.1.*"
}
```

and run command:
```sh
composer update tattali/mobile-detect-bundle
```

### Full reference

You can change default behaviour of your redirects with action parameter:

- `redirect`: redirects to appropriate host with your current path
- `no_redirect`: no redirection (default behaviour)
- `redirect_without_path`: redirects to appropriate host index page

```yaml
# conﬁg/packages/mobile_detect.yaml
mobile_detect:
  redirect:
    full:
      action: redirect            # redirect, no_redirect, redirect_without_path
      host: http://site.com       # with scheme (http|https), default null, url validate
      is_enabled: true            # default false
      status_code: 301            # default 302
    mobile:
      action: redirect            # redirect, no_redirect, redirect_without_path
      host: http://m.site.com     # with scheme (http|https), default null, url validate
      is_enabled: true            # default false
      status_code: 301            # default 302
    tablet:
      action: redirect            # redirect, no_redirect, redirect_without_path
      host: http://t.site.com     # with scheme (http|https), default null, url validate
      is_enabled: true            # default false
      status_code: 301            # default 302
    detect_tablet_as_mobile: true # default false

  service:
    mobile_detector: mobile_detect.mobile_detector.default

  switch_device_view:
    save_referer_path: false                  # default true
                                              # true  redirectUrl = http://site.com/current/path?currentQuery=string
                                              # false redirectUrl = http://site.com
  cookie_expire_datetime_modifier: "+1 month" # default
  cookie_key: "device_view"                   # default
  switch_param: "device_view"                 # default
  device_view_class: "MobileDetectBundle\Helper\DeviceView"
  request_response_listener_class: "MobileDetectBundle\EventListener\RequestResponseListener"
  twig_extension_class: "MobileDetectBundle\Twig\Extension\MobileDetectExtension"
```

You can also create route specific rules for redirecting in your routing.yml.
Just add appropriate platform(s) to the options field and add a redirect rule.

```yaml
# conﬁg/routes/mobile_detect.yaml
someaction:
  path: /someaction
  controller: Your\Controller\ClassController::someAction
  options: { mobile: redirect, tablet: no_redirect, full: redirect_without_path } # redirect, no_redirect, redirect_without_path
```

### Switch device view

For switch device view, use `device_view` GET parameter:

````
http://site.com?device_view={full/mobile/tablet}
````
### Symfony toolbar
![](https://raw.githubusercontent.com/suncat2000/MobileDetectBundle/master/Resources/doc/sf-toolbar.png)

PHP examples
------------

### Check type device
```php
$mobileDetector = $this->get('mobile_detect.mobile_detector');
$mobileDetector->isMobile();
$mobileDetector->isTablet()
```

### Check phone
**is[iPhone|HTC|Nexus|Dell|Motorola|Samsung|Sony|Asus|Palm|Vertu|GenericPhone]**

```php
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
-----------

```twig
{% if is_mobile() %}
{% if is_tablet() %}
{% if is_device('iphone') %} # magic methods is[...]
{% if is_ios() %}
{% if is_android_os() %}
```

```twig
{% if is_full_view() %}
{% if is_mobile_view() %}
{% if is_tablet_view() %}
{% if is_not_mobile_view() %}
```

```twig
{{ full_view_url() }}       # with current path and query. http://fullsite.com/current/path?param1=value1&param2=value2
{{ full_view_url(false) }}  # to configure host only (without /current/path?param1=value1&param2=value2). http://fullsite.com
<a href="{{ full_view_url() }}" title="Full view">Full view</a>
```

Twig examples
-------------

```twig
{% extends is_mobile() ? "MyBundle:Layout:mobile.html.twig" : "MyBundle:Layout:full.html.twig" %}
```

```twig
{% if is_mobile_view() %}
  {% extends "MyBundle:Layout:mobile.html.twig" %}
{% else if is_tablet_view() %}
  {% extends "MyBundle:Layout:tablet.html.twig" %}
{% else if is_full_view() or is_not_mobile_view() %}
  {% extends "MyBundle:Layout:full.html.twig" %}
{% endif %}
```

```twig
{% if is_device('iphone') %}
  <link rel="stylesheet" href="{{ asset('css/iphone.css') }}" type="text/css" />
{% endif %}
```

```twig
{% if is_mobile_view() %}
  <link rel="canonical" href="{{ full_view_url() }}" />
{% endif %}
```

Usage Example:
--------------

#### Setting up redirection to and from a mobile site that is the same Symfony instance as your main site.

In this example, let's assume that you have a website http://site.com and you wish to activate
redirection to a mobile site http://m.site.com when the user is using a mobile device.

Additionally, when a user with a desktop browser reaches the mobile site http://m.site.com, he
should be redirected to the full version at http://site.com.

1. **Set up mobile redirection to your `conﬁg/packages/mobile_detect.yaml`**

  ```yaml
  mobile_detect:
    redirect:
      mobile:
        action: redirect
        host: http://m.site.com
        is_enabled: true
        status_code: 301
      tablet: ~
    switch_device_view: ~
  ```

  Now when you hit http://site.com with a mobile device, you are redirected to http://m.site.com.
  At this point if the http://m.site.com is configured to point to your project, you will get circular reference error.
  To get rid of the circular reference error, we want to disable mobile redirecting when we land on our mobile site.

2. **Create a new `app.php` file with a name like, for example, `app_mobile.php` and change the following:**
  ```php
  $kernel = new AppKernel('prod', false);
  ```
  to:
  ```php
  $kernel = new AppKernel('mobile', false);
  ```
  Now your mobile site has its own environment and we can nicely create some custom configuration for it, disable
  mobile redirecting and activate desktop redirection instead.

3. **Create `config_mobile.yml` next to your `config.yml` and disable mobile redirecting. This should take care of the circular reference errors. Adding the `full` configuration activates desktop redirection.**

  Also you might want to define your routing file as mobile specific. If you do, just create new `routing_mobile.yml`
  file and use it just like the default `routing.yml`. This gives you nice opportunity to route requests to
  custom mobile specific controllers that can render views that are designed for mobile. This way you don't need to write
  platform specific conditions to your view files.

  ```yaml
  framework:
    router:
      resource: "%kernel.root_dir%/config/routing_mobile.yml"

  mobile_detect:
    redirect:
      mobile:
        is_enabled: false
      tablet: ~
      full:
        is_enabled: true
        host: http://site.com
    switch_device_view: ~
  ```

4. **Configure your http server: Make sure that in your http server virtual host, you make http://m.site.com use `app_mobile.php` as its script file instead of `app.php`.**

  After you have restarted your http server everything should work.
  Also remember to clear the cache if you do changes to configs or you might end to get frustrated for nothing.
