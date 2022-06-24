Redirection
===========

Set up automated mobile/desktop/tablet redirection

In this example, we will make sure to activate the automatic redirection to a mobile site http://m.example.com when the user uses a mobile device and desktop http://example.com when the user uses a computer or desktop browser.

If the user reaches the mobile site http://m.example.com, on his desktop browser he should be redirected to the full version at http://example.com.

If the user reaches the desktop site http://example.com, with his mobile he should be redirected to the full version at http://m.example.com.

```env
# .env
REDIRECT_DESKTOP=http://example.com
REDIRECT_MOBILE=http://m.example.com
```
```yaml
# conﬁg/packages/mobile_detect.yaml
mobile_detect:
  redirect:
    full:
      action: redirect                # redirect, no_redirect, redirect_without_path
      host: '%env(REDIRECT_DESKTOP)%' # with scheme (http|https), default null, url validate
      is_enabled: true                # default false
      status_code: 301                # default 302
    mobile:
      action: redirect                # redirect, no_redirect, redirect_without_path
      host: '%env(REDIRECT_MOBILE)%'  # with scheme (http|https), default null, url validate
      is_enabled: true                # default false
      status_code: 301                # default 302
```

Then you can create your Controllers and constrain your actions to match each host

```php
// src/Controller/DesktopController.php
<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

#[Route(name: 'desktop_', host: '%env(REDIRECT_DESKTOP)%')]
class DesktopController extends AbstractController
{
    #[Route("/", name: "homepage")]
    public function homepage()
    {
        // dd('desktop');
    }
}
```

```php
// src/Controller/MobileController.php
<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

#[Route(name: 'mobile_', host: '%env(REDIRECT_MOBILE)%')]
class MobileController extends AbstractController
{
    #[Route("/", name: "homepage")]
    public function homepage()
    {
        // dd('mobile');
    }
}
```

Or use it directly on your action
```php
// src/Controller/MainController.php
<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Routing\Annotation\Route;

class MainController extends Controller
{
    #[Route("/myAction", name: 'my_action', host: '%env(REDIRECT_MOBILE)%')]
    public function myAction()
    {
        // dd('myAction');
    }
}
```

If your host contain a port (for local testing)
```php
// src/Controller/MobileController.php
<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

#[Route(name: 'mobile_', condition: "env('REDIRECT_MOBILE') === request.getSchemeAndHttpHost()")]
class MobileController extends AbstractController
{
    #[Route("/", name: "homepage")]
    public function homepage()
    {
        // dd('mobile');
    }
}
