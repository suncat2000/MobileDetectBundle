<?php

/*
 * This file is part of the MobileDetectBundle.
 *
 * (c) Nikolay Ivlev <nikolay.kotovsky@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SunCat\MobileDetectBundle\DeviceDetector;

/**
 *
 * @author vidy videni <vidy.videni@gmail.com>
 *
 */
interface DeviceDetectorInterface
{

    /**
     * Set the User-Agent to be used.
     *
     * @param string $userAgent The user agent string to set.
     *
     * @return string|null
     */
    public function setUserAgent($userAgent);

    /**
     * @return boolean
     */
    public function isTablet();

    /**
     * @return boolean
     */
    public function isMobile();
    
    /**
     * Is device
     * @param string $deviceName is[iPhone|BlackBerry|HTC|Nexus|Dell|Motorola|Samsung|Sony|Asus|Palm|Vertu|...]
     *
     * @return boolean
     */
    public function isDevice($deviceName);
}