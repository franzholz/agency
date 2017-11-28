<?php
/***************************************************************
*  Copyright notice
*
*  (c) 1999-2003 Kasper Skårhøj (kasperYYYY@typo3.com)
*  (c) 2004-2012 Stanislas Rolland <typo3(arobas)sjbr.ca>
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
 * deprecated file: only used for TYPO3 4.x
 *
 * Part of the agency (Agency Registration) extension.
 * A variant restricted to front end user self-registration and profile maintenance, with a number of enhancements (see the manual).
 *
 * @author	Kasper Skårhøj <kasperYYYY@typo3.com>
 * @author	Stanislas Rolland <typo3(arobas)sjbr.ca>
 * @author	Franz Holzinger <franz@ttproducts.de>
 * @maintainer	Franz Holzinger <franz@ttproducts.de>
 *
 *
 */
class tx_agency_pi_base extends tslib_pibase {

		// Plugin initialization variables
	public $prefixId = 'agency';
	public $scriptRelPath = 'pi/class.tx_agency_pi_base.php'; // Path to this script relative to the extension dir.
	public $extKey = AGENCY_EXT;		// Extension key.

	public function main ($content, $conf) {
		$this->pi_setPiVarDefaults();
		$this->conf = $conf;
			// Check installation requirements
		$content =
			$this->checkRequirements(
				$conf,
				$this->extKey
			);
			// Check presence of deprecated markers
		$content .=
			$this->checkDeprecatedMarkers(
				$this->cObj,
				$conf,
				$this->extKey
			);

			// If no error content, proceed
		if ($content == '') {
			$mainObj = t3lib_div::getUserObj('&tx_agency_control_main');
			$mainObj->cObj = $this->cObj;
			$content = $mainObj->main($content, $conf, $this, 'fe_users');
		}
		return $content;
	}

	/* Checks requirements for this plugin
	 *
	 * @return string Error message, if error found, empty string otherwise
	 */
	protected function checkRequirements ($conf, $extKey) {
		$content = '';

        if (
            !isset($conf['table.']) ||
            isset($conf['table.']['name'])
        ) {
            $message = sprintf($GLOBALS['TSFE']->sL('LLL:EXT:' . $extKey . '/pi/locallang.xml:internal_required_typoscript_missing'), $extension);
            t3lib_div::sysLog($message, $extKey, t3lib_div::SYSLOG_SEVERITY_ERROR);
            $content .= sprintf($GLOBALS['TSFE']->sL('LLL:EXT:' . $extKey . '/pi/locallang.xml:internal_check_requirements_frontend'), $message);
            return $content;
        }

		$requiredExtensions = array();
		$loginSecurityLevel = $GLOBALS['TYPO3_CONF_VARS']['FE']['loginSecurityLevel'];

			// Check if all required extensions are available
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$extKey]['constraints']['depends'])) {
			$requiredExtensions =
				array_diff(
					array_keys($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$extKey]['constraints']['depends']),
					array('php', 'typo3')
				);
		}

		if (
			$loginSecurityLevel == 'rsa' ||
			(
				tx_agency_controldata::enableAutoLoginOnConfirmation($conf, '')
			)
		) {
			$requiredExtensions[] = 'rsaauth';
		}

		foreach ($requiredExtensions as $extension) {
			if (!t3lib_extMgm::isLoaded($extension)) {
				$message = sprintf($GLOBALS['TSFE']->sL('LLL:EXT:' . $extKey . '/pi/locallang.xml:internal_required_extension_missing'), $extension);
				t3lib_div::sysLog($message, $extKey, t3lib_div::SYSLOG_SEVERITY_ERROR);
				$content .= sprintf($GLOBALS['TSFE']->sL('LLL:EXT:' . $extKey . '/pi/locallang.xml:internal_check_requirements_frontend'), $message);
			}
		}

			// Check if any conflicting extension is available
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$extKey]['constraints']['conflicts'])) {
			$conflictingExtensions =
				array_keys($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$extKey]['constraints']['conflicts']);
		}

		foreach ($conflictingExtensions as $extension) {
			if (t3lib_extMgm::isLoaded($extension)) {
				$message = sprintf($GLOBALS['TSFE']->sL('LLL:EXT:' . $extKey . '/pi/locallang.xml:internal_conflicting_extension_installed'), $extension);
				t3lib_div::sysLog($message, $extKey, t3lib_div::SYSLOG_SEVERITY_ERROR);
				$content .= sprintf($GLOBALS['TSFE']->sL('LLL:EXT:' . $extKey . '/pi/locallang.xml:internal_check_requirements_frontend'), $message);
			}
		}

			// Check if front end login security level is correctly set
		$supportedTransmissionSecurityLevels = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$extKey]['loginSecurityLevels'];

		if (
            (
                $loginSecurityLevel != '' ||
                version_compare(TYPO3_version, '6.2.0', '<')
            ) &&
            !in_array($loginSecurityLevel, $supportedTransmissionSecurityLevels)
        ) {
			$message = $GLOBALS['TSFE']->sL('LLL:EXT:' . $extKey . '/pi/locallang.xml:internal_login_security_level');
			t3lib_div::sysLog($message, $extKey, t3lib_div::SYSLOG_SEVERITY_ERROR);
			$content .= sprintf($GLOBALS['TSFE']->sL('LLL:EXT:' . $extKey . '/pi/locallang.xml:internal_check_requirements_frontend'), $message);
		} else {
				// Check if salted passwords are enabled in front end
			if (t3lib_extMgm::isLoaded('saltedpasswords')) {
				if (!tx_saltedpasswords_div::isUsageEnabled('FE')) {
					$message = $GLOBALS['TSFE']->sL('LLL:EXT:' . $extKey . '/pi/locallang.xml:internal_salted_passwords_disabled');
					t3lib_div::sysLog($message, $extKey, t3lib_div::SYSLOG_SEVERITY_ERROR);
					$content .= sprintf($GLOBALS['TSFE']->sL('LLL:EXT:' . $extKey . '/pi/locallang.xml:internal_check_requirements_frontend'), $message);
				} else {
						// Check if we can get a salting instance
					$objSalt = tx_saltedpasswords_salts_factory::getSaltingInstance(NULL);
					if (!is_object($objSalt)) {
							// Could not get a salting instance from saltedpasswords
						$message = $GLOBALS['TSFE']->sL('LLL:EXT:' . $extKey . '/pi/locallang.xml:internal_salted_passwords_no_instance');
						t3lib_div::sysLog($message, $extKey, t3lib_div::SYSLOG_SEVERITY_ERROR);
						$content .= sprintf($GLOBALS['TSFE']->sL('LLL:EXT:' . $extKey . '/pi/locallang.xml:internal_check_requirements_frontend'), $message);
					}
				}
			}

				// Check if we can get a backend from rsaauth
			if (t3lib_extMgm::isLoaded('rsaauth')) {
					// rsaauth in TYPO3 4.5 misses autoload
				if (
					version_compare(TYPO3_version, '6.2.0', '<') &&
					!class_exists('tx_rsaauth_backendfactory')
				) {
					require_once(t3lib_extMgm::extPath('rsaauth') . 'sv1/backends/class.tx_rsaauth_backendfactory.php');
					require_once(t3lib_extMgm::extPath('rsaauth') . 'sv1/storage/class.tx_rsaauth_storagefactory.php');
				}
				$backend = tx_rsaauth_backendfactory::getBackend();
				$storage = tx_rsaauth_storagefactory::getStorage();
				if (!is_object($backend) || !$backend->isAvailable() || !is_object($storage)) {
						// Required RSA auth backend not available
					$message = $GLOBALS['TSFE']->sL('LLL:EXT:' . $extKey . '/pi/locallang.xml:internal_rsaauth_backend_not_available');
					t3lib_div::sysLog($message, $extKey, t3lib_div::SYSLOG_SEVERITY_ERROR);
					$content .= sprintf($GLOBALS['TSFE']->sL('LLL:EXT:' . $extKey . '/pi/locallang.xml:internal_check_requirements_frontend'), $message);
				}
			}
		}

		return $content;
	}

	/* Checks whether the HTML templates contains any deprecated marker
	 *
	 * @return string Error message, if error found, empty string otherwise
	 */
	protected function checkDeprecatedMarkers (
		$cObj,
		array $conf,
		$extKey
	) {
		$content = '';
		$templateCode = $cObj->fileResource($conf['templateFile']);

		$messages =
			tx_agency_marker::checkDeprecatedMarkers(
				$templateCode,
				$extKey,
				$conf['templateFile']
			);

		foreach ($messages as $message) {
			t3lib_div::sysLog($message, $extKey, t3lib_div::SYSLOG_SEVERITY_ERROR);
			$content .= sprintf($GLOBALS['TSFE']->sL('LLL:EXT:' . $extKey . '/pi/locallang.xml:internal_check_requirements_frontend'), $message);
		}

		return $content;
	}
}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/' . AGENCY_EXT . '/pi/class.tx_agency_pi_base.php']) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/' . AGENCY_EXT . '/pi/class.tx_agency_pi_base.php']);
}
