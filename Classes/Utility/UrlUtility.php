<?php

declare(strict_types=1);

/*
 * This file is part of the "w3c_category_manager" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace W3code\W3cCategoryManager\Utility;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder;

/**
 * Class UrlUtility
 *
 * @author Mehdi Guermazi <mehdi.guermazi@w3code.tn>
 */
class UrlUtility
{
    /**
     * @param string $action
     * @param string $controller
     * @param string $extension
     * @param string $plugin
     * @param array $controllerArguments
     * @param array $additionalArguments
     * @param bool $absoluteUri
     * @return string
     */
    public static function generate(
        string $action,
        string $controller,
        string $extension,
        string $plugin,
        array $controllerArguments = [],
        array $additionalArguments = [],
        bool $absoluteUri = true
    ): string {
        return self::getUriBuilder()
            ->reset()
            ->setCreateAbsoluteUri($absoluteUri)
            ->setArguments($additionalArguments)
            ->uriFor($action, $controllerArguments, $controller, $extension, $plugin);
    }

    /**
     * @return UriBuilder
     */
    private static function getUriBuilder(): UriBuilder
    {
        return GeneralUtility::makeInstance(UriBuilder::class);
    }
}
