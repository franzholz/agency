<?php
defined('TYPO3') || die();

(static function() {
    \TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
        'Agency',
        'Register',
        [
            \JambageCom\Agency\Controller\UserController::class => 'show, new, preview, edit, update'
        ],
        // non-cacheable actions
        [
            \JambageCom\Agency\Controller\UserController::class => 'show, new, preview, edit, update'
        ]
    );

    // wizards
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPageTSConfig(
        'mod {
            wizards.newContentElement.wizardItems.plugins {
                elements {
                    register {
                        iconIdentifier = agency-plugin-register
                        title = LLL:EXT:agency/Resources/Private/Language/locallang_db.xlf:tx_agency_register.name
                        description = LLL:EXT:agency/Resources/Private/Language/locallang_db.xlf:tx_agency_register.description
                        tt_content_defValues {
                            CType = list
                            list_type = agency_register
                        }
                    }
                }
                show = *
            }
       }'
    );
})();
