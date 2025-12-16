<?php

declare(strict_types=1);

namespace JambageCom\Agency\Database;

/***************************************************************
*  Copyright notice
*
*  (c) 2018 Franz Holzinger (franz@ttproducts.de)
*  All rights reserved
*
*  This script is part of the Typo3 project. The Typo3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*  A copy is found in the textfile GPL.txt and important notices to the license
*  from the author is found in LICENSE.txt distributed with these scripts.
*
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/
/**
 * Part of the agency (Agency Registration) extension.
 *
 * setup configuration functions. former classes tx_agency_data
 *
 * @author  Franz Holzinger <franz@ttproducts.de>
 *
 * @package TYPO3
 * @subpackage agency
 *
 *
 */
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Resource\Security\FileNameValidator;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\File\BasicFileUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;

use JambageCom\Div2007\Captcha\CaptchaInterface;
use JambageCom\Div2007\Captcha\CaptchaManager;
use JambageCom\Div2007\Database\CoreQuery;
use JambageCom\Div2007\Database\QueryBuilderApi;
use JambageCom\Div2007\Utility\ArrayUtility;
use JambageCom\Div2007\Utility\CompatibilityUtility;
use JambageCom\Div2007\Utility\FrontendUtility;
use JambageCom\Div2007\Utility\HtmlUtility;
use JambageCom\Div2007\Utility\SystemUtility;
use JambageCom\Div2007\Utility\TableUtility;

use JambageCom\Agency\Api\ParameterApi;
use JambageCom\Agency\Configuration\ConfigurationStore;
use JambageCom\Agency\Constants\Field;
use JambageCom\Agency\Domain\Repository\FrontendUserRepository;
use JambageCom\Agency\Request\Parameters;
use JambageCom\Agency\Security\Authentication;
use JambageCom\Agency\Security\SecuredData;


class Data implements SingletonInterface
{
    public $lang;
    public $tca;
    public $freeCap; // object of type tx_srfreecap_pi2
    public $control;
    public $controlData;
    public $dataArray = [];
    public $origArray = [];
    protected $evalErrors = [];
    public $saved = false; // is set if data is saved
    public $theTable;
    public $addTableArray = [];
    public $fileFunc = ''; // Set to a basic_filefunc object for file uploads

    public $error;
    public $additionalUpdateFields = '';
    public $additionalOverrideFields = [];
    public $fieldList = ''; // List of fields from $TCA[table]['columns'] or fe_admin_fieldList (TYPO3 below 6.2)
    public $specialfieldlist = ''; // list of special fields like captcha
    public $adminFieldList;
    public $additionalIncludedFields = []; // list of additional front end fields which are active only in the preview mode: username, cnum
    public $recUid = 0;
    public $missing = []; // array of required missing fields
    public $inError = []; // array of fields with eval errors other than absence
    public $templateCode = '';
    /**
     * @var CoreQuery
     */
    protected $coreQuery;

    public function __construct(
        protected readonly ConnectionPool $connectionPool,
        protected readonly FrontendUserRepository $frontendUserRepository
    ) {
    }

    public function init(
        $coreQuery,
        $lang,
        $tca,
        $control,
        $theTable,
        $templateCode,
        Parameters $controlData,
        $staticInfoObj
    ): void {
        $this->coreQuery = $coreQuery;
        $this->lang = $lang;
        $this->tca = $tca;
        $this->control = $control;
        $this->controlData = $controlData;
        $this->fileFunc = GeneralUtility::makeInstance(BasicFileUtility::class);
        $parameterApi = GeneralUtility::makeInstance(ParameterApi::class);

        // Fetching the template file
        $this->setTemplateCode($templateCode);

        if (
            CaptchaManager::isLoaded(
                $controlData->getExtensionKey()
            )
        ) {
            $this->setSpecialFieldList(Field::CAPTCHA);
        }

        // Get POST parameters
        $fe = $parameterApi->getParameter('FE');

        if (
            isset($fe) &&
            is_array($fe) &&
            $controlData->isTokenValid()
        ) {
            $feDataArray = $fe[$theTable];
            $this->setDataArray($feDataArray);
        }
    }

    public function getFrontendUserRepository(): ?FrontendUserRepository
    {
        return $this->frontendUserRepository;
    }

    public function getCoreQuery()
    {
        return $this->coreQuery;
    }

    public function setError($error): void
    {
        $this->error = $error;
    }

    public function getError()
    {
        return $this->error;
    }

    public function setSaved($value): void
    {
        $this->saved = $value;
    }

    public function getSaved()
    {
        return $this->saved;
    }

    public function getTemplateCode()
    {
        return $this->templateCode;
    }

    /*
     * Sets the source code of the HTML template
     *
     * @param string $templateCode: the source code
     * @return void
     */
    public function setTemplateCode($templateCode): void
    {
        $this->templateCode = $templateCode;
    }

    public function getFieldList()
    {
        return $this->fieldList;
    }

    public function setFieldList($fieldList): void
    {
        $this->fieldList = $fieldList;
    }

    public function setSpecialFieldList($specialfieldlist): void
    {
        $this->specialfieldlist = $specialfieldlist;
    }

    public function getSpecialFieldList()
    {
        return $this->specialfieldlist;
    }

    public function getAdminFieldList()
    {
        return $this->adminFieldList;
    }

    public function setAdminFieldList($adminFieldList): void
    {
        $this->adminFieldList = $adminFieldList;
    }

    public function getAdditionalUpdateFields()
    {
        return $this->additionalUpdateFields;
    }

    public function setAdditionalUpdateFields($additionalUpdateFields): void
    {
        $this->additionalUpdateFields = $additionalUpdateFields;
    }

    public function getAdditionalOverrideFields()
    {
        return $this->additionalOverrideFields;
    }

    public function setAdditionalOverrideFields($fields): void
    {
        $this->additionalOverrideFields = $fields;
    }

    public function getAdditionalIncludedFields()
    {
        return $this->additionalIncludedFields;
    }

    public function setAdditionalIncludedFields($fields): void
    {
        $this->additionalIncludedFields = $fields;
    }

    public function setRecUid($uid): void
    {
        $this->recUid = intval($uid);
    }

    public function getRecUid()
    {
        return $this->recUid;
    }

    public function getAddTableArray()
    {
        return $this->addTableArray;
    }

    public function addTableArray($table): void
    {
        if (!in_array($table, $this->addTableArray)) {
            $this->addTableArray[] = $table;
        }
    }

    public function setDataArray(
        array $dataArray
    ): void {
        $this->dataArray = $dataArray;
        if (isset($this->dataArray['uid'])) {
            $this->dataArray['uid'] = intval($this->dataArray['uid']);
        }
    }

    public function getDataArray($k = 0)
    {
        if ($k) {
            $result = $this->dataArray[$k];
        } else {
            $result = $this->dataArray;
        }

        return $result;
    }

    public function resetDataArray(): void
    {
        $this->dataArray = [];
    }

    public function setOrigArray(array $origArray): void
    {
        $this->origArray = $origArray;
    }

    public function getOrigArray()
    {
        return $this->origArray;
    }

    public function bNewAvailable()
    {
        $dataArray = $this->getDataArray();
        $result = (!empty($dataArray['username']) || !empty($dataArray['email']));
        return $result;
    }

    /**
    * Overrides field values as specified by TS setup
    *
    * @return void  all overriding done directly on array $this->dataArray
    */
    public function overrideValues(
        array &$dataArray,
        $cmdKey,
        $conf
    ): void {
        $cObj = FrontendUtility::getContentObjectRenderer();
        $overrideFieldArray = [];

        // Addition of overriding values
        if (isset($conf['overrideValues.'])) {
            foreach ($conf['overrideValues.'] as $theField => $theValue) {
                $control = false;

                if (
                    $theField == 'if.'
                ) {
                    $control = true;
                    $ifCondition = $theValue;

                    if (is_array($ifCondition)) {
                        foreach ($ifCondition as $k => $ifLine) {
                            if (is_array($ifLine)) {
                                $conditionMatch = false;
                                if (!isset($ifLine['where'])) {
                                    continue;
                                }
                                $whereParts = GeneralUtility::trimExplode('=', $ifLine['where']);

                                if (
                                    empty($whereParts) ||
                                    count($whereParts) != 2
                                ) {
                                    continue;
                                }
                                $whereField = $whereParts[0];
                                $whereValue = $whereParts[1];

                                if (
                                    $dataArray[$whereField] == $whereValue
                                ) {
                                    $conditionMatch = true;
                                }

                                if ($conditionMatch) {
                                    foreach ($ifLine as $ifField => $ifValue) {
                                        if (
                                            is_array($ifValue) &&
                                            strpos($ifField, '.') == strlen($ifField) - 1
                                        ) {
                                            $command = substr($ifField, 0, strlen($ifField) - 1);
                                            switch ($command) {
                                                case 'set':
                                                    $dataArray = array_merge($dataArray, $ifValue);
                                                    $overrideFieldArray = array_merge($overrideFieldArray, array_keys($ifValue));
                                                    break;
                                                default:
                                                    // nothing
                                                    break;
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                } elseif (
                    $theField == 'usergroup' &&
                    $this->controlData->getTable() == 'fe_users' &&
                    !empty($conf['allowUserGroupSelection'])
                ) {
                    $overrideArray = GeneralUtility::trimExplode(',', (string) $theValue, true);
                    if (is_array($dataArray[$theField])) {
                        $dataValue = array_merge($dataArray[$theField], $overrideArray);
                    } else {
                        $dataValue = $overrideArray;
                    }
                    $dataValue = array_unique($dataValue);
                } else {
                    $stdWrap = ($conf['overrideValues.'][$theField . '.'] ?? '');
                    if ($stdWrap) {
                        $dataValue = $cObj->stdWrap($theValue, $stdWrap);
                    } else {
                        $dataValue = $theValue;
                    }
                }

                if (!$control) {
                    $dataArray[$theField] = $dataValue;
                }
            }
        }

        if (!empty($overrideFieldArray)) {
            $this->setAdditionalOverrideFields($overrideFieldArray);
        }
    }   // overrideValues

    /**
    * fetches default field values as specified by TS setup
    *
    * @param array  Array with key/values being marker-strings/substitution values.
    * @return array the data row with key/value pairs
    */
    public function readDefaultValues($cmdKey)
    {
        $confObj = GeneralUtility::makeInstance(ConfigurationStore::class);
        $conf = $confObj->getConf();
        $dataArray = [];

        // Addition of default values
        if (is_array($conf[$cmdKey . '.']['defaultValues.'])) {
            foreach($conf[$cmdKey . '.']['defaultValues.'] as $theField => $theValue) {
                $dataArray[$theField] = $theValue;
            }
        }
        return $dataArray;
    }

    /**
    * Gets the error message to be displayed
    *
    * @param string  $theField: the name of the field being validated
    * @param string  $theRule: the name of the validation rule being evaluated
    * @param string  $label: a default error message provided by the invoking function
    * @param integer $orderNo: ordered number of the rule for the field (>0 if used)
    * @param string  $param: parameter for the error message
    * @param boolean $bInternal: if the bug is caused by an internal problem
    * @return string  the error message to be displayed
    */
    public function getFailureText(
        $dataArray,
        $theField,
        $theRule,
        $label,
        $orderNo = '',
        $param = '',
        $bInternal = false
    ) {
        $confObj = GeneralUtility::makeInstance(ConfigurationStore::class);
        $conf = $confObj->getConf();

        if (
            (string) $orderNo != '' &&
            $theRule &&
            isset($conf['evalErrors.'][$theField . '.'][$theRule . '.'])
        ) {
            $count = 0;

            foreach ($conf['evalErrors.'][$theField . '.'][$theRule . '.'] as $k => $v) {
                $bKIsInt = MathUtility::canBeInterpretedAsInteger($k);

                if ($bInternal) {
                    if ($k == 'internal') {
                        $failureLabel = $v;
                        break;
                    }
                } elseif ($bKIsInt) {
                    $count++;

                    if ($count == $orderNo) {
                        $failureLabel = $v;
                        break;
                    }
                }
            }
        }

        if (!isset($failureLabel)) {
            if (
                $theRule &&
                isset($conf['evalErrors.'][$theField . '.'][$theRule])
            ) {
                $failureLabel = $conf['evalErrors.'][$theField . '.'][$theRule];
            } else {
                $failureLabel = '';
                $internalPostfix = ($bInternal ? '_internal' : '');
                if ($theRule) {
                    $labelname = 'evalErrors_' . $theRule . '_' . $theField . $internalPostfix;
                    $failureLabel = $this->lang->getLabel($labelname);
                    $failureLabel = $failureLabel ?: $this->lang->getLabel('evalErrors_' . $theRule . $internalPostfix);
                }

                if (!$failureLabel) { // this remains only for compatibility reasons
                    $labelname = $label;
                    $failureLabel = $this->lang->getLabel($labelname);
                }
            }
        }

        if ($param != '' && $failureLabel != '') {
            $failureLabel = sprintf($failureLabel, $param);
        }

        return $failureLabel;
    }   // getFailureText

    /**
    * Applies validation rules specified in TS setup
    *
    * @param array  Array with key/values being marker-strings/substitution values.
    * @return void  on return, the ControlData failure will contain the list of fields which were not ok
    */
    public function evalValues(
        ConfigurationStore $confObj,
        $staticInfoObj,
        $theTable,
        array &$dataArray,
        array &$origArray,
        array &$markContentArray,
        $cmdKey,
        array $requiredArray,
        array $checkFieldArray,
        $captcha
    ) {
        $conf = $confObj->getConf();
        $failureMsg = [];
        $displayFieldArray = GeneralUtility::trimExplode(',', $conf[$cmdKey . '.']['fields'], true);
        if (
            $captcha instanceof CaptchaInterface
        ) {
            $displayFieldArray[] = Field::CAPTCHA;
        }
        $pathSite = Environment::getPublicPath() . '/';

        // Check required, set failure if not ok.
        $failureArray = [];

        foreach ($requiredArray as $k => $theField) {

            $bIsMissing = false;

            if (isset($dataArray[$theField])) {
                if (
                    empty($dataArray[$theField])
                ) {
                    $bIsMissing = true;
                }
            } else {
                $bIsMissing = true;
            }
            if ($bIsMissing) {
                $failureArray[] = $theField;
                $this->missing[$theField] = true;
            }
        }
        $pid = intval($dataArray['pid'] ?? '');

        // Evaluate: This evaluates for more advanced things than "required" does. But it returns the same error code, so you must let the required-message tell, if further evaluation has failed!
        $bRecordExists = false;
        $recordTestPid = 0;

        if (is_array($conf[$cmdKey . '.']['evalValues.'])) {
            $cmd = $this->controlData->getCmd();
            if ($cmd == 'edit' || $cmdKey == 'edit') {
                if ($pid) {
                    // This may be tricked if the input has the pid-field set but the edit-field list does NOT allow the pid to be edited. Then the pid may be false.
                    $recordTestPid = $pid;
                } elseif (!empty($dataArray['uid'])) {
                    $tempRecArr = $GLOBALS['TSFE']->sys_page->getRawRecord($this->controlData->getTable(), $dataArray['uid']);
                    $recordTestPid = intval($tempRecArr['pid']);
                }
                $bRecordExists = ($recordTestPid != 0);
            } else {
                $thePid = $this->controlData->getPid();
                $recordTestPid = ($thePid ?: MathUtility::convertToPositiveInteger($pid));
            }
            $countArray = [];
            $countArray['hook'] = [];
            $countArray['preg'] = [];

            foreach ($conf[$cmdKey . '.']['evalValues.'] as $theField => $theValue) {
                if (
                    count($checkFieldArray) &&
                    !in_array($theField, $checkFieldArray)
                ) {
                    continue;
                }
                $this->evalErrors[$theField] = [];
                $failureMsg[$theField] = [];
                $listOfCommands = GeneralUtility::trimExplode(',', (string) $theValue, true);
                // Unset the incoming value is empty and unsetEmpty is specified
                if (array_search('unsetEmpty', $listOfCommands) !== false) {
                    if (
                        isset($dataArray[$theField]) &&
                        empty($dataArray[$theField]) &&
                        trim($dataArray[$theField]) !== '0'
                    ) {
                        unset($dataArray[$theField]);
                    }

                    if (
                        isset($dataArray[$theField . '_again']) &&
                        empty($dataArray[$theField . '_again']) &&
                        trim($dataArray[$theField . '_again']) !== '0'
                    ) {
                        unset($dataArray[$theField . '_again']);
                    }
                }

                if (
                    isset($dataArray[$theField]) ||
                    isset($dataArray[$theField . '_again']) ||
                    !count($origArray) ||
                    !isset($origArray[$theField])
                ) {
                    foreach ($listOfCommands as $k => $cmd) {
                        $cmdParts = preg_split('/\[|\]/', $cmd); // Point is to enable parameters after each command enclosed in brackets [..]. These will be in position 1 in the array.
                        $theCmd = trim($cmdParts[0]);
                        switch($theCmd) {
                            case 'uniqueGlobal':
                            case 'uniqueDeletedGlobal':
                            case 'uniqueLocal':
                            case 'uniqueDeletedLocal':

                                $showDeleted = true;
                                $showPid = 0;
                                if ($theCmd == 'uniqueLocal' || $theCmd == 'uniqueGlobal') {
                                    $showDeleted = false;
                                }
                                if ($theCmd == 'uniqueLocal' || $theCmd == 'uniqueDeletedLocal') {
                                    $showPid = $recordTestPid;
                                }

                                $DBrow =
                                    $this->frontendUserRepository->getSpecificRecord(
                                        $showPid,
                                        $theField,
                                        (string) $dataArray[$theField],
                                        $showDeleted
                                    );

                                if (
                                    !is_array($dataArray[$theField]) &&
                                    trim($dataArray[$theField]) != '' &&
                                    isset($DBrow) &&
                                    is_array($DBrow)
                                ) {
                                    if (
                                        !$bRecordExists ||
                                        $DBrow['uid'] != $dataArray['uid']
                                    ) {
                                        // Only issue an error if the record is not existing (if new...) and if the record with the false value selected was not our self.
                                        $failureArray[] = $theField;
                                        $this->inError[$theField] = true;
                                        $this->evalErrors[$theField][] = $theCmd;
                                        $failureMsg[$theField][] =
                                            $this->getFailureText(
                                                $dataArray,
                                                $theField,
                                                'uniqueLocal',
                                                'evalErrors_existed_already'
                                            );
                                    }
                                }
                                break;
                            case 'twice':
                                $fieldValue = strval($dataArray[$theField] ?? '');
                                $fieldAgainValue = strval($dataArray[$theField . '_again'] ?? '');
                                if (strcmp($fieldValue, $fieldAgainValue)) {
                                    $failureArray[] = $theField;
                                    $this->inError[$theField] = true;
                                    $this->evalErrors[$theField][] = $theCmd;
                                    $failureMsg[$theField][] =
                                        $this->getFailureText(
                                            $dataArray,
                                            $theField,
                                            $theCmd,
                                            'evalErrors_same_twice'
                                        );
                                }
                                break;
                            case 'email':
                                if (
                                    !is_array($dataArray[$theField]) &&
                                    trim($dataArray[$theField]) &&
                                    !GeneralUtility::validEmail(trim($dataArray[$theField]))
                                ) {
                                    $failureArray[] = $theField;
                                    $this->inError[$theField] = true;
                                    $this->evalErrors[$theField][] = $theCmd;
                                    $failureMsg[$theField][] =
                                        $this->getFailureText(
                                            $dataArray,
                                            $theField,
                                            $theCmd,
                                            'evalErrors_valid_email'
                                        );
                                }
                                break;
                            case 'required':
                                if (
                                    empty($dataArray[$theField]) &&
                                    trim($dataArray[$theField]) !== '0'
                                ) {
                                    $failureArray[] = $theField;
                                    $this->inError[$theField] = true;
                                    $this->evalErrors[$theField][] = $theCmd;
                                    $failureMsg[$theField][] =
                                        $this->getFailureText(
                                            $dataArray,
                                            $theField,
                                            $theCmd,
                                            'evalErrors_required'
                                        );
                                }
                                break;
                            case 'atLeast':
                                $chars = intval($cmdParts[1]);

                                if (
                                    isset($dataArray[$theField]) &&
                                    !is_array($dataArray[$theField]) &&
                                    strlen($dataArray[$theField]) < $chars
                                ) {
                                    $failureArray[] = $theField;
                                    $this->inError[$theField] = true;
                                    $this->evalErrors[$theField][] = $theCmd;
                                    $failureMsg[$theField][] =
                                        sprintf(
                                            $this->getFailureText(
                                                $dataArray,
                                                $theField,
                                                $theCmd,
                                                'evalErrors_atleast_characters'
                                            ),
                                            $chars
                                        );
                                }
                                break;
                            case 'atMost':
                                $chars = intval($cmdParts[1]);
                                if (
                                    isset($dataArray[$theField]) &&
                                    !is_array($dataArray[$theField]) && strlen($dataArray[$theField]) > $chars
                                ) {
                                    $failureArray[] = $theField;
                                    $this->inError[$theField] = true;
                                    $this->evalErrors[$theField][] = $theCmd;
                                    $failureMsg[$theField][] =
                                        sprintf(
                                            $this->getFailureText(
                                                $dataArray,
                                                $theField,
                                                $theCmd,
                                                'evalErrors_atmost_characters'
                                            ),
                                            $chars
                                        );
                                }
                                break;
                            case 'inBranch':
                                $pars = explode(';', $cmdParts[1]);
                                if (intval($pars[0])) {
                                    $cObj = FrontendUtility::getContentObjectRenderer();
                                    $pid_list = $cObj->getTreeList(
                                        intval($pars[0]),
                                        intval($pars[1]) ?: 999,
                                        intval($pars[2])
                                    );

                                    if (
                                        !$pid_list ||
                                        !GeneralUtility::inList($pid_list, $dataArray[$theField])
                                    ) {
                                        $failureArray[] = $theField;
                                        $this->inError[$theField] = true;
                                        $this->evalErrors[$theField][] = $theCmd;
                                        $failureMsg[$theField][] =
                                            sprintf(
                                                $this->getFailureText(
                                                    $dataArray,
                                                    $theField,
                                                    $theCmd,
                                                    'evalErrors_unvalid_list'
                                                ),
                                                $pid_list
                                            );
                                    }
                                }
                                break;
                            case 'upload':
                                if (
                                    isset($dataArray[$theField]) &&
                                    isset($GLOBALS['TCA'][$theTable]['columns'][$theField]['config']) &&
                                    is_array($GLOBALS['TCA'][$theTable]['columns'][$theField]['config'])
                                ) {
                                    $colSettings = $GLOBALS['TCA'][$theTable]['columns'][$theField];
                                    $colConfig = $colSettings['config'];
                                    if (
                                        $colConfig['type'] == 'group' &&
                                        $colConfig['internal_type'] == 'file'
                                    ) {
                                        $uploadPath = $colConfig['uploadfolder'];
                                        $allowedExtArray = GeneralUtility::trimExplode(',', $colConfig['allowed'], true);
                                        $maxSize = $colConfig['max_size'];
                                        $fileNameArray = $dataArray[$theField];
                                        $newFileNameArray = [];

                                        if (
                                            is_array($fileNameArray) &&
                                            $fileNameArray[0] != ''
                                        ) {
                                            foreach ($fileNameArray as $k => $filename) {
                                                if (is_array($filename)) {
                                                    $filename = $filename['name'];
                                                }
                                                $bAllowedFilename = $this->checkFilename($filename);
                                                $fI = pathinfo($filename);
                                                $fileExtension = strtolower($fI['extension']);
                                                $fullfilename = $pathSite . $uploadPath . '/' . $filename;
                                                if (
                                                    $bAllowedFilename &&
                                                    (!count($allowedExtArray) || in_array($fileExtension, $allowedExtArray))
                                                ) {
                                                    if (@is_file($fullfilename)) {
                                                        if (!$maxSize || (filesize($pathSite . $uploadPath.'/'.$filename) < ($maxSize * 1024))) {
                                                            $newFileNameArray[] = $filename;
                                                        } else {
                                                            $this->evalErrors[$theField][] = $theCmd;
                                                            $failureMsg[$theField][] =
                                                                sprintf(
                                                                    $this->getFailureText(
                                                                        $dataArray,
                                                                        $theField,
                                                                        'max_size',
                                                                        'evalErrors_size_too_large'
                                                                    ),
                                                                    $maxSize
                                                                );
                                                            $failureArray[] = $theField;
                                                            $this->inError[$theField] = true;
                                                            if (@is_file($pathSite . $uploadPath . '/' . $filename)) {
                                                                @unlink($pathSite . $uploadPath . '/' . $filename);
                                                            }
                                                        }
                                                    } else {
                                                        if (
                                                            isset($_FILES) &&
                                                            is_array($_FILES) &&
                                                            isset($_FILES['FE']) &&
                                                            is_array($_FILES['FE']) &&
                                                            isset($_FILES['FE']['tmp_name']) &&
                                                            is_array($_FILES['FE']['tmp_name']) &&
                                                            isset($_FILES['FE']['tmp_name'][$theTable]) &&
                                                            is_array($_FILES['FE']['tmp_name'][$theTable]) &&
                                                            isset($_FILES['FE']['tmp_name'][$theTable][$theField]) &&
                                                            is_array($_FILES['FE']['tmp_name'][$theTable][$theField]) &&
                                                            isset($_FILES['FE']['tmp_name'][$theTable][$theField][$k])
                                                        ) {
                                                            $bWritePermissionError = true;
                                                        } else {
                                                            $bWritePermissionError = false;
                                                        }
                                                        $this->evalErrors[$theField][] = $theCmd;
                                                        $failureMsg[$theField][] =
                                                            sprintf(
                                                                $this->getFailureText(
                                                                    $dataArray,
                                                                    $theField,
                                                                    'isfile',
                                                                    ($bWritePermissionError ? 'evalErrors_write_permission' : 'evalErrors_file_upload')
                                                                ),
                                                                $filename
                                                            );
                                                        $failureArray[] = $theField;
                                                    }
                                                } else {
                                                    $this->evalErrors[$theField][] = $theCmd;
                                                    $failureMsg[$theField][] =
                                                        sprintf(
                                                            $this->getFailureText(
                                                                $dataArray,
                                                                $theField,
                                                                'allowed',
                                                                'evalErrors_file_extension'
                                                            ),
                                                            $fileExtension
                                                        );
                                                    $failureArray[] = $theField;
                                                    $this->inError[$theField] = true;
                                                    if ($bAllowedFilename && @is_file($fullfilename)) {
                                                        @unlink($fullfilename);
                                                    }
                                                }
                                            }
                                            $dataValue = $newFileNameArray;
                                            $dataArray[$theField] = $dataValue;
                                        }
                                    }
                                }
                                break;
                            case 'wwwURL':
                                if ($dataArray[$theField]) {
                                    $urlParts = parse_url($dataArray[$theField]);
                                    if (
                                        $urlParts === false ||
                                        !GeneralUtility::isValidUrl($dataArray[$theField]) ||
                                        ($urlParts['scheme'] != 'http' && $urlParts['scheme'] != 'https') ||
                                        $urlParts['user'] ||
                                        $urlParts['pass']
                                    ) {
                                        $failureArray[] = $theField;
                                        $this->inError[$theField] = true;
                                        $this->evalErrors[$theField][] = $theCmd;
                                        $failureMsg[$theField][] =
                                            $this->getFailureText(
                                                $dataArray,
                                                $theField,
                                                $theCmd,
                                                'evalErrors_unvalid_url'
                                            );
                                    }
                                }
                                break;
                            case 'date':
                                if (
                                    !is_array($dataArray[$theField]) &&
                                    $dataArray[$theField] &&
                                    !$this->evalDate(
                                        $dataArray[$theField],
                                        $conf['dateFormat']
                                    )
                                ) {
                                    $failureArray[] = $theField;
                                    $this->inError[$theField] = true;
                                    $this->evalErrors[$theField][] = $theCmd;
                                    $failureMsg[$theField][] =
                                        $this->getFailureText(
                                            $dataArray,
                                            $theField,
                                            $theCmd,
                                            'evalErrors_unvalid_date'
                                        );
                                }
                                break;
                            case 'preg':
                                if (
                                    !is_array($dataArray[$theField]) &&
                                    !(
                                        empty($dataArray[$theField]) &&
                                        trim($dataArray[$theField]) !== '0'
                                    )
                                ) {
                                    if (isset($countArray['preg'][$theCmd])) {
                                        $countArray['preg'][$theCmd]++;
                                    } else {
                                        $countArray['preg'][$theCmd] = 1;
                                    }
                                    $pattern = str_replace('preg[', '', $cmd);
                                    $pattern = substr($pattern, 0, strlen($pattern) - 1);
                                    $matches = [];
                                    $test = preg_match($pattern, $dataArray[$theField], $matches);

                                    if ($test === false || $test == 0) {
                                        $failureArray[] = $theField;
                                        $this->inError[$theField] = true;
                                        $this->evalErrors[$theField][] = $theCmd;
                                        $failureMsg[$theField][] =
                                            $this->getFailureText(
                                                $dataArray,
                                                $theField,
                                                $theCmd,
                                                'evalErrors_' . $theCmd,
                                                $countArray['preg'][$theCmd],
                                                $cmd,
                                                ($test === false)
                                            );
                                    }
                                }
                                break;
                            case 'hook':
                            default:
                                if (isset($countArray['hook'][$theCmd])) {
                                    $countArray['hook'][$theCmd]++;
                                } else {
                                    $countArray['hook'][$theCmd] = 1;
                                }
                                $extKey = $this->controlData->getExtensionKey();
                                $hookClassArray = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$extKey]['model'];
                                if (is_array($hookClassArray)) {
                                    foreach ($hookClassArray as $classRef) {
                                        $hookObj = GeneralUtility::makeInstance($classRef);

                                        if (
                                            is_object($hookObj) &&
                                            method_exists($hookObj, 'evalValues')
                                        ) {
                                            if (
                                                method_exists($hookObj, 'needsInit') &&
                                                method_exists($hookObj, 'init') &&
                                                $hookObj->needsInit()
                                            ) {
                                                $hookObj->init($this);
                                            }

                                            $test = false; // set it to true if you test the following hook
                                            $bInternal = false;
                                            $errorField = $hookObj->evalValues(
                                                $confObj,
                                                $staticInfoObj,
                                                $theTable,
                                                $dataArray,
                                                $origArray,
                                                $markContentArray,
                                                $cmdKey,
                                                $requiredArray,
                                                $checkFieldArray,
                                                $theField,
                                                $cmdParts,
                                                $bInternal,
                                                $test, // must be set to false if it is not a test
                                                $this
                                            );

                                            if ($errorField != '') {
                                                $failureArray[] = $errorField;
                                                $this->evalErrors[$theField][] = $theCmd;

                                                if (!$test) {
                                                    $this->inError[$theField] = true;
                                                    $failureText =
                                                        $this->getFailureText(
                                                            $dataArray,
                                                            $theField,
                                                            $theCmd,
                                                            'evalErrors_' . $theCmd,
                                                            $countArray['hook'][$theCmd],
                                                            $cmd,
                                                            $bInternal
                                                        );

                                                    if (method_exists($hookObj, 'getFailureText')) {
                                                        $hookFailureText =
                                                            $hookObj->getFailureText(
                                                                $failureText,
                                                                $dataArray,
                                                                $theField,
                                                                $theCmd,
                                                                'evalErrors_' . $theCmd,
                                                                $countArray['hook'][$theCmd],
                                                                $cmd,
                                                                $bInternal
                                                            );
                                                        if ($hookFailureText != '') {
                                                            $failureText = $hookFailureText;
                                                        }
                                                    }
                                                    $failureMsg[$theField][] = $failureText;
                                                }
                                                break;
                                            }
                                        } else {
                                            debug($classRef, 'error in the class name for the hook "model"'); // keep this
                                        }
                                    }
                                }

                                if (
                                    $theField == Field::CAPTCHA &&
                                    $captcha instanceof CaptchaInterface
                                ) {
                                    $errorField = '';

                                    if (
                                        $dataArray[$theField] == '' ||
                                        !$captcha->evalValues(
                                            $dataArray[$theField],
                                            $cmdParts[0]
                                        )
                                    ) {
                                        $errorField = $theField;
                                    }

                                    if ($errorField != '') {
                                        $failureArray[] = $errorField;
                                        $this->evalErrors[$theField][] = $theCmd;

                                        if (!$test) {
                                            $this->inError[$theField] = true;
                                            $failureText =
                                                $this->getFailureText(
                                                    $dataArray,
                                                    $theField,
                                                    $theCmd,
                                                    'evalErrors_' . $theCmd,
                                                    $countArray['hook'][$theCmd],
                                                    $cmd,
                                                    $bInternal
                                                );
                                            $failureMsg[$theField][] = $failureText;
                                        }
                                    }
                                }
                                break;
                        }
                    }
                }

                if (
                    in_array($theField, $displayFieldArray) ||
                    in_array($theField, $failureArray)
                ) {
                    $errorMsg = '';
                    if (
                        (
                            is_string($failureMsg[$theField]) &&
                            !empty($failureMsg[$theField])
                        ) ||
                        (
                            is_array($failureMsg[$theField]) &&
                            !empty($failureMsg[$theField][0])
                        )
                    ) {
                        $xhtmlFix = HtmlUtility::determineXhtmlFix();
                        $markerOut = $markContentArray['###EVAL_ERROR_saved###'] ?? '';
                        $markerOut .= '<br' . $xhtmlFix . '>';
                        if (is_string($failureMsg[$theField])) {
                            $errorMsg = $failureMsg[$theField];
                        } else {
                            $errorMsg = implode('<br' . $xhtmlFix . '>', $failureMsg[$theField]);
                        }
                        $markContentArray['###EVAL_ERROR_saved###'] = $markerOut . $errorMsg;
                    }
                    $markContentArray['###EVAL_ERROR_FIELD_' . $theField . '###'] = ($errorMsg != '' ? $errorMsg : '<!--no error-->');
                }

                if (!count($this->evalErrors[$theField])) {
                    unset($this->evalErrors[$theField]);
                }
            }
        }

        if (empty($markContentArray['###EVAL_ERROR_saved###'])) {
            $markContentArray['###EVAL_ERROR_saved###'] = '';
        }

        if (!empty($this->missing['zone']) && is_object($staticInfoObj)) {
            // empty zone if there is not zone for the provided country
            $zoneArray = $staticInfoObj->initCountrySubdivisions($dataArray['static_info_country']);

            if (
                !isset($zoneArray) ||
                is_array($zoneArray) && !count($zoneArray)
            ) {
                unset($this->missing['zone']);
                $k = array_search('zone', $failureArray);
                unset($failureArray[$k]);
            }
        }
        if (!empty($this->missing)) {
            foreach ($this->missing as $theField => $value) {
                $errorMsg = $this->getFailureText(
                    $dataArray,
                    $theField,
                    'required',
                    'evalErrors_required'
                );
                $this->evalErrors[$theField][] = 'required';
                $this->inError[$theField] = true;
                $markContentArray['###EVAL_ERROR_FIELD_' . $theField . '###'] = $errorMsg;
            }
        }

        $failureArray = array_unique($failureArray);
        $failure = implode(',', $failureArray);
        $this->controlData->setFailure($failure);
        return $this->evalErrors;
    } // evalValues

    /**
    * Transforms fields into certain things...
    *
    * @return boolean  all parsing done directly on input and output array $dataArray
    */
    public function parseValues(
        $theTable,
        array &$dataArray,
        array $origArray,
        $cmdKey
    ) {
        $result = true;
        $confObj = GeneralUtility::makeInstance(ConfigurationStore::class);
        $conf = $confObj->getConf();

        if (is_array($conf['parseValues.'])) {

            foreach($conf['parseValues.'] as $theField => $theValue) {
                $listOfCommands = GeneralUtility::trimExplode(',', $theValue, true);
                if (in_array('setEmptyIfAbsent', $listOfCommands)) {
                    $this->setEmptyIfAbsent($theTable, $theField, $dataArray);
                }
                $internalType = $GLOBALS['TCA'][$theTable]['columns'][$theField]['config']['internal_type'] ?? '';

                if (
                    isset($dataArray[$theField]) ||
                    isset($origArray[$theField]) ||
                    $internalType == 'file'
                ) {
                    foreach($listOfCommands as $cmd) {
                        $cmdParts = preg_split('/\[|\]/', $cmd); // Point is to enable parameters after each command enclosed in brackets [..]. These will be in position 1 in the array.
                        $theCmd = trim($cmdParts['0']);
                        $parameter = trim($cmdParts['1'] ?? '');
                        $bValueAssigned = true;
                        if (
                            $theField == 'password' &&
                            !isset($dataArray[$theField])
                        ) {
                            $bValueAssigned = false;
                        }
                        $dataValue = ($dataArray[$theField] ?? $origArray[$theField]);

                        switch($theCmd) {
                            case 'int':
                                $dataValue = intval($dataValue);
                                break;
                            case 'lower':
                            case 'upper':
                                $cObj = FrontendUtility::getContentObjectRenderer();
                                $dataValue = $cObj->caseshift($dataValue, $theCmd);
                                break;
                            case 'nospace':
                                $dataValue = str_replace(' ', '', $dataValue);
                                break;
                            case 'alpha':
                                $dataValue = preg_replace('/[^a-zA-Z' . preg_quote($parameter) . ']/', '', $dataValue);
                                break;
                            case 'num':
                                $dataValue = preg_replace('/[^0-9]/', '', $dataValue);
                                break;
                            case 'alphanum':
                                $dataValue = preg_replace('/[^a-zA-Z0-9' . preg_quote($parameter) . ']/', '', $dataValue);
                                break;
                            case 'alphanum_x':
                                $dataValue = preg_replace('/[^a-zA-Z0-9_\\-' . preg_quote($parameter) . ']/', '', $dataValue);
                                break;
                            case 'trim':
                                $dataValue = trim($dataValue);
                                break;
                            case 'random':
                                $dataValue = substr(md5(uniqid(microtime(), 1)), 0, intval($cmdParts[1]));
                                break;
                            case 'files':
                                $fieldDataArray = [];
                                if (!empty($dataValue)) {
                                    if (is_array($dataValue)) {
                                        $fieldDataArray = $dataValue;
                                    } elseif (is_string($dataValue) && $dataValue) {
                                        $fieldDataArray = GeneralUtility::trimExplode(',', (string) $dataValue, true);
                                    }
                                }
                                $dataValue =
                                    $this->processFiles(
                                        $theTable,
                                        $theField,
                                        $fieldDataArray,
                                        $cmdKey,
                                        $fileDeleted
                                    );

                                if ($fileDeleted) {
                                    $result = false;
                                }
                                break;
                            case 'multiple':
                                $fieldDataArray = [];
                                if (
                                    !empty($dataArray[$theField]) ||
                                    (
                                        isset($dataArray[$theField]) &&
                                        $dataArray[$theField] == '0' // A zero value is different from an empty value. It must be kept for the case when only the first element of a checkbox with the value 0 has been selected.
                                    )
                                ) {
                                    if (is_array($dataArray[$theField])) {
                                        $fieldDataArray = $dataArray[$theField];
                                    } elseif (
                                        is_string($dataArray[$theField])
                                    ) {
                                        $fieldDataArray =
                                            GeneralUtility::trimExplode(
                                                ',',
                                                $dataArray[$theField],
                                                true
                                            );
                                    }
                                }
                                $dataValue = $fieldDataArray;
                                break;
                            case 'checkArray':
                                if (is_array($dataValue)) {
                                    $newDataValue = 0;
                                    foreach($dataValue as $kk => $vv) {
                                        $kk = (
                                            class_exists('t3lib_utility_Math') ?
                                                t3lib_utility_Math::forceIntegerInRange($kk, 0) :
                                                GeneralUtility::intInRange($kk, 0)
                                        );

                                        if ($kk <= 30) {
                                            if ($vv) {
                                                $newDataValue |= pow(2, $kk);
                                            }
                                        }
                                    }
                                    $dataValue = $newDataValue;
                                }
                                break;
                            case 'uniqueHashInt':
                                $otherFields = GeneralUtility::trimExplode(';', $cmdParts[1], true);
                                $hashArray = [];
                                foreach($otherFields as $fN) {
                                    $vv = $dataArray[$fN];
                                    $vv = preg_replace('/\s+/', '', $vv);
                                    $vv = preg_replace('/[^a-zA-Z0-9]/', '', $vv);
                                    $vv = strtolower($vv);
                                    $hashArray[] = $vv;
                                }
                                $dataValue = hexdec(substr(md5(serialize($hashArray)), 0, 8));
                                break;
                            case 'wwwURL':
                                if ($dataValue) {
                                    $urlParts = parse_url($dataValue);
                                    if ($urlParts !== false) {
                                        if (!$urlParts['scheme']) {
                                            $urlParts['scheme'] = 'http';
                                            $dataValue = $urlParts['scheme'] . '://' . $dataValue;
                                        }
                                        if (GeneralUtility::isValidUrl($dataValue)) {
                                            $dataValue = $urlParts['scheme'] . '://' .
                                                $urlParts['host'] .
                                                $urlParts['path'] .
                                                ($urlParts['query'] ? '?' . $urlParts['query'] : '') .
                                                ($urlParts['fragment'] ? '#' . $urlParts['fragment'] : '');
                                        }
                                    }
                                }
                                break;
                            case 'date':
                                if (
                                    $dataValue &&
                                    $this->evalDate(
                                        $dataValue,
                                        $conf['dateFormat']
                                    )
                                ) {
                                    $dateArray = $this->fetchDate($dataValue, $conf['dateFormat']);
                                    $dataValue = $dateArray['y'] . '-' . $dateArray['m'] . '-'.$dateArray['d'];
                                    $translateArray = [
                                        'd' => ($dateArray['d'] < 10 ? '0' . $dateArray['d'] : $dateArray['d']),
                                        'j' => $dateArray['d'],
                                        'm' => ($dateArray['m'] < 10 ? '0' . $dateArray['m'] : $dateArray['m']),
                                        'n' => $dateArray['m'],
                                        'y' => $dateArray['y'],
                                        'Y' => $dateArray['y']
                                    ];
                                    $searchArray = array_keys($translateArray);
                                    $replaceArray = array_values($translateArray);
                                    $dataValue = str_replace($searchArray, $replaceArray, $conf['dateFormat']);
                                } elseif (!isset($dataArray[$theField])) {
                                    $bValueAssigned = false;
                                } elseif (!$dataValue) {
                                    $dataValue = '0';
                                }
                                break;
                            default:
                                $bValueAssigned = false;
                                break;
                        }

                        if ($bValueAssigned) {
                            $dataArray[$theField] = $dataValue;
                        }
                    }
                }
            }
        }

        return $result;
    }   // parseValues

    /**
    * Checks for valid filenames
    *
    * @param string  $filename: the name of the file
    * @return void
    */
    public function checkFilename($filename)
    {
        $result = true;

        $fI = pathinfo($filename);
        $fileExtension = strtolower($fI['extension']);
        if (
            strpos($fileExtension, 'php') !== false ||
            strpos($fileExtension, 'htaccess') !== false
        ) {
            $result = false; // no php files are allowed here
        }

        if (strpos($filename, '..') !== false) {
            $result = false; //  no '..' path is allowed
        }
        return $result;
    }

    /**
    * Processes uploaded files
    *
    * @param string $theTable: the name of the table being edited
    * @param string  $theField: the name of the field
    * @return array file names
    */
    public function processFiles(
        $theTable,
        $theField,
        array $fieldDataArray,
        $cmdKey,
        &$deleted
    ) {
        $deleted = false;

        if (is_array($GLOBALS['TCA'][$theTable]['columns'][$theField])) {
            $uploadPath = $GLOBALS['TCA'][$theTable]['columns'][$theField]['config']['uploadfolder'];
        }
        $fileNameArray = [];
        $pathSite = Environment::getPublicPath() . '/';

        if ($uploadPath) {
            if (count($fieldDataArray)) {
                foreach ($fieldDataArray as $file) {
                    if (is_array($file)) {
                        if ($this->checkFilename($file['name'])) {
                            if ($file['submit_delete']) {
                                //                              if ($cmdKey != 'edit') {
                                if (@is_file($pathSite . $uploadPath . '/' . $file['name'])) {
                                    @unlink($pathSite . $uploadPath . '/' . $file['name']);
                                    $deleted = true;
                                }
                                //                              }
                            } else {
                                $fileNameArray[] = $file['name'];
                            }
                        }
                    } else {
                        if ($this->checkFilename($file)) {
                            $fileNameArray[] = $file;
                        }
                    }
                }
            }

            if (
                isset($_FILES['FE']['name'][$theTable][$theField]) &&
                is_array($_FILES['FE']['name'][$theTable][$theField])
            ) {
                foreach($_FILES['FE']['name'][$theTable][$theField] as $i => $filename) {

                    if (
                        $filename &&
                        $this->checkFilename($filename) &&
                        $this->evalFileError($_FILES['FE']['error'][$theTable][$theField][$i])
                    ) {
                        $fI = pathinfo($filename);

                        if (GeneralUtility::makeInstance(FileNameValidator::class)->isValid($fI['name'])) {
                            $tmpFilename = basename($filename, '.' . $fI['extension']) . '_' . substr(md5(uniqid($filename)), 0, 10) . '.' . $fI['extension'];
                            $cleanFilename = $this->fileFunc->cleanFileName($tmpFilename);
                            $theDestFile = $this->fileFunc->getUniqueName($cleanFilename, $pathSite . $uploadPath . '/');
                            $result = GeneralUtility::upload_copy_move($_FILES['FE']['tmp_name'][$theTable][$theField][$i], $theDestFile);
                            $fI2 = pathinfo($theDestFile);
                            $fileNameArray[] = $fI2['basename'];
                        }
                    }
                }
            }
        }

        return $fileNameArray;
    }

    /**
    * Saves the data into the database
    *
    * @return void  sets $this->saved
    */
    public function save(
        array &$newRow,
        $staticInfoObj,
        Parameters $controlData,
        $theTable,
        array $dataArray,
        array $origArray,
        $feUser,
        $token,
        $cmd,
        $cmdKey,
        $pid,
        $password,
        $extraFields,
        $hookClassArray
    ) {
        $confObj = GeneralUtility::makeInstance(ConfigurationStore::class);
        $conf = $confObj->getConf();
        $result = 0;
        $includedFields = $confObj->getIncludedFields($cmdKey);
        debug ($includedFields, '$includedFields');
        $usePrivacyPolicy =
            ($cmdKey == 'create') &&
            in_array('privacy_policy_acknowledged', $includedFields);
        debug ($usePrivacyPolicy, '$usePrivacyPolicy +++');


        switch($cmdKey) {
            case 'edit':
            case 'password':
                $theUid = $dataArray['uid'];
                $result = $theUid;
                $authObj = GeneralUtility::makeInstance(Authentication::class);
                $aCAuth = $authObj->aCAuth($origArray, $conf['setfixed.']['EDIT.']['_FIELDLIST'] ?? '');

                // Fetch the original record to check permissions
                if (
                    $conf['edit'] &&
                    ($controlData->isLoggedIn() || $aCAuth)
                ) {
                    // Must be logged in in order to edit  (OR be validated by email)
                    $newFieldList =
                        implode(
                            ',',
                            array_intersect(
                                explode(',', $this->getFieldList()),
                                GeneralUtility::trimExplode(',', $conf[$cmdKey . '.']['fields'], true)
                            )
                        );
                    $newFieldArray =
                        array_unique(
                            array_merge(
                                explode(',', $newFieldList),
                                explode(',', $this->getAdminFieldList()),
                                $this->getAdditionalOverrideFields()
                            )
                        );
                    $fieldArray = GeneralUtility::trimExplode(',', $conf[$cmdKey . '.']['fields'], true);

                    // Do not reset the name if we have no new value
                    if (
                        !in_array('name', $fieldArray) &&
                        !in_array('first_name', $fieldArray) &&
                        !in_array('last_name', $fieldArray)
                    ) {
                        $newFieldArray = array_diff($newFieldArray, ['name']);
                    }
                    // Do not reset the username if we have no new value
                    if (!in_array('username', $fieldArray) && empty($dataArray['username'])) {
                        $newFieldArray = array_diff($newFieldArray, ['username']);
                    }

                    if (
                        $aCAuth ||
                        QueryBuilderApi::accessGranted(
                            $controlData->getContext(),
                            $theTable,
                            $origArray,
                            $feUser,
                            !empty($conf['fe_userEditSelf'])
                        )
                        // $this->coreQuery->DBmayFEUserEdit(
                        //     $theTable,
                        //     $origArray,
                        //     $feUser,
                        //     $conf['allowedGroups'] ?? '',
                        //     $conf['fe_userEditSelf'] ?? ''
                        // )
                    ) {
                        $parsedData =
                            $this->parseOutgoingData(
                                $theTable,
                                $cmdKey,
                                $pid,
                                $conf,
                                $dataArray,
                                $origArray
                            );
                        debug ($parsedData, '$parsedData');

                        $differences =
                            ArrayUtility::arrayDifference(
                                $origArray,
                                $parsedData
                            );
                        debug ($differences, '$differences');
                        $outGoingData = $differences['insertions'] ?? [];

                        if ($theTable == 'fe_users' && isset($dataArray['password'])) {
                            // Do not set the outgoing password if the incoming password was unset
                            $outGoingData['password'] = $password;
                        }
                        debug ($dataArray, '$dataArray +++ HIER');

                        $newFieldList = implode(',', $newFieldArray);
                        debug ($newFieldList, '$newFieldList');
                        if (isset($GLOBALS['TCA'][$theTable]['ctrl']['token'])) {
                            // Save token in record
                            $outGoingData['token'] = $token;
                            // Could be set conditional to adminReview or user confirm
                            $newFieldList .= ',token';
                        }
                        debug ($outGoingData, '$outGoingData');
                        $this->frontendUserRepository->update(
                            $theUid,
                            $outGoingData,
                            $newFieldList,
                        );
                        $this->frontendUserRepository->updateMMRelations($dataArray);
                        $this->setSaved(true);
                        debug ($outGoingData, '$outGoingData');
                        $newRow = $this->parseIncomingData($outGoingData);
                        debug ($newRow, '$newRow nach parseIncomingData');
                        $newRow['uid'] = $theUid;
                        debug ($newRow, '$newRow +++ HIER müssen alle Felder mit Checkbox-Werten eingetragen werden');
                        $fieldList = $this->getFieldList();
                        $checkFields =
                            $this->tca->getCheckboxFields($theTable, $fieldList);
                        debug ($checkFields, '$checkFields TODO: HIER +++');
                        $removeFields = [];
                        foreach ($checkFields as $checkField) {
                            // Missing checked fields must not be unset
                            if (
                                isset($outGoingData[$checkField])
                            ) {
                                $newRow[$checkField] = $outGoingData[$checkField];
                                debug($checkField, '$checkField +++');
                            } else if (isset($dataArray[$checkField])) {
                                $removeFields[] = $checkField;
                            }
                        }
                        debug ($newRow, '$newRow nach Erweiterung checkbox');
                        debug ($removeFields, '$removeFields +++');

                        $modifyFieldList =
                            implode(
                                ',',
                                array_diff(
                                    explode(',', $newFieldList),
                                    $removeFields
                                )
                            );
                    debug ($modifyFieldList, '$modifyFieldList +++');

                        $this->tca->modifyRow(
                            $newRow,
                            $staticInfoObj,
                            $theTable,
                            $modifyFieldList,
                            $usePrivacyPolicy,
                            true
                        );

                        debug ($newRow, '$newRow nach modifyRow');
                        $newRow = array_merge($origArray, $newRow);
                        SystemUtility::userProcess(
                            $this->control,
                            $conf['edit.'],
                            'userFunc_afterSave',
                            ['rec' => $newRow, 'origRec' => $origArray]
                        );

                        // Post-edit processing: call user functions and hooks
                        // Call all afterSaveEdit hooks after the record has been edited and saved
                        if (is_array($hookClassArray)) {
                            foreach($hookClassArray as $classRef) {
                                $hookObj = GeneralUtility::makeInstance($classRef);
                                if (method_exists($hookObj, 'registrationProcess_afterSaveEdit')) {
                                    if (
                                        method_exists($hookObj, 'needsInit') &&
                                        method_exists($hookObj, 'init') &&
                                        $hookObj->needsInit()
                                    ) {
                                        $hookObj->init($this);
                                    }

                                    $hookObj->registrationProcess_afterSaveEdit(
                                        $theTable,
                                        $dataArray,
                                        $origArray,
                                        $token,
                                        $newRow,
                                        $cmd,
                                        $cmdKey,
                                        $pid,
                                        $newFieldList,
                                        $this
                                    );
                                }
                            }
                        }
                    } else {
                        $this->setError('###TEMPLATE_NO_PERMISSIONS###');
                    }
                }
                break;
            default:
                if (is_array($conf[$cmdKey . '.'])) {

                    $newFieldList =
                        implode(
                            ',',
                            array_intersect(
                                explode(
                                    ',',
                                    $this->getFieldList()
                                ),
                                GeneralUtility::trimExplode(
                                    ',',
                                    $conf[$cmdKey . '.']['fields'],
                                    true
                                )
                            )
                        );

                    $newFieldList =
                        implode(
                            ',',
                            array_unique(
                                array_merge(
                                    explode(
                                        ',',
                                        $newFieldList
                                    ),
                                    explode(
                                        ',',
                                        $this->getAdminFieldList()
                                    ),
                                    $this->getAdditionalOverrideFields(),
                                    $this->getAdditionalIncludedFields(),
                                    explode(
                                        ',',
                                        $extraFields
                                    )
                                )
                            )
                        );

                    $parsedArray =
                        $this->parseOutgoingData(
                            $theTable,
                            $cmdKey,
                            $pid,
                            $conf,
                            $dataArray,
                            $origArray
                        );

                    if ($theTable == 'fe_users') {
                        $parsedArray['password'] = $password;
                    }

                    if (isset($GLOBALS['TCA'][$theTable]['ctrl']['token'])) {

                        $parsedArray['token'] = $token;
                        $newFieldList  .= ',token';
                    }

                    $insertFields = [];
                    foreach ($parsedArray as $f => $v) {
                        if (GeneralUtility::inList($newFieldList, $f)) {
                            $insertFields[$f] = $v;
                        }
                    }
                    $newId = $this->frontendUserRepository->save(
                        $this->controlData->getPid(),
                        $insertFields,
                        // $newFieldList,
                    );
                    $result = $newId;

                    // Enable users to own themselves.
                    if (
                        $theTable == 'fe_users' &&
                        !empty($conf['fe_userOwnSelf'])
                    ) {
                        $extraList = '';
                        $tmpDataArray = [];
                        if (isset($GLOBALS['TCA'][$theTable]['ctrl']['fe_cruser_id'])) {
                            $field = $GLOBALS['TCA'][$theTable]['ctrl']['fe_cruser_id'];
                            $dataArray[$field] = $newId;
                            $tmpDataArray[$field] = $newId;
                            $extraList .= ',' . $field;
                        }

                        if (isset($GLOBALS['TCA'][$theTable]['ctrl']['fe_crgroup_id'])) {
                            $field = $GLOBALS['TCA'][$theTable]['ctrl']['fe_crgroup_id'];
                            if (is_array($dataArray['usergroup'])) {
                                [$tmpDataArray[$field]] = $dataArray['usergroup'];
                            } else {
                                $tmpArray = explode(',', $dataArray['usergroup']);
                                [$tmpDataArray[$field]] = $tmpArray;
                            }
                            $tmpDataArray[$field] = intval($tmpDataArray[$field]);
                            $extraList .= ',' . $field;
                        }

                        if (count($tmpDataArray)) {
                            $this->frontendUserRepository->update(
                                $newId,
                                $tmpDataArray,
                                $extraList,
                            );
                        }
                    }
                    $dataArray['uid'] = $newId;
                    $this->frontendUserRepository->updateMMRelations($dataArray);
                    $this->setSaved(true);
                    $newRow = $GLOBALS['TSFE']->sys_page->getRawRecord($theTable, $newId);

                    if (is_array($newRow)) {
                        // Post-create processing: call user functions and hooks

                        $newRow = $this->parseIncomingData($newRow);
                        $this->tca->modifyRow(
                            $newRow,
                            $staticInfoObj,
                            $theTable,
                            $this->getFieldList(),
                            $usePrivacyPolicy,
                            true
                        );

                        SystemUtility::userProcess(
                            $this->control,
                            $conf['create.'],
                            'userFunc_afterSave',
                            ['rec' => $newRow, 'origRec' => $origArray]
                        );

                        // Call all afterSaveCreate hooks after the record has been created and saved
                        if (is_array($hookClassArray)) {
                            foreach ($hookClassArray as $classRef) {
                                $hookObj = GeneralUtility::makeInstance($classRef);
                                if (method_exists($hookObj, 'registrationProcess_afterSaveCreate')) {
                                    if (
                                        method_exists($hookObj, 'needsInit') &&
                                        method_exists($hookObj, 'init') &&
                                        $hookObj->needsInit()
                                    ) {
                                        $hookObj->init($this);
                                    }

                                    $hookObj->registrationProcess_afterSaveCreate(
                                        $this->controlData,
                                        $theTable,
                                        $dataArray,
                                        $origArray,
                                        $token,
                                        $newRow,
                                        $cmd,
                                        $cmdKey,
                                        $pid,
                                        $extraList,
                                        $this
                                    );
                                }
                            }
                        }
                    } else {
                        $this->setError('###TEMPLATE_NO_PERMISSIONS###');
                        $this->setSaved(false);
                        $result = false;
                    }
                }
                break;
        }

        return $result;
    }   // save

    /**
    * Processes a record deletion request
    *
    * @return void  sets $this->saved
    */
    public function deleteRecord(
        Parameters $controlData,
        $theTable,
        array $origArray,
        array &$dataArray,
        array $feUser
    ): void {
        $confObj = GeneralUtility::makeInstance(ConfigurationStore::class);
        $conf = $confObj->getConf();

        if ($conf['delete']) {
            // If deleting is enabled
            if (!empty($origArray)) {

                $authObj = GeneralUtility::makeInstance(Authentication::class);
                $aCAuth = $authObj->aCAuth($origArray, $conf['setfixed.']['DELETE.']['_FIELDLIST']);

                if ($controlData->isLoggedIn() || $aCAuth) {
                    // Must be logged in OR be authenticated by the aC code in order to delete
                    // If the recUid selects a record.... (no check here)

                    if (
                        $aCAuth ||
                        QueryBuilderApi::accessGranted(
                            $controlData->getContext(),
                            $theTable,
                            $origArray,
                            $feUser,
                            !empty($conf['fe_userEditSelf'])
                        )
                        // $this->coreQuery->DBmayFEUserEdit(
                        //     $theTable,
                        //     $origArray,
                        //     $feUser,
                        //     $conf['allowedGroups'] ?? '',
                        //     $conf['fe_userEditSelf'] ?? ''
                        // )
                    ) {
                        // Delete the record and display form, if access granted.
                        $extKey = $controlData->getExtensionKey();

                        // <Ries van Twisk added registrationProcess hooks>
                        // Call all beforeSaveDelete hooks BEFORE the record is deleted
                        if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$extKey]['registrationProcess'])) {
                            foreach($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$extKey]['registrationProcess'] as $classRef) {
                                $hookObj = GeneralUtility::makeInstance($classRef);
                                if (method_exists($hookObj, 'registrationProcess_beforeSaveDelete')) {
                                    if (
                                        method_exists($hookObj, 'needsInit') &&
                                        method_exists($hookObj, 'init') &&
                                        $hookObj->needsInit()
                                    ) {
                                        $hookObj->init($this);
                                    }
                                    $hookObj->registrationProcess_beforeSaveDelete(
                                        $controlData,
                                        $origArray,
                                        $this
                                    );
                                }
                            }
                        }

                        if (
                            empty($GLOBALS['TCA'][$theTable]['ctrl']['delete']) ||
                            !empty($conf['forceFileDelete'])
                        ) {
                            // If the record is being fully deleted... then remove the images or files attached.
                            $this->deleteFilesFromRecord($theTable, $origArray);
                        }
                        $this->frontendUserRepository->delete(
                            $this->getRecUid()
                        );
                        $this->deleteMMRelations(
                            $theTable,
                            $this->getRecUid(),
                            $origArray
                        );
                        $dataArray = $origArray;
                        $this->setSaved(true);
                    } else {
                        $this->setError('###TEMPLATE_NO_PERMISSIONS###');
                    }
                } else {
                    $this->setError('###TEMPLATE_NO_PERMISSIONS###');
                }
            } else {
                $this->setError('###TEMPLATE_INTERNAL_ERROR###');
            }
        }
    }   // deleteRecord

    /**
     * Delete the files associated with a deleted record
     *
     * @param string  $uid: record id
     * @return void
     */
    public function deleteFilesFromRecord(
        $theTable,
        $row
    ): void {
        $updateFields = [];
        $pathSite = Environment::getPublicPath() . '/';
        foreach($GLOBALS['TCA'][$theTable]['columns'] as $field => $conf) {
            if (
                $conf['config']['type'] == 'group' &&
                isset($conf['config']['internal_type']) &&
                $conf['config']['internal_type'] == 'file' &&
                isset($row[$field])
            ) {
                $updateFields[$field] = '';
                $this->frontendUserRepository->update(
                    $uid,
                    $updateFields,
                    $field,
                );
                unset($updateFields[$field]);
                $delFileArr = $row[$field];
                if (!is_array($delFileArr)) {
                    $delFileArr = explode(',', (string) $row[$field]);
                }
                foreach($delFileArr as $n) {
                    if ($n != '') {
                        $fpath = $pathSite . $conf['config']['uploadfolder'] . '/' . $n;
                        if(@is_file($fpath)) {
                            @unlink($fpath);
                        }
                    }
                }
            }
        }
    }   // deleteFilesFromRecord

    /** fetchDate($value)
    *
    *  Check if the value is a correct date in format yyyy-mm-dd
    */
    public function fetchDate(
        $value,
        $dateFormat
    ) {
        $confObj = GeneralUtility::makeInstance(ConfigurationStore::class);
        $conf = $confObj->getConf();

        $resultArray = ['m' => '', 'd' => '', 'y' => ''];
        $dateValue = trim($value);
        $split = $conf['dateSplit'];
        if (!$split) {
            $split = '-';
        }
        $split = '/' . $split . '/';
        $dateFormatArray = preg_split($split, $dateFormat);
        $dateValueArray = preg_split($split, $dateValue);

        $max = sizeof($dateFormatArray);
        $yearOffset = 0;
        for ($i = 0; $i < $max; $i++) {

            switch($dateFormatArray[$i]) {
                // day
                // d - day of the month, 2 digits with leading zeros; i.e. "01" to "31"
                // j - day of the month without leading zeros; i.e. "1" to "31"
                case 'd':
                case 'j':
                    $resultArray['d'] = intval($dateValueArray[$i]);
                    break;
                    // month
                    // m - month; i.e. "01" to "12"
                    // n - month without leading zeros; i.e. "1" to "12"
                case 'm':
                case 'n':
                    $resultArray['m'] = intval($dateValueArray[$i]);
                    break;
                    // M - month, textual, 3 letters; e.g. "Jan"
                    // F - month, textual, long; e.g. "January"
                    // case 'M','F': ...to be written ;break;
                    // year

                    // Y - year, 4 digits; e.g. "1999"
                case 'Y':
                    $resultArray['y'] = intval($dateValueArray[$i]);
                    break;
                    // y - year, 2 digits; e.g. "99"
                case 'y':
                    $yearVal = intval($dateValueArray[$i]);
                    if($yearVal <= 11) {
                        $resultArray['y'] = '20' . $yearVal;
                    } else {
                        $resultArray['y'] = '19' . $yearVal;
                    }
                    break;
            }
        }
        return $resultArray;
    }

    /** evalDate($value)
     *
     *  Check if the value is a correct date in format yyyy-mm-dd
     */
    public function evalDate(
        $value,
        $dateFormat
    ) {
        if(!$value) {
            return false;
        }
        $dateArray = $this->fetchDate($value, $dateFormat);

        if(is_numeric($dateArray['y']) && is_numeric($dateArray['m']) && is_numeric($dateArray['d'])) {
            $result = checkdate($dateArray['m'], $dateArray['d'], $dateArray['y']);
        } else {
            $result = false;
        }
        return $result;
    }   // evalDate

    /**
    * Update MM relations
    *
    * @return void
    */
    // public function updateMMRelations(
    //     $theTable,
    //     array $row
    // ): void {
    //     // update the MM relation
    //     $fieldsList = array_keys($row);
    //     foreach ($GLOBALS['TCA'][$theTable]['columns'] as $colName => $colSettings) {
    //
    //         if (
    //             in_array($colName, $fieldsList) &&
    //             $colSettings['config']['type'] == 'select' &&
    //             isset($colSettings['config']['MM'])
    //         ) {
    //             $valuesArray = $row[$colName];
    //             if (isset($valuesArray) && is_array($valuesArray)) {
    //                 $res =
    //                     $GLOBALS['TYPO3_DB']->exec_DELETEquery(
    //                         $colSettings['config']['MM'],
    //                         'uid_local=' . intval($row['uid'])
    //                     );
    //                 $insertFields = [];
    //                 $insertFields['uid_local'] = intval($row['uid']);
    //                 $insertFields['tablenames'] = '';
    //                 $insertFields['sorting'] = 0;
    //                 foreach($valuesArray as $theValue) {
    //                     $insertFields['uid_foreign'] = intval($theValue);
    //                     $insertFields['sorting']++;
    //                     $res =
    //                         $GLOBALS['TYPO3_DB']->exec_INSERTquery(
    //                             $colSettings['config']['MM'],
    //                             $insertFields
    //                         );
    //                 }
    //             }
    //         }
    //     }
    // }   // updateMMRelations

    /**
    * Delete MM relations
    *
    * @return void
    */
    public function deleteMMRelations(
        string $theTable,
        int $uid,
        array $row = []
    ): void {
        // update the MM relation
        $fieldsList = array_keys($row);

        foreach ($GLOBALS['TCA'][$theTable]['columns'] as $colName => $colSettings) {
            if (
                in_array($colName, $fieldsList) &&
                $colSettings['config']['type'] == 'select' &&
                isset($colSettings['config']['MM'])
            ) {
                $mmTable = $colSettings['config']['MM'];
                $connection = $this->connectionPool
                    ->getConnectionForTable($mmTable);
                $result = $connection->delete(
                    $mmTable,
                    [
                        'uid_foreign' => $uid
                    ]
                );

                // $GLOBALS['TYPO3_DB']->exec_DELETEquery(
                //     $colSettings['config']['MM'],
                //     'uid_local=' . intval($uid)
                // );
            }
        }
    }   // deleteMMRelations

    /**
    * Updates the input array from preview
    *
    * @param array  $inputArr: new values
    * @return array  updated array
    */
    public function modifyDataArrForFormUpdate(
        array $conf,
        array $inputArr,
        $cmdKey
    ) {
        if (is_array($conf[$cmdKey.'.']['evalValues.'])) {
            foreach($conf[$cmdKey.'.']['evalValues.'] as $theField => $theValue) {
                $listOfCommands = GeneralUtility::trimExplode(',', $theValue, true);
                foreach($listOfCommands as $k => $cmd) {
                    $cmdParts = preg_split('/\[|\]/', $cmd); // Point is to enable parameters after each command enclosed in brackets [..]. These will be in position 1 in the array.
                    $theCmd = trim($cmdParts[0]);
                    switch($theCmd) {
                        case 'twice':
                            if (isset($inputArr[$theField])) {
                                if (!isset($inputArr[$theField . '_again'])) {
                                    $inputArr[$theField . '_again'] = $inputArr[$theField];
                                }
                                $this->setAdditionalUpdateFields($this->getAdditionalUpdateFields() . ',' . $theField . '_again');
                            }
                            break;
                    }
                }
            }
        }

        if (is_array($conf['parseValues.'])) {
            foreach($conf['parseValues.'] as $theField => $theValue) {
                $listOfCommands = GeneralUtility::trimExplode(',', $theValue, true);
                foreach($listOfCommands as $k => $cmd) {
                    $cmdParts = preg_split('/\[|\]/', $cmd); // Point is to enable parameters after each command enclosed in brackets [..]. These will be in position 1 in the array.
                    $theCmd = trim($cmdParts[0]);
                    switch($theCmd) {
                        case 'multiple':
                            if (isset($inputArr[$theField])) {
                                unset($inputArr[$theField]);
                            }
                            break;
                        case 'checkArray':
                            if ($inputArr[$theField] && !$this->controlData->isPreview()) {
                                for($a = 0; $a <= 50; $a++) {
                                    if ($inputArr[$theField] & pow(2, $a)) {
                                        $alt_theField = $theField . '][' . $a;
                                        $inputArr[$alt_theField] = 1;
                                        $this->setAdditionalUpdateFields($this->getAdditionalUpdateFields() . ',' . $alt_theField);
                                    }
                                }
                            }
                            break;
                    }
                }
            }
        }

        $inputArr =
            SystemUtility::userProcess(
                $this->control,
                $conf,
                'userFunc_updateArray',
                $inputArr
            );

        foreach($inputArr as $theField => $value) {

            if (is_array($value)) {
                $value = implode(',', $value);
            }
            $inputArr[$theField] = $value;
        }

        SecuredData::secureInput($inputArr, true);

        return $inputArr;
    }   // modifyDataArrForFormUpdate

    /**
    * Moves first, middle and last name into name
    *
    * @param array $dataArray: incoming array
    * @param string $cmdKey: the command key
    * @param string $theTable: the table in use
    * @return void  done directly on $dataArray passed by reference
    */
    public function setName(
        array &$dataArray,
        $cmdKey,
        $theTable
    ): void {
        $confObj = GeneralUtility::makeInstance(ConfigurationStore::class);
        $conf = $confObj->getConf();

        if (
            in_array('name', explode(',', $this->getFieldList())) &&
            !in_array('name', GeneralUtility::trimExplode(',', $conf[$cmdKey . '.']['fields'], true)) &&
            in_array('first_name', GeneralUtility::trimExplode(',', $conf[$cmdKey . '.']['fields'], true)) &&
            in_array('last_name', GeneralUtility::trimExplode(',', $conf[$cmdKey . '.']['fields'], true))
        ) {
            // Honour Address List (tt_address) configuration settings
            $nameFormat = '';
            if (
                $theTable == 'tt_address' &&
                ExtensionManagementUtility::isLoaded('tt_address') &&
                isset($GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['tt_address'])
            ) {
                $extConf = GeneralUtility::makeInstance(ExtensionConfiguration::class)->get('tt_address');
                if (is_array($extConf) && isset($extConf['backwardsCompatFormat'])) {
                    $nameFormat = $extConf['backwardsCompatFormat'];
                }
            }

            if ($nameFormat != '') {
                $dataArray['name'] = sprintf(
                    $nameFormat,
                    $dataArray['first_name'],
                    $dataArray['middle_name'],
                    $dataArray['last_name']
                );
            } else {
                $dataArray['name'] = trim(trim($dataArray['first_name'] ?? '')
                    . ((in_array('middle_name', GeneralUtility::trimExplode(',', ($conf[$cmdKey . '.']['fields'] ?? ''), true)) && !empty($dataArray['middle_name'])) ? ' ' . trim($dataArray['middle_name']) : '')
                    . ' ' . trim($dataArray['last_name'] ?? ''));
            }
        }
    }

    /**
    * Moves email into username if useEmailAsUsername is set
    *
    * @return void  done directly on array $this->dataArray
    */
    public function setUsername(
        $theTable,
        array &$dataArray,
        $cmdKey
    ): void {
        $confObj = GeneralUtility::makeInstance(ConfigurationStore::class);
        $conf = $confObj->getConf();

        if (
            $conf[$cmdKey.'.']['useEmailAsUsername'] &&
            $theTable == 'fe_users' && GeneralUtility::inList($this->getFieldList(), 'username') &&
            empty($this->evalErrors['email'])
        ) {
            $dataArray['username'] = trim($dataArray['email']);
        }
    }

    /**
    * Transforms incoming timestamps into dates
    *
    * @return parsedArray
    */
    public function parseIncomingData(
        array $origArray,
        $bUnsetZero = true
    ) {
        $confObj = GeneralUtility::makeInstance(ConfigurationStore::class);
        $conf = $confObj->getConf();

        $parsedArray = $origArray;
        if (count($origArray) && is_array($conf['parseFromDBValues.'])) {
            foreach($conf['parseFromDBValues.'] as $theField => $theValue) {
                $listOfCommands = GeneralUtility::trimExplode(',', $theValue, true);
                if (is_array($listOfCommands)) {
                    foreach($listOfCommands as $k2 => $cmd) {
                        $cmdParts = preg_split('/\[|\]/', $cmd); // Point is to enable parameters after each command enclosed in brackets [..]. These will be in position 1 in the array.
                        $theCmd = trim($cmdParts[0]);
                        switch($theCmd) {
                            case 'date':
                            case 'adodb_date':
                                if (!empty($origArray[$theField])) {
                                    $parsedArray[$theField] = date(
                                        $conf['dateFormat'],
                                        $origArray[$theField]
                                    );
                                }
                                if (
                                    isset($parsedArray[$theField]) &&
                                    $parsedArray[$theField] == 0
                                ) {
                                    if ($bUnsetZero) {
                                        unset($parsedArray[$theField]);
                                    } else {
                                        $parsedArray[$theField] = '';
                                    }
                                }
                                break;
                        }
                    }
                }
            }
        }

        return $parsedArray;
    }   // parseIncomingData

    /**
     * Processes data before entering the database
     * 1. Transforms outgoing dates into timestamps
     * 2. Modifies the select fields into the count if mm tables are used.
     * 3. Deletes de-referenced files
     *
     * @return parsedArray
     */
    public function parseOutgoingData(
        $theTable,
        $cmdKey,
        $pid,
        $conf,
        array $dataArray,
        array $origArray
    ) {
        debug ($dataArray, 'parseOutgoingData $dataArray');
        debug ($origArray, 'parseOutgoingData $origArray');
        $tablesObj = GeneralUtility::makeInstance(Tables::class);
        $addressObj = $tablesObj->get('address');
        $confObj = GeneralUtility::makeInstance(ConfigurationStore::class);
        $conf = $confObj->getConf();
        $parsedArray = $dataArray;
        $pathSite = Environment::getPublicPath() . '/';

        if (is_array($conf['parseToDBValues.'])) {

            foreach ($conf['parseToDBValues.'] as $theField => $theValue) {
                $listOfCommands = GeneralUtility::trimExplode(',', $theValue, true);
                foreach($listOfCommands as $k2 => $cmd) {
                    $cmdParts = preg_split('/\[|\]/', $cmd); // Point is to enable parameters after each command enclosed in brackets [..]. These will be in position 1 in the array.
                    $theCmd = trim($cmdParts[0]);
                    if (
                        ($theCmd == 'date' || $theCmd == 'adodb_date') &&
                        $dataArray[$theField]
                    ) {
                        if(strlen($dataArray[$theField]) == 8) {
                            $parsedArray[$theField] = substr($dataArray[$theField], 0, 4) . '-' . substr($dataArray[$theField], 4, 2) . '-' . substr($dataArray[$theField], 6, 2);
                        } else {
                            $parsedArray[$theField] = $dataArray[$theField];
                        }
                        $dateArray = $this->fetchDate($parsedArray[$theField], $conf['dateFormat']);
                    }

                    switch ($theCmd) {
                        case 'date':
                        case 'adodb_date':
                            if ($dataArray[$theField]) {
                                $parsedArray[$theField] =
                                    mktime(
                                        0,
                                        0,
                                        0,
                                        $dateArray['m'],
                                        $dateArray['d'],
                                        $dateArray['y']
                                    );

                                // Consider time zone offset
                                // This is necessary if the server wants to have the date not in GMT,
                                // so the offset must be added first to compensate for this
                                // it stands to reason to execute it all the time
                                if (!empty($GLOBALS['TYPO3_CONF_VARS']['SYS']['serverTimeZone'])) {
                                    $parsedArray[$theField] += ($GLOBALS['TYPO3_CONF_VARS']['SYS']['serverTimeZone'] * 3600);
                                }
                            }
                            break;
                        case 'deleteUnreferencedFiles':
                            $fieldConfig = $GLOBALS['TCA'][$theTable]['columns'][$theField]['config'];
                            if (
                                is_array($fieldConfig) &&
                                $fieldConfig['type'] == 'group' &&
                                $fieldConfig['internal_type'] == 'file' &&
                                $fieldConfig['uploadfolder']
                            ) {
                                $uploadPath = $fieldConfig['uploadfolder'];
                                $origFiles = [];
                                if (is_array($origArray[$theField])) {
                                    $origFiles = $origArray[$theField];
                                } elseif ($origArray[$theField]) {
                                    $origFiles = GeneralUtility::trimExplode(',', $origArray[$theField], true);
                                }
                                $updatedFiles = [];
                                if (is_array($dataArray[$theField])) {
                                    $updatedFiles = $dataArray[$theField];
                                } elseif ($dataArray[$theField]) {
                                    $updatedFiles =
                                        GeneralUtility::trimExplode(',', $dataArray[$theField], true);
                                }
                                $unReferencedFiles = array_diff($origFiles, $updatedFiles);
                                foreach ($unReferencedFiles as $file) {
                                    if(@is_file($pathSite . $uploadPath . '/' . $file)) {
                                        @unlink($pathSite . $uploadPath . '/' . $file);
                                    }
                                }
                            }
                            break;
                    }
                }
            }
        }

        // update the MM relation count field
        $fieldsList = array_keys($parsedArray);
        foreach ($GLOBALS['TCA'][$theTable]['columns'] as $colName => $colSettings) {
            if (isset($parsedArray[$colName])) {
                $fieldObj = $addressObj->getFieldObj($colName);

                if (isset($fieldObj) && is_object($fieldObj)) {
                    $foreignTable =
                        $this->tca->getForeignTable(
                            $theTable,
                            $colName
                        );
                    $fieldObj->parseOutgoingData(
                        $theTable,
                        $colName,
                        $foreignTable,
                        $cmdKey,
                        $pid,
                        $conf,
                        $dataArray,
                        $origArray,
                        $parsedArray
                    );
                }

                if (
                    is_array($parsedArray[$colName]) &&
                    in_array($colName, $fieldsList)
                ) {
                    if (
                        $colSettings['config']['type'] == 'category' ||
                        $colSettings['config']['type'] == 'select' &&
                        isset($colSettings['config']['MM'])
                    ) {
                        // set the count instead of the comma separated list
                        if ($parsedArray[$colName]) {
                            $parsedArray[$colName] = count($parsedArray[$colName]);
                        } else {
                            $parsedArray[$colName] = 0;
                        }
                    } elseif (
                        isset($colSettings['config']['type']) &&
                        $colSettings['config']['type'] == 'check'
                    ) {
                        $value = 0;
                        foreach ($parsedArray[$colName] as $dec) {  // Combine values to one hexidecimal number
                            $value |= (1 << $dec);
                        }
                        $parsedArray[$colName] = $value;
                    } else {
                        $parsedArray[$colName] =
                            implode(',', $parsedArray[$colName]);
                    }
                }
            }
        }
        debug ($parsedArray, 'parseOutgoingData ENDE $parsedArray');

        return $parsedArray;
    }   // parseOutgoingData

    /**
    * Checks the error value from the upload $_FILES array.
    *
    * @param string  $error_code: the error code
    * @return boolean  true if ok
    */
    public function evalFileError(
        $error_code
    ) {
        $result = false;
        if ($error_code == 0) {
            $result = true;
            // File upload okay
        } elseif ($error_code == '1') {
            $result = false; // filesize exceeds upload_max_filesize in php.ini
        } elseif ($error_code == '3') {
            return false; // The file was uploaded partially
        } elseif ($error_code == '4') {
            $result = true;
            // No file was uploaded
        } else {
            $result = true;
        }

        return $result;
    }   // evalFileError

    public function getInError()
    {
        return $this->inError;
    }

    /*
     * Sets the index $theField of the incoming data array to empty value depending on type of $theField
     * as defined in the TCA for $theTable
     *
     * @param string $theTable: the name of the table
     * @param string $theField: the name of the field
     * @param array $dataArray: the incoming data array
     * @return void
     */
    protected function setEmptyIfAbsent(
        $theTable,
        $theField,
        array &$dataArray
    ) {
        if (!isset($dataArray[$theField])) {
            $fieldConfig = $GLOBALS['TCA'][$theTable]['columns'][$theField]['config'];
            if (is_array($fieldConfig)) {
                $type = $fieldConfig['type'];
                switch ($type) {
                    case 'check':
                    case 'radio':
                        $dataArray[$theField] = 0;
                        break;
                    case 'input':
                        $eval = $fieldConfig['eval'];
                        switch ($eval) {
                            case 'int':
                            case 'date':
                            case 'datetime':
                            case 'time':
                            case 'timesec':
                                $dataArray[$theField] = 0;
                                break;
                            default:
                                $dataArray[$theField] = '';
                                break;
                        }
                        break;
                    default:
                        $dataArray[$theField] = '';
                        break;
                }
            } else {
                $dataArray[$theField] = '';
            }
        }
    }

    public function removePasswordAdditions(
        $theTable,
        $uid,
        $row
    ): void {
        $deleteFields = [
            'lost_password',
            'tx_agency_password'
        ];
        foreach ($deleteFields as $field) {
            $row[$field] = '';
        }
        $newFieldList = implode(',', $deleteFields);
        $this->frontendUserRepository->update(
            $uid,
            $row,
            $newFieldList
        );
    }

    public function activateLostPassword(
        $uid
    )
    {
        $outGoingData = [];
        $outGoingData['lost_password'] = '1';

        $extraList = 'lost_password';
        $result =
        $this->frontendUserRepository->update(
            $uid,
            $outGoingData,
            $extraList
        );
    }
}
