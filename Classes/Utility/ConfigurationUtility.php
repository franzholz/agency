<?php
declare(strict_types=1);

namespace JambageCom\Agency\Utility;

use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationExtensionNotConfiguredException;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationPathDoesNotExistException;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Utility\GeneralUtility;

use JambageCom\Agency\Constants\Extension;


class ConfigurationUtility
{
    /**
     * @param string $path
     * @return string
     * @throws ExtensionConfigurationExtensionNotConfiguredException
     * @throws ExtensionConfigurationPathDoesNotExistException
     */
    public static function getExtensionConfiguration(string $path = ''): ?string
    {
        $result = null;

        try {
            $result =  GeneralUtility::makeInstance(ExtensionConfiguration::class)->get(Extension::KEY, $path);
        }
        catch (ExtensionConfigurationExtensionNotConfiguredException | ExtensionConfigurationPathDoesNotExistException) {
        }
        return $result;
    }
}

