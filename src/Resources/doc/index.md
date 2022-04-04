You can also create route specific rules for redirecting in your routing.yml. Just add appropriate platform(s) to the
options field and add a redirect rule.

```yaml
# conﬁg/routes/mobile_detect.yaml
someaction:
  path: /someaction
  controller: Your\Controller\ClassController::someAction
  options: { mobile: redirect, tablet: no_redirect, full: redirect_without_path } # redirect, no_redirect, redirect_without_path
```

Twig Helper
-----------

```twig
{% if is_mobile() %}
{% if is_tablet() %}
{% if is_device('iphone') %} # magic methods is[...]
{% if is_ios() %}
{% if is_android_os() %}
{% if is_windows_os() %}
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

Usage Example:
--------------

#### Setting up redirection to and from a mobile site that is the same Symfony instance as your main site.

In this example, let's assume that you have a website http://site.com and you wish to activate redirection to a mobile
site http://m.site.com when the user is using a mobile device.

Additionally, when a user with a desktop browser reaches the mobile site http://m.site.com, he should be redirected to
the full version at http://site.com.

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

Now when you hit http://site.com with a mobile device, you are redirected to http://m.site.com. At this point if
the http://m.site.com is configured to point to your project, you will get circular reference error. To get rid of the
circular reference error, we want to disable mobile redirecting when we land on our mobile site.

2. **Create a new `app.php` file with a name like, for example, `app_mobile.php` and change the following:**

  ```php
  $kernel = new AppKernel('prod', false);
  ```

to:

  ```php
  $kernel = new AppKernel('mobile', false);
  ```

Now your mobile site has its own environment and we can nicely create some custom configuration for it, disable mobile
redirecting and activate desktop redirection instead.

3. **Create `config_mobile.yml` next to your `config.yml` and disable mobile redirecting. This should take care of the
   circular reference errors. Adding the `full` configuration activates desktop redirection.**

Also you might want to define your routing file as mobile specific. If you do, just create new `routing_mobile.yml`
file and use it just like the default `routing.yml`. This gives you nice opportunity to route requests to custom mobile
specific controllers that can render views that are designed for mobile. This way you don't need to write platform
specific conditions to your view files.

  ```yaml
  framework:
    router:
      resource: '%kernel.root_dir%/config/routing_mobile.yml'

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

4. **Configure your http server: Make sure that in your http server virtual host, you make http://m.site.com
   use `app_mobile.php` as its script file instead of `app.php`.**

After you have restarted your http server everything should work. Also remember to clear the cache if you do changes to
configs or you might end to get frustrated for nothing.
