Redirection
===========

Set up automated mobile/desktop/tablet redirection

In this example, we will make sure to activate the automatic redirection to a mobile site http://m.site.com when the user uses a mobile device and desktop http://site.com when the user uses a computer or desktop browser.

If the user reaches the mobile site http://m.site.com, on his desktop browser he should be redirected to the full version at http://site.com.

If the user reaches the desktop site http://site.com, with his mobile he should be redirected to the full version at http://m.site.com.

```yaml
# conﬁg/packages/mobile_detect.yaml
mobile_detect:
  redirect:
    full:
      action: redirect            # redirect, no_redirect, redirect_without_path
      host: http://localhost:8001 # with scheme (http|https), default null, url validate
      is_enabled: true            # default false
      status_code: 301            # default 302
    mobile:
      action: redirect            # redirect, no_redirect, redirect_without_path
      host: http://localhost:8002 # with scheme (http|https), default null, url validate
      is_enabled: true            # default false
      status_code: 301            # default 302
```
