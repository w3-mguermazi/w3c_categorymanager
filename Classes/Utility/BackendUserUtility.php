<?php

declare(strict_types=1);

/*
 * This file is part of the "w3c_categorymanager" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace W3code\W3cCategoryManager\Utility;

use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;

/**
 * Class BackendUserUtility
 *
 * @author Mehdi Guermazi <mehdi.guermazi@w3code.tn>
 * @author Haythem Daoud <haythem.daoud@w3code.tn>
 */
class BackendUserUtility
{
    /**
     * Get backend user
     *
     * @return BackendUserAuthentication
     */
    public static function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }
}
