<?php

/*
 * This file is part of the MobileDetectBundle.
 *
 * (c) Nikolay Ivlev <nikolay.kotovsky@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SunCat\MobileDetectBundle\Helper;

use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * RedirectResponseWithCookie
 *
 * @author suncat2000 <nikolay.kotovsky@gmail.com>
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
    public function __construct($url, $status = 302, Cookie $cookie)
    {
        parent::__construct($url, $status);

        $this->headers->setCookie($cookie);
    }
}
