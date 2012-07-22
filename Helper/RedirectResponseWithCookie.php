<?php

namespace SunCat\MobileDetectBundle\Helper;

use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * RedirectResponseWithCookie
 */
class RedirectResponseWithCookie extends RedirectResponse
{
   /**
    * Creates a redirect response so that it conforms to the rules defined for a redirect status code.
    *
    * @param string  $url    The URL to redirect to
    * @param integer $status The status code (302 by default)
    * @param Cookie  $cookie An array of Cookie objects
    */
   public function __construct($url, $status = 302, $cookie = 'full')
   {
        parent::__construct($url, $status);

        if (!$cookie instanceof Cookie) {
            throw new \InvalidArgumentException(sprintf('Third parameter is not a valid Cookie object.'));
        }

        $this->headers->setCookie($cookie);
   }
}