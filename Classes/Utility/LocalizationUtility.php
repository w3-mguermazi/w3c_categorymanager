<?php

declare(strict_types=1);

/*
 * This file is part of the "w3c_category_manager" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace W3code\W3cCategoryManager\Utility;

use TYPO3\CMS\Extbase\Utility\LocalizationUtility as BaseLocalizationUtility;

/**
 * Class LocalizationUtility
 *
 * @author Mehdi Guermazi <mehdi.guermazi@w3code.tn>
 */
class LocalizationUtility
{
    private const EXTENSION_NAME = 'w3c_category_manager';

    /**
     * @param string $key
     * @return string
     */
    public static function translate(string $key): string
    {
        return (string)BaseLocalizationUtility::translate($key, self::EXTENSION_NAME);
    }
}
