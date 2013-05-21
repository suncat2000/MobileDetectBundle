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
 * MobileDetector class
 *
 * Extends Mobile_Detect
 *
 * @author suncat2000 <nikolay.kotovsky@gmail.com>
 *
 */
class MobileDetector extends \Mobile_Detect
{
    /**
     * Constructor
     *
     * @param array $phoneDevices
     * @param array $tabletDevices
     * @param array $operatingSystems
     * @param array $userAgents
     * @param array $utilities
     * @param array $properties
     */
    public function __construct(
        array $phoneDevices = array(),
        array $tabletDevices = array(),
        array $operatingSystems = array(),
        array $userAgents = array(),
        array $utilities = array(),
        array $properties = array()
    ) {
        $this->phoneDevices = array_merge($this->phoneDevices, $phoneDevices);
        $this->tabletDevices = array_merge($this->tabletDevices, $tabletDevices);
        $this->operatingSystems = array_merge($this->operatingSystems, $operatingSystems);
        $this->userAgents = array_merge($this->userAgents, $userAgents);
        $this->utilities = array_merge($this->utilities, $utilities);
        $this->properties = array_merge($this->properties, $properties);

        parent::__construct();
    }
}
