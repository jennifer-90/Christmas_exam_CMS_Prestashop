<?php
/**
 * 2007-2019 PrestaShop
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to http://www.prestashop.com for more information.
 *
 *  @author    PrestaShop SA <contact@prestashop.com>
 *  @copyright 2007-2019 PrestaShop SA
 *  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *  International Registered Trademark & Property of PrestaShop SA
 */

namespace SumUp\Authentication;

/**
 * Class AccessToken
 *
 * @package SumUp\Authentication
 */
class AccessToken
{
    /**
     * The access token value.
     *
     * @var string
     */
    protected $value = '';

    /**
     * The access token type.
     *
     * @var string
     */
    protected $type = '';

    /**
     * The number of seconds the access token will be valid.
     *
     * @var int
     */
    protected $expiresIn;

    /**
     * The scopes for this access token.
     *
     * @var array
     */
    protected $scope;

    /**
     * The refresh token.
     *
     * @var string
     */
    protected $refreshToken;

    /**
     * Create a new access token entity.
     *
     * @param string $value
     * @param string $type
     * @param int    $expiresIn
     * @param array  $scope
     * @param string $refreshToken
     */
    public function __construct($value, $type = '', $expiresIn = -1, array $scope = array(), $refreshToken = null)
    {
        if ($value) {
            $this->value = $value;
        }
        if ($type) {
            $this->type = $type;
        }
        if ($expiresIn) {
            $this->expiresIn = $expiresIn;
        }
        if ($scope) {
            $this->scope = $scope;
        }
        if ($refreshToken) {
            $this->refreshToken = $refreshToken;
        }
    }

    /**
     * Returns the access token.
     *
     * @return string
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Returns the type of the access token.
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Returns the total number of seconds that the token will be valid.
     *
     * @return int
     */
    public function getExpiresIn()
    {
        return $this->expiresIn;
    }

    /**
     * Returns the scopes for the current access token.
     *
     * @return array
     */
    public function getScopes()
    {
        return $this->scope;
    }

    /**
     * Returns the refresh token if any.
     *
     * @return null|string
     */
    public function getRefreshToken()
    {
        return $this->refreshToken;
    }
}
