<?php

declare(strict_types=1);

/*
 * This file is part of the MobileDetectBundle.
 *
 * (c) Nikolay Ivlev <nikolay.kotovsky@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace MobileDetectBundle\DeviceDetector;

/**
 * @method bool isiPhone()
 * @method bool isBlackBerry()
 * @method bool isPixel()
 * @method bool isHTC()
 * @method bool isNexus()
 * @method bool isDell()
 * @method bool isMotorola()
 * @method bool isSamsung()
 * @method bool isLG()
 * @method bool isSony()
 * @method bool isAsus()
 * @method bool isXiaomi()
 * @method bool isNokiaLumia()
 * @method bool isMicromax()
 * @method bool isPalm()
 * @method bool isVertu()
 * @method bool isPantech()
 * @method bool isFly()
 * @method bool isWiko()
 * @method bool isiMobile()
 * @method bool isSimValley()
 * @method bool isWolfgang()
 * @method bool isAlcatel()
 * @method bool isNintendo()
 * @method bool isAmoi()
 * @method bool isINQ()
 * @method bool isOnePlus()
 * @method bool isGenericPhone()
 * @method bool isiPad()
 * @method bool isNexusTablet()
 * @method bool isGoogleTablet()
 * @method bool isSamsungTablet()
 * @method bool isKindle()
 * @method bool isSurfaceTablet()
 * @method bool isHPTablet()
 * @method bool isAsusTablet()
 * @method bool isBlackBerryTablet()
 * @method bool isHTCtablet()
 * @method bool isMotorolaTablet()
 * @method bool isNookTablet()
 * @method bool isAcerTablet()
 * @method bool isToshibaTablet()
 * @method bool isLGTablet()
 * @method bool isFujitsuTablet()
 * @method bool isPrestigioTablet()
 * @method bool isLenovoTablet()
 * @method bool isDellTablet()
 * @method bool isYarvikTablet()
 * @method bool isMedionTablet()
 * @method bool isArnovaTablet()
 * @method bool isIntensoTablet()
 * @method bool isIRUTablet()
 * @method bool isMegafonTablet()
 * @method bool isEbodaTablet()
 * @method bool isAllViewTablet()
 * @method bool isArchosTablet()
 * @method bool isAinolTablet()
 * @method bool isNokiaLumiaTablet()
 * @method bool isSonyTablet()
 * @method bool isPhilipsTablet()
 * @method bool isCubeTablet()
 * @method bool isCobyTablet()
 * @method bool isMIDTablet()
 * @method bool isMSITablet()
 * @method bool isSMiTTablet()
 * @method bool isRockChipTablet()
 * @method bool isFlyTablet()
 * @method bool isbqTablet()
 * @method bool isHuaweiTablet()
 * @method bool isNecTablet()
 * @method bool isPantechTablet()
 * @method bool isBronchoTablet()
 * @method bool isVersusTablet()
 * @method bool isZyncTablet()
 * @method bool isPositivoTablet()
 * @method bool isNabiTablet()
 * @method bool isKoboTablet()
 * @method bool isDanewTablet()
 * @method bool isTexetTablet()
 * @method bool isPlaystationTablet()
 * @method bool isTrekstorTablet()
 * @method bool isPyleAudioTablet()
 * @method bool isAdvanTablet()
 * @method bool isDanyTechTablet()
 * @method bool isGalapadTablet()
 * @method bool isMicromaxTablet()
 * @method bool isKarbonnTablet()
 * @method bool isAllFineTablet()
 * @method bool isPROSCANTablet()
 * @method bool isYONESTablet()
 * @method bool isChangJiaTablet()
 * @method bool isGUTablet()
 * @method bool isPointOfViewTablet()
 * @method bool isOvermaxTablet()
 * @method bool isHCLTablet()
 * @method bool isDPSTablet()
 * @method bool isVistureTablet()
 * @method bool isCrestaTablet()
 * @method bool isMediatekTablet()
 * @method bool isConcordeTablet()
 * @method bool isGoCleverTablet()
 * @method bool isModecomTablet()
 * @method bool isVoninoTablet()
 * @method bool isECSTablet()
 * @method bool isStorexTablet()
 * @method bool isVodafoneTablet()
 * @method bool isEssentielBTablet()
 * @method bool isRossMoorTablet()
 * @method bool isiMobileTablet()
 * @method bool isTolinoTablet()
 * @method bool isAudioSonicTablet()
 * @method bool isAMPETablet()
 * @method bool isSkkTablet()
 * @method bool isTecnoTablet()
 * @method bool isJXDTablet()
 * @method bool isiJoyTablet()
 * @method bool isFX2Tablet()
 * @method bool isXoroTablet()
 * @method bool isViewsonicTablet()
 * @method bool isVerizonTablet()
 * @method bool isOdysTablet()
 * @method bool isCaptivaTablet()
 * @method bool isIconbitTablet()
 * @method bool isTeclastTablet()
 * @method bool isOndaTablet()
 * @method bool isJaytechTablet()
 * @method bool isBlaupunktTablet()
 * @method bool isDigmaTablet()
 * @method bool isEvolioTablet()
 * @method bool isLavaTablet()
 * @method bool isAocTablet()
 * @method bool isMpmanTablet()
 * @method bool isCelkonTablet()
 * @method bool isWolderTablet()
 * @method bool isMediacomTablet()
 * @method bool isMiTablet()
 * @method bool isNibiruTablet()
 * @method bool isNexoTablet()
 * @method bool isLeaderTablet()
 * @method bool isUbislateTablet()
 * @method bool isPocketBookTablet()
 * @method bool isKocasoTablet()
 * @method bool isHisenseTablet()
 * @method bool isHudl()
 * @method bool isTelstraTablet()
 * @method bool isGenericTablet()
 * @method bool isAndroidOS()
 * @method bool isBlackBerryOS()
 * @method bool isPalmOS()
 * @method bool isSymbianOS()
 * @method bool isWindowsMobileOS()
 * @method bool isWindowsPhoneOS()
 * @method bool isiOS()
 * @method bool isiPadOS()
 * @method bool isSailfishOS()
 * @method bool isMeeGoOS()
 * @method bool isMaemoOS()
 * @method bool isJavaOS()
 * @method bool iswebOS()
 * @method bool isbadaOS()
 * @method bool isBREWOS()
 * @method bool isChrome()
 * @method bool isDolfin()
 * @method bool isOpera()
 * @method bool isSkyfire()
 * @method bool isEdge()
 * @method bool isIE()
 * @method bool isFirefox()
 * @method bool isBolt()
 * @method bool isTeaShark()
 * @method bool isBlazer()
 * @method bool isSafari()
 * @method bool isWeChat()
 * @method bool isUCBrowser()
 * @method bool isbaiduboxapp()
 * @method bool isbaidubrowser()
 * @method bool isDiigoBrowser()
 * @method bool isMercury()
 * @method bool isObigoBrowser()
 * @method bool isNetFront()
 * @method bool isGenericBrowser()
 * @method bool isPaleMoon()
 * @method bool isBot()
 * @method bool isMobileBot()
 * @method bool isDesktopMode()
 * @method bool isTV()
 * @method bool isWebKit()
 * @method bool isConsole()
 * @method bool isWatch()
 */
interface MobileDetectorInterface
{
    /**
     * Magic overloading method.
     *
     * @method bool is[...]()
     *
     * @param string $name
     * @param array  $arguments
     *
     * @throws \BadMethodCallException when the method doesn't exist and doesn't start with 'is'
     */
    public function __call($name, $arguments);

    /**
     * Retrieve the list of known browsers. Specifically, the user agents.
     *
     * @return array list of browsers / user agents
     */
    public static function getBrowsers();

    /**
     * Retrieve the list of mobile operating systems.
     *
     * @return array the list of mobile operating systems
     */
    public static function getOperatingSystems();

    /**
     * Retrieve the list of known phone devices.
     *
     * @return array list of phone devices
     */
    public static function getPhoneDevices();

    /**
     * Get the properties array.
     *
     * @return array
     */
    public static function getProperties();

    /**
     * Get the current script version.
     * This is useful for the demo.php file,
     * so people can check on what version they are testing
     * for mobile devices.
     *
     * @return string the version number in semantic version format
     */
    public static function getScriptVersion();

    /**
     * Retrieve the list of known tablet devices.
     *
     * @return array list of tablet devices
     */
    public static function getTabletDevices();

    /**
     * Alias for getBrowsers() method.
     *
     * @return array list of user agents
     */
    public static function getUserAgents();

    /**
     * Retrieve the list of known utilities.
     *
     * @return array list of utilities
     */
    public static function getUtilities();

    /**
     * Check the HTTP headers for signs of mobile.
     * This is the fastest mobile check possible; it's used
     * inside isMobile() method.
     *
     * @return bool
     */
    public function checkHttpHeadersForMobile();

    /**
     * Retrieves the cloudfront headers.
     *
     * @return array
     */
    public function getCfHeaders();

    /**
     * Set CloudFront headers
     * http://docs.aws.amazon.com/AmazonCloudFront/latest/DeveloperGuide/header-caching.html#header-caching-web-device.
     *
     * @param array $cfHeaders List of HTTP headers
     *
     * @return bool If there were CloudFront headers to be set
     */
    public function setCfHeaders($cfHeaders = null);

    /**
     * Retrieves a particular header. If it doesn't exist, no exception/error is caused.
     * Simply null is returned.
     *
     * @param string $header The name of the header to retrieve. Can be HTTP compliant such as
     *                       "User-Agent" or "X-Device-User-Agent" or can be php-esque with the
     *                       all-caps, HTTP_ prefixed, underscore seperated awesomeness.
     *
     * @return string|null the value of the header
     */
    public function getHttpHeader($header);

    /**
     * Retrieves the HTTP headers.
     *
     * @return array
     */
    public function getHttpHeaders();

    /**
     * Set the HTTP Headers. Must be PHP-flavored. This method will reset existing headers.
     *
     * @param array $httpHeaders The headers to set. If null, then using PHP's _SERVER to extract
     *                           the headers. The default null is left for backwards compatibility.
     */
    public function setHttpHeaders($httpHeaders = null);

    public function getMatchesArray();

    public function getMatchingRegex();

    public function getMobileHeaders();

    /**
     * Get all possible HTTP headers that
     * can contain the User-Agent string.
     *
     * @return array list of HTTP headers
     */
    public function getUaHttpHeaders();

    /**
     * Retrieve the User-Agent.
     *
     * @return string|null the user agent if it's set
     */
    public function getUserAgent();

    /**
     * Set the User-Agent to be used.
     *
     * @param string $userAgent the user agent string to set
     *
     * @return string|null
     */
    public function setUserAgent($userAgent = null);

    /**
     * This method checks for a certain property in the
     * userAgent.
     *
     * @param string $key
     *
     * @return bool|int|null
     */
    public function is($key);

    /**
     * Check if the device is mobile.
     * Returns true if any type of mobile device detected, including special ones.
     *
     * @return bool
     */
    public function isMobile();

    /**
     * Check if the device is a tablet.
     * Return true if any type of tablet device is detected.
     *
     * @return bool
     */
    public function isTablet();

    /**
     * Some detection rules are relative (not standard),
     * because of the diversity of devices, vendors and
     * their conventions in representing the User-Agent or
     * the HTTP headers.
     *
     * This method will be used to check custom regexes against
     * the User-Agent string.
     *
     * @param $regex
     * @param string $userAgent
     *
     * @return bool
     */
    public function match($regex, $userAgent = null);

    /**
     * Prepare the version number.
     *
     * @param string $ver The string version, like "2.6.21.2152";
     *
     * @return float
     */
    public function prepareVersionNo($ver);

    /**
     * Check the version of the given property in the User-Agent.
     * Will return a float number. (eg. 2_0 will return 2.0, 4.3.1 will return 4.31).
     *
     * @param string $propertyName The name of the property. See self::getProperties() array
     *                             keys for all possible properties.
     * @param string $type         Either self::VERSION_TYPE_STRING to get a string value or
     *                             self::VERSION_TYPE_FLOAT indicating a float value. This parameter
     *                             is optional and defaults to self::VERSION_TYPE_STRING. Passing an
     *                             invalid parameter will default to the this type as well.
     *
     * @return string|float the version of the property we are trying to extract
     */
    public function version($propertyName, $type = MobileDetector::VERSION_TYPE_STRING);
}
