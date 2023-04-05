<?php
/**
 * NOTICE OF LICENSE
 *
 * @author Mondial Relay <offrestart@mondialrelay.fr>
 * @copyright Copyright (c) Mondial Relay
 * @license http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */

require_once _PS_MODULE_DIR_ . '/mondialrelay/classes/services/MondialrelayService.php';

/*
** Some tools using used in the module
*/
class MondialrelayTools
{
    const REGEX_CLEAN_ADDR = '/[^a-zA-Z0-9-\s\'\!\,\|\(\)\.\*\&\#\/\:]/';

    const REGEX_CLEAN_PHONE = '/[^0-9+\(\)]*/';

    /**
     * Checks if a zipcode is valid according to its country
     *
     * @param string $zipcode
     * @param string $iso_country

     * @return boolean
     */
    public static function checkZipcodeByCountry($zipcode, $iso_country)
    {
        $zipcodeFormat = Db::getInstance()->getValue('
                SELECT `zip_code_format`
                FROM `'._DB_PREFIX_."country`
                WHERE `iso_code` = '". pSQL($iso_country) . "'");

        if (!$zipcodeFormat) {
            return true;
        }

        $regxMask = str_replace(
            array('N', 'C', 'L'),
            array(
                '[0-9]',
                $iso_country,
                '[a-zA-Z]'
            ),
            $zipcodeFormat
        );

        return preg_match('/^'.$regxMask.'$/', $zipcode);
    }

    /**
     * Formats a (french) phonenumber
     *
     * @param string $phone_number
     *
     * @return string
     */
    public static function getFormattedPhonenumber($phone_number)
    {
        if (!$phone_number) {
            return '';
        }
        $begin      = Tools::substr($phone_number, 0, 3);
        $pad_number = (strpos($begin, '+3') !== false) ? 12 :
            (strpos($begin, '00') ? 13 : 10);

        return str_pad(
            Tools::substr(preg_replace(self::REGEX_CLEAN_PHONE, '', $phone_number), 0, $pad_number),
            $pad_number,
            '0',
            STR_PAD_LEFT
        );
    }
    
    /**
     * Checks for SOAP and cURL availability
     * @return boolean
     */
    public static function checkDependencies()
    {
        $loadedExtensions = get_loaded_extensions();
        return in_array('curl', $loadedExtensions) && in_array('soap', $loadedExtensions);
    }
    
    /**
     * Returns an array of hooks in which the module is not registered
     * @return array
     */
    public static function getModuleMissingHooks()
    {
        // @see Module::isRegisteredInHook
        $module = Module::getInstanceByName('mondialrelay');
        
        $hooksAliases = Hook::getHookAliasList();
        
        $missingHooks = array();
        foreach ($module->hooks as $hook) {
            $hook = isset($hooksAliases[$hook]) ? $hooksAliases[$hook] : $hook;
            if (!$module->isRegisteredInHook($hook)) {
                $missingHooks[] = $hook;
            }
        }
        
        return $missingHooks;
    }
    
    /**
     * Checks that every required configuration field is filled for the current shop
     * context
     */
    public static function checkWebserviceConfiguration()
    {
        $conf = Configuration::getMultiple(array(
            Mondialrelay::WEBSERVICE_ENSEIGNE,
            Mondialrelay::WEBSERVICE_BRAND_CODE,
            Mondialrelay::WEBSERVICE_KEY,
            Mondialrelay::WEIGHT_COEFF,
            Mondialrelay::LABEL_LANG,
        ));
        
        return count($conf) == count(array_filter($conf));
    }
    
    /**
     * Formats an array for a select list, by creating an array of array with
     * the keys and values of the original array in 'label' and 'value' fields
     *
     * @param array $array
     * @return array
     */
    public static function formatArrayForSelect($array)
    {
        return array_map(
            function ($v, $l) {
                return array(
                    'label' => $l,
                    'value' => $v,
                );
            },
            array_keys($array),
            $array
        );
    }
    
    /**
     * Checks the webservice connection with specific information, by trying to
     * retrieve a relay list
     *
     * @param string $enseigne
     * @param string $key
     * @param string $errors an error array filled by the function
     *
     * @return boolean
     */
    public static function checkWebserviceConnection($enseigne, $key, &$errors = array())
    {
        $params = array(
            'CP' => array(
                // We need the iso_country to validate the zipcode format
                'zipcode' => '75000',
                'iso_country' => 'FR',
            ),
            'Pays' => 'FR',
        );

        try {
            $service = MondialrelayService::getService('Relay_Search');
            $service->setEnseigne($enseigne);
            $service->setPrivateKey($key);

            // Set data
            if (!$service->init(array($params))) {
                foreach ($service->getErrors() as $itemErrors) {
                    foreach ($itemErrors as $error) {
                        $errors[] = $error;
                    }
                }
                return false;
            }

            // Send data
            if (!$service->send()) {
                foreach ($service->getErrors() as $itemErrors) {
                    foreach ($itemErrors as $error) {
                        $errors[] = $error;
                    }
                }
                return false;
            }

            $result = $service->getResult();

            $statCode = $result[0]->STAT;
            if ($statCode == 0) {
                return true;
            } else {
                $errors[] = $service->getErrorFromStatCode($result[0]->STAT);
            }
        } catch (Exception $e) {
            $errors[] = $e->getMessage();
        }
        
        return false;
    }
    
    /**
     * The brand code is never used by the webservice, so we have implement a
     * validation function somewhere.
     *
     * @param string $brandCode
     *
     * @return boolean
     */
    public static function validateBrandCode($brandCode)
    {
        return preg_match("#^[0-9]{2}$#", $brandCode);
    }
    
    /**
     * Sets a delivery address for a cart and all its products and
     * customizations.
     * We can't just set the id_address_delivery, because the cart_product and
     * customization tables also have delivery addresses.
     *
     * @param Cart $cart
     * @param int $id_address_delivery
     */
    public static function setCartDeliveryAddress($cart, $id_address_delivery)
    {
        $id_address_invoice = $cart->id_address_invoice;
        $cart->updateAddressId($cart->id_address_delivery, $id_address_delivery);
        $cart->id_address_invoice = $id_address_invoice;
    }
    
    /**
     * From an array of shop ids, returns only those where the module is
     * enabled
     *
     * @param int $id_module
     * @param array $id_shop_list
     * @return array
     */
    public static function getShopsWithModuleEnabled($id_module, $id_shop_list)
    {
        $query = new DbQuery();
        $query->select('id_shop')
            ->from('module_shop')
            ->where('id_module = ' . (int) $id_module)
            ->where('id_shop IN (' . implode(', ', array_map(
                function ($i) {
                    return (int)$i;
                },
                $id_shop_list
            )) . ')');
        $res = Db::getInstance()->executeS($query);
        if (!$res) {
            return array();
        }
        
        $return = array();
        foreach ($res as $row) {
            $return[] = $row['id_shop'];
        }
        return $return;
    }

    /**
     * Gets the language code by a post code and a country ISO code.
     *
     * @param int $postCode The post code.
     * @param string $countryIso The country ISO code.
     * @return string The language code.
     * @since 3.0.13
     */
    public static function getLanguageByPostCode($postCode, $countryIso)
    {
        if ($countryIso == 'BE') {
            // Test for FR postcode.
            if (($postCode >= 1000 && $postCode <= 1499)
                || ($postCode >= 4000 && $postCode <= 7999)
                || ($postCode >= 10000 && $postCode <= 99999)
            ) {
                return 'FR';
            }

            // Test for NL postcode.
            if (($postCode >= 1500 && $postCode <= 3999)
                || ($postCode >= 8000 && $postCode <= 9999)
            ) {
                return 'NL';
            }

            // FR by default for Belgium.
            return 'FR';
        } elseif ($countryIso == 'ES') {
            return 'ES';
        } elseif ($countryIso == 'NL') {
            return 'NL';
        } elseif ($countryIso == 'FR') {
            return 'FR';
        } else {
            return 'EN';
        }
    }

    public function mbstring_binary_safe_encoding($reset = false)
    {
        static $encodings  = array();
        static $overloaded = null;

        if (is_null($overloaded)) {
            $overloaded = function_exists('mb_internal_encoding') && (ini_get('mbstring.func_overload') & 2);
        }

        if (false === $overloaded) {
            return;
        }

        if (!$reset) {
            $encoding = mb_internal_encoding();
            array_push($encodings, $encoding);
            mb_internal_encoding('ISO-8859-1');
        }

        if ($reset && $encodings) {
            $encoding = array_pop($encodings);
            mb_internal_encoding($encoding);
        }
    }

    private function seems_utf8($str)
    {
        self::mbstring_binary_safe_encoding();
        $length = strlen($str);
        self::mbstring_binary_safe_encoding(true);
        for ($i = 0; $i < $length; $i++) {
            $c = ord($str[$i]);
            if ($c < 0x80) {
                $n = 0;
            }
            // 0bbbbbbb
            elseif (($c & 0xE0) == 0xC0) {
                $n = 1;
            }
            // 110bbbbb
            elseif (($c & 0xF0) == 0xE0) {
                $n = 2;
            }
            // 1110bbbb
            elseif (($c & 0xF8) == 0xF0) {
                $n = 3;
            }
            // 11110bbb
            elseif (($c & 0xFC) == 0xF8) {
                $n = 4;
            }
            // 111110bb
            elseif (($c & 0xFE) == 0xFC) {
                $n = 5;
            }
            // 1111110b
            else {
                return false;
            }
            // Does not match any model
            for ($j = 0; $j < $n; $j++) {
                // n bytes matching 10bbbbbb follow ?
                if ((++$i == $length) || ((ord($str[$i]) & 0xC0) != 0x80)) {
                    return false;
                }

            }
        }
        return true;
    }

    public static function remove_accents($string)
    {
        if (!preg_match('/[\x80-\xff]/', $string)) {
            return $string;
        }

        if (self::seems_utf8($string)) {
            $chars = array(
                // Decompositions for Latin-1 Supplement
                'ª' => 'a', 'º'  => 'o',
                'À' => 'A', 'Á'  => 'A',
                'Â' => 'A', 'Ã'  => 'A',
                'Ä' => 'A', 'Å'  => 'A',
                'Æ' => 'AE', 'Ç' => 'C',
                'È' => 'E', 'É'  => 'E',
                'Ê' => 'E', 'Ë'  => 'E',
                'Ì' => 'I', 'Í'  => 'I',
                'Î' => 'I', 'Ï'  => 'I',
                'Ð' => 'D', 'Ñ'  => 'N',
                'Ò' => 'O', 'Ó'  => 'O',
                'Ô' => 'O', 'Õ'  => 'O',
                'Ö' => 'O', 'Ù'  => 'U',
                'Ú' => 'U', 'Û'  => 'U',
                'Ü' => 'U', 'Ý'  => 'Y',
                'Þ' => 'TH', 'ß' => 's',
                'à' => 'a', 'á'  => 'a',
                'â' => 'a', 'ã'  => 'a',
                'ä' => 'a', 'å'  => 'a',
                'æ' => 'ae', 'ç' => 'c',
                'è' => 'e', 'é'  => 'e',
                'ê' => 'e', 'ë'  => 'e',
                'ì' => 'i', 'í'  => 'i',
                'î' => 'i', 'ï'  => 'i',
                'ð' => 'd', 'ñ'  => 'n',
                'ò' => 'o', 'ó'  => 'o',
                'ô' => 'o', 'õ'  => 'o',
                'ö' => 'o', 'ø'  => 'o',
                'ù' => 'u', 'ú'  => 'u',
                'û' => 'u', 'ü'  => 'u',
                'ý' => 'y', 'þ'  => 'th',
                'ÿ' => 'y', 'Ø'  => 'O',
                // Decompositions for Latin Extended-A
                'Ā' => 'A', 'ā'  => 'a',
                'Ă' => 'A', 'ă'  => 'a',
                'Ą' => 'A', 'ą'  => 'a',
                'Ć' => 'C', 'ć'  => 'c',
                'Ĉ' => 'C', 'ĉ'  => 'c',
                'Ċ' => 'C', 'ċ'  => 'c',
                'Č' => 'C', 'č'  => 'c',
                'Ď' => 'D', 'ď'  => 'd',
                'Đ' => 'D', 'đ'  => 'd',
                'Ē' => 'E', 'ē'  => 'e',
                'Ĕ' => 'E', 'ĕ'  => 'e',
                'Ė' => 'E', 'ė'  => 'e',
                'Ę' => 'E', 'ę'  => 'e',
                'Ě' => 'E', 'ě'  => 'e',
                'Ĝ' => 'G', 'ĝ'  => 'g',
                'Ğ' => 'G', 'ğ'  => 'g',
                'Ġ' => 'G', 'ġ'  => 'g',
                'Ģ' => 'G', 'ģ'  => 'g',
                'Ĥ' => 'H', 'ĥ'  => 'h',
                'Ħ' => 'H', 'ħ'  => 'h',
                'Ĩ' => 'I', 'ĩ'  => 'i',
                'Ī' => 'I', 'ī'  => 'i',
                'Ĭ' => 'I', 'ĭ'  => 'i',
                'Į' => 'I', 'į'  => 'i',
                'İ' => 'I', 'ı'  => 'i',
                'Ĳ' => 'IJ', 'ĳ' => 'ij',
                'Ĵ' => 'J', 'ĵ'  => 'j',
                'Ķ' => 'K', 'ķ'  => 'k',
                'ĸ' => 'k', 'Ĺ'  => 'L',
                'ĺ' => 'l', 'Ļ'  => 'L',
                'ļ' => 'l', 'Ľ'  => 'L',
                'ľ' => 'l', 'Ŀ'  => 'L',
                'ŀ' => 'l', 'Ł'  => 'L',
                'ł' => 'l', 'Ń'  => 'N',
                'ń' => 'n', 'Ņ'  => 'N',
                'ņ' => 'n', 'Ň'  => 'N',
                'ň' => 'n', 'ŉ'  => 'n',
                'Ŋ' => 'N', 'ŋ'  => 'n',
                'Ō' => 'O', 'ō'  => 'o',
                'Ŏ' => 'O', 'ŏ'  => 'o',
                'Ő' => 'O', 'ő'  => 'o',
                'Œ' => 'OE', 'œ' => 'oe',
                'Ŕ' => 'R', 'ŕ'  => 'r',
                'Ŗ' => 'R', 'ŗ'  => 'r',
                'Ř' => 'R', 'ř'  => 'r',
                'Ś' => 'S', 'ś'  => 's',
                'Ŝ' => 'S', 'ŝ'  => 's',
                'Ş' => 'S', 'ş'  => 's',
                'Š' => 'S', 'š'  => 's',
                'Ţ' => 'T', 'ţ'  => 't',
                'Ť' => 'T', 'ť'  => 't',
                'Ŧ' => 'T', 'ŧ'  => 't',
                'Ũ' => 'U', 'ũ'  => 'u',
                'Ū' => 'U', 'ū'  => 'u',
                'Ŭ' => 'U', 'ŭ'  => 'u',
                'Ů' => 'U', 'ů'  => 'u',
                'Ű' => 'U', 'ű'  => 'u',
                'Ų' => 'U', 'ų'  => 'u',
                'Ŵ' => 'W', 'ŵ'  => 'w',
                'Ŷ' => 'Y', 'ŷ'  => 'y',
                'Ÿ' => 'Y', 'Ź'  => 'Z',
                'ź' => 'z', 'Ż'  => 'Z',
                'ż' => 'z', 'Ž'  => 'Z',
                'ž' => 'z', 'ſ'  => 's',
                // Decompositions for Latin Extended-B
                'Ș' => 'S', 'ș'  => 's',
                'Ț' => 'T', 'ț'  => 't',
                // Euro Sign
                '€' => 'E',
                // GBP (Pound) Sign
                '£' => '',
                // Vowels with diacritic (Vietnamese)
                // unmarked
                'Ơ' => 'O', 'ơ'  => 'o',
                'Ư' => 'U', 'ư'  => 'u',
                // grave accent
                'Ầ' => 'A', 'ầ'  => 'a',
                'Ằ' => 'A', 'ằ'  => 'a',
                'Ề' => 'E', 'ề'  => 'e',
                'Ồ' => 'O', 'ồ'  => 'o',
                'Ờ' => 'O', 'ờ'  => 'o',
                'Ừ' => 'U', 'ừ'  => 'u',
                'Ỳ' => 'Y', 'ỳ'  => 'y',
                // hook
                'Ả' => 'A', 'ả'  => 'a',
                'Ẩ' => 'A', 'ẩ'  => 'a',
                'Ẳ' => 'A', 'ẳ'  => 'a',
                'Ẻ' => 'E', 'ẻ'  => 'e',
                'Ể' => 'E', 'ể'  => 'e',
                'Ỉ' => 'I', 'ỉ'  => 'i',
                'Ỏ' => 'O', 'ỏ'  => 'o',
                'Ổ' => 'O', 'ổ'  => 'o',
                'Ở' => 'O', 'ở'  => 'o',
                'Ủ' => 'U', 'ủ'  => 'u',
                'Ử' => 'U', 'ử'  => 'u',
                'Ỷ' => 'Y', 'ỷ'  => 'y',
                // tilde
                'Ẫ' => 'A', 'ẫ'  => 'a',
                'Ẵ' => 'A', 'ẵ'  => 'a',
                'Ẽ' => 'E', 'ẽ'  => 'e',
                'Ễ' => 'E', 'ễ'  => 'e',
                'Ỗ' => 'O', 'ỗ'  => 'o',
                'Ỡ' => 'O', 'ỡ'  => 'o',
                'Ữ' => 'U', 'ữ'  => 'u',
                'Ỹ' => 'Y', 'ỹ'  => 'y',
                // acute accent
                'Ấ' => 'A', 'ấ'  => 'a',
                'Ắ' => 'A', 'ắ'  => 'a',
                'Ế' => 'E', 'ế'  => 'e',
                'Ố' => 'O', 'ố'  => 'o',
                'Ớ' => 'O', 'ớ'  => 'o',
                'Ứ' => 'U', 'ứ'  => 'u',
                // dot below
                'Ạ' => 'A', 'ạ'  => 'a',
                'Ậ' => 'A', 'ậ'  => 'a',
                'Ặ' => 'A', 'ặ'  => 'a',
                'Ẹ' => 'E', 'ẹ'  => 'e',
                'Ệ' => 'E', 'ệ'  => 'e',
                'Ị' => 'I', 'ị'  => 'i',
                'Ọ' => 'O', 'ọ'  => 'o',
                'Ộ' => 'O', 'ộ'  => 'o',
                'Ợ' => 'O', 'ợ'  => 'o',
                'Ụ' => 'U', 'ụ'  => 'u',
                'Ự' => 'U', 'ự'  => 'u',
                'Ỵ' => 'Y', 'ỵ'  => 'y',
                // Vowels with diacritic (Chinese, Hanyu Pinyin)
                'ɑ' => 'a',
                // macron
                'Ǖ' => 'U', 'ǖ'  => 'u',
                // acute accent
                'Ǘ' => 'U', 'ǘ'  => 'u',
                // caron
                'Ǎ' => 'A', 'ǎ'  => 'a',
                'Ǐ' => 'I', 'ǐ'  => 'i',
                'Ǒ' => 'O', 'ǒ'  => 'o',
                'Ǔ' => 'U', 'ǔ'  => 'u',
                'Ǚ' => 'U', 'ǚ'  => 'u',
                // grave accent
                'Ǜ' => 'U', 'ǜ'  => 'u',
            );

            $string = strtr($string, $chars);
        } else {
            $chars = array();
            // Assume ISO-8859-1 if not UTF-8
            $chars['in'] = "\x80\x83\x8a\x8e\x9a\x9e"
                . "\x9f\xa2\xa5\xb5\xc0\xc1\xc2"
                . "\xc3\xc4\xc5\xc7\xc8\xc9\xca"
                . "\xcb\xcc\xcd\xce\xcf\xd1\xd2"
                . "\xd3\xd4\xd5\xd6\xd8\xd9\xda"
                . "\xdb\xdc\xdd\xe0\xe1\xe2\xe3"
                . "\xe4\xe5\xe7\xe8\xe9\xea\xeb"
                . "\xec\xed\xee\xef\xf1\xf2\xf3"
                . "\xf4\xf5\xf6\xf8\xf9\xfa\xfb"
                . "\xfc\xfd\xff";

            $chars['out'] = "EfSZszYcYuAAAAAACEEEEIIIINOOOOOOUUUUYaaaaaaceeeeiiiinoooooouuuuyy";

            $string              = strtr($string, $chars['in'], $chars['out']);
            $double_chars        = array();
            $double_chars['in']  = array("\x8c", "\x9c", "\xc6", "\xd0", "\xde", "\xdf", "\xe6", "\xf0", "\xfe");
            $double_chars['out'] = array('OE', 'oe', 'AE', 'DH', 'TH', 'ss', 'ae', 'dh', 'th');
            $string              = str_replace($double_chars['in'], $double_chars['out'], $string);
        }

        return $string;
    }
}
