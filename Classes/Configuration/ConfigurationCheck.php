<?php

namespace JambageCom\Agency\Configuration;

/***************************************************************
*  Copyright notice
*
*  (c) 2016-2016 Stanislas Rolland <typo3(arobas)sjbr.ca>
*  All rights reserved
*
*  This script is part of the Typo3 project. The Typo3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*  A copy is found in the textfile GPL.txt and important notices to the license
*  from the author is found in LICENSE.txt distributed with these scripts.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/
/**
 *
 * Part of the agency (Agency Registration) extension.
 *
 * Check the configuration and extension requirements
 *
 * @author	Stanislas Rolland <typo3(arobas)sjbr.ca>
 * @author	Franz Holzinger <franz@ttproducts.de>
 * @maintainer	Franz Holzinger <franz@ttproducts.de>
 *
 *
 */

class ConfigurationCheck {

	/* Checks requirements for this plugin
	 *
	 * @return string Error message, if error found, empty string otherwise
	 */
	static public function checkRequirements ($conf, $extensionKey) {
		$content = '';
		$requiredExtensions = array();
		$loginSecurityLevel = $GLOBALS['TYPO3_CONF_VARS']['FE']['loginSecurityLevel'];

			// Check if all required extensions are available
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$extensionKey]['constraints']['depends'])) {
			$requiredExtensions =
				array_diff(
					array_keys($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$extensionKey]['constraints']['depends']),
					array('php', 'typo3')
				);
		}

		if (
			$loginSecurityLevel == 'rsa' ||
			(
				\tx_agency_controldata::enableAutoLoginOnConfirmation($conf, '')
			)
		) {
			$requiredExtensions[] = 'rsaauth';
		}

		foreach ($requiredExtensions as $extension) {
			if (!\t3lib_extMgm::isLoaded($extension)) {
				$message = sprintf($GLOBALS['TSFE']->sL('LLL:EXT:' . $extensionKey . '/pi/locallang.xml:internal_required_extension_missing'), $extension);
				\t3lib_div::sysLog($message, $extensionKey, \t3lib_div::SYSLOG_SEVERITY_ERROR);
				$content .= sprintf($GLOBALS['TSFE']->sL('LLL:EXT:' . $extensionKey . '/pi/locallang.xml:internal_check_requirements_frontend'), $message);
			}
		}

			// Check if any conflicting extension is available
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$extensionKey]['constraints']['conflicts'])) {
			$conflictingExtensions =
				array_keys($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$extensionKey]['constraints']['conflicts']);
		}

		foreach ($conflictingExtensions as $extension) {
			if (\t3lib_extMgm::isLoaded($extension)) {
				$message = sprintf($GLOBALS['TSFE']->sL('LLL:EXT:' . $extensionKey . '/pi/locallang.xml:internal_conflicting_extension_installed'), $extension);
				\t3lib_div::sysLog($message, $extensionKey, \t3lib_div::SYSLOG_SEVERITY_ERROR);
				$content .= sprintf($GLOBALS['TSFE']->sL('LLL:EXT:' . $extensionKey . '/pi/locallang.xml:internal_check_requirements_frontend'), $message);
			}
		}

		return $content;
	}

	/**
	 * Checks security settings
	 *
	 * @param string $extensionKey the extension key
	 * @return string Error message, if error found, empty string otherwise
	 */
	static public function checkSecuritySettings ($extensionKey)
	{
		$content = '';
		$loginSecurityLevel = $GLOBALS['TYPO3_CONF_VARS']['FE']['loginSecurityLevel'];

		// Check if front end login security level is correctly set
		$supportedTransmissionSecurityLevels = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$extensionKey]['loginSecurityLevels'];

		if (!in_array($loginSecurityLevel, $supportedTransmissionSecurityLevels)) {
			$message = $GLOBALS['TSFE']->sL('LLL:EXT:' . $extensionKey . '/pi/locallang.xml:internal_login_security_level');
			\t3lib_div::sysLog($message, $extensionKey, \t3lib_div::SYSLOG_SEVERITY_ERROR);
			$content .= sprintf($GLOBALS['TSFE']->sL('LLL:EXT:' . $extensionKey . '/pi/locallang.xml:internal_check_requirements_frontend'), $message);
		} else {
				// Check if salted passwords are enabled in front end
			if (\t3lib_extMgm::isLoaded('saltedpasswords')) {
				if (!\tx_saltedpasswords_div::isUsageEnabled('FE')) {
					$message = $GLOBALS['TSFE']->sL('LLL:EXT:' . $extensionKey . '/pi/locallang.xml:internal_salted_passwords_disabled');
					\t3lib_div::sysLog($message, $extensionKey, \t3lib_div::SYSLOG_SEVERITY_ERROR);
					$content .= sprintf($GLOBALS['TSFE']->sL('LLL:EXT:' . $extensionKey . '/pi/locallang.xml:internal_check_requirements_frontend'), $message);
				} else {
						// Check if we can get a salting instance
					$objSalt = \tx_saltedpasswords_salts_factory::getSaltingInstance(NULL);
					if (!is_object($objSalt)) {
							// Could not get a salting instance from saltedpasswords
						$message = $GLOBALS['TSFE']->sL('LLL:EXT:' . $extensionKey . '/pi/locallang.xml:internal_salted_passwords_no_instance');
						\t3lib_div::sysLog($message, $extensionKey, \t3lib_div::SYSLOG_SEVERITY_ERROR);
						$content .= sprintf($GLOBALS['TSFE']->sL('LLL:EXT:' . $extensionKey . '/pi/locallang.xml:internal_check_requirements_frontend'), $message);
					}
				}
			}

				// Check if we can get a backend from rsaauth
			if (\t3lib_extMgm::isLoaded('rsaauth')) {
					// rsaauth in TYPO3 4.5 misses autoload
				if (
					version_compare(TYPO3_version, '6.2.0', '<') &&
					!class_exists('tx_rsaauth_backendfactory')
				) {
					require_once(\t3lib_extMgm::extPath('rsaauth') . 'sv1/backends/class.tx_rsaauth_backendfactory.php');
					require_once(t3lib_extMgm::extPath('rsaauth') . 'sv1/storage/class.tx_rsaauth_storagefactory.php');
				}
				$backend = \tx_rsaauth_backendfactory::getBackend();
				$storage = \tx_rsaauth_storagefactory::getStorage();
				if (!is_object($backend) || !$backend->isAvailable() || !is_object($storage)) {
						// Required RSA auth backend not available
					$message = $GLOBALS['TSFE']->sL('LLL:EXT:' . $extensionKey . '/pi/locallang.xml:internal_rsaauth_backend_not_available');
					\t3lib_div::sysLog($message, $extensionKey, \t3lib_div::SYSLOG_SEVERITY_ERROR);
					$content .= sprintf($GLOBALS['TSFE']->sL('LLL:EXT:' . $extensionKey . '/pi/locallang.xml:internal_check_requirements_frontend'), $message);
				}
			}
		}

		return $content;
	}

	/* Checks whether the HTML templates contains any deprecated marker
	 *
	 * @return string Error message, if error found, empty string otherwise
	 */
	static public function checkDeprecatedMarkers (
		$cObj,
		array $conf,
		$extensionKey
	) {
		$content = '';
		$templateCode = $cObj->fileResource($conf['templateFile']);

		$messages =
			\tx_agency_marker::checkDeprecatedMarkers(
				$templateCode,
				$extensionKey,
				$conf['templateFile']
			);

		foreach ($messages as $message) {
			\t3lib_div::sysLog($message, $extensionKey, \t3lib_div::SYSLOG_SEVERITY_ERROR);
			$content .= sprintf($GLOBALS['TSFE']->sL('LLL:EXT:' . $extensionKey . '/pi/locallang.xml:internal_check_requirements_frontend'), $message);
		}

		return $content;
	}

}

