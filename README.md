![Mobile Detect](https://user-images.githubusercontent.com/10502887/161483098-d40a2d7d-0e78-4f38-a7ac-49390718746e.png)

MobileDetectBundle
==================

Symfony 3.4.x-6.0.x bundle for detect mobile devices, manage mobile view and redirect to the mobile and tablet version.

[![Github Actions Status](https://github.com/tattali/MobileDetectBundle/actions/workflows/main.yml/badge.svg?branch=main
)](https://github.com/tattali/MobileDetectBundle/actions/workflows/main.yml?query=branch%3Amain) [![Latest Stable Version](http://poser.pugx.org/tattali/mobile-detect-bundle/v)](https://packagist.org/packages/tattali/mobile-detect-bundle) [![Total Downloads](http://poser.pugx.org/tattali/mobile-detect-bundle/downloads)](https://packagist.org/packages/tattali/mobile-detect-bundle) [![codecov](https://codecov.io/gh/tattali/MobileDetectBundle/branch/main/graph/badge.svg?token=HWV1OYRSD9)](https://codecov.io/gh/tattali/MobileDetectBundle) [![License](http://poser.pugx.org/tattali/mobile-detect-bundle/license)](https://packagist.org/packages/tattali/mobile-detect-bundle) [![PHP Version Require](http://poser.pugx.org/tattali/mobile-detect-bundle/require/php)](https://packagist.org/packages/tattali/mobile-detect-bundle)

*This bundle is a fork of [suncat2000/MobileDetectBundle](https://github.com/suncat2000/MobileDetectBundle). As this project doesn't look maintained anymore, we decided to create & maintain a fork. For more information read my [manifest](https://github.com/tattali/MobileDetectBundle/issues/8).*

Introduction
------------

This Bundle use [Mobile_Detect](https://github.com/serbanghita/Mobile-Detect) class and provides the following features:

* Detect the various mobile devices by Name, OS, browser User-Agent
* Manages site views for the various mobile devices (`mobile`, `tablet`, `full`)
* Redirects to mobile and tablet sites

Documentation
-------------

### Installation

```sh
composer require tattali/mobile-detect-bundle
```

### Usage

#### Checking device

```php
use MobileDetectBundle\DeviceDetector\MobileDetectorInterface;

public function someaction(MobileDetectorInterface $mobileDetector)
{
    $mobileDetector->isMobile();
    $mobileDetector->isTablet();
    $mobileDetector->is('iPhone');
}
```

```twig
{% if is_mobile() %}
{% if is_tablet() %}
{% if is_device('iPhone') %} # magic methods is[...]
```

#### Switch device view

For switch device view, use `device_view` GET parameter:

```url
http://localhost:8000?device_view={full/mobile/tablet}
```

Or using the Symfony toolbar
![mbd-bundle-sf-toolbar](https://user-images.githubusercontent.com/10502887/161488224-aaedde1c-d3c3-4636-8761-a207fbd5d4ff.png)

#### Going further

- [Symfony legacy versions](src/Resources/doc/legacy-versions.md)
- [Full reference](src/Resources/doc/reference.md)

Contribute and feedback
-----------------------

Any feedback and contribution will be very appreciated.

License and credits
-------

This bundle is under the MIT license. See the complete [license](src/Resources/meta/LICENSE) in the bundle

Original authors: [suncat2000](https://github.com/suncat2000), [HenriVesala](https://github.com/HenriVesala), [netmikey](https://github.com/netmikey) and [all contributors](https://github.com/suncat2000/MobileDetectBundle/graphs/contributors)
