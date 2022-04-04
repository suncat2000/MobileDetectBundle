Full reference
==============

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
  cookie_expire_datetime_modifier: '+1 month' # default
  cookie_key: 'device_view'                   # default
  switch_param: 'device_view'                 # default
  device_view_class: 'MobileDetectBundle\Helper\DeviceView'
  request_response_listener_class: 'MobileDetectBundle\EventListener\RequestResponseListener'
  twig_extension_class: 'MobileDetectBundle\Twig\Extension\MobileDetectExtension'
```
