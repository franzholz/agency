..  _localization:

============
Localization
============


Language files
==============


The main language file is :file:`Resources/Private/Language/locallang.xlf`. 
See `Translation files (XLIFF format) <https://docs.typo3.org/permalink/t3coreapi:xliff>`_  .

Use the TYPO3 back end to install the available translations.


Activate language
You must activate the language in your TypoScript setup. Here comes an example how to activate German as the main language for TYPO3.
Example:
# language
   config.htmlTag_langKey = de
   config.language = de
   config.locale_all = de_DE
   config.sys_language_uid = 0
Adapting labels
You may adapt the labels in pi/locallang.xlf to to your needs and languages.
Any label may be overridden by inserting the appropriate assignment in your TS template setup:
plugin.tx_agency._LOCAL_LANG.languageCode.labelName = overridingValue

You can find the name of the label (languageCode) you want to modify (or translate) by inspecting the extension file pi/locallang.xlf.
Overriding labels specified in TCA
You may also override by the same method labels from other files when they are referenced by the TCA definition of a field (see the tutorial section on Adding fields to the registration form). This is done as follows:
plugin.tx_agency._LOCAL_LANG.languageCode.tableName.fieldName = overridingValue
Switching salutation mode
You may also switch the salutation mode used in these labels when this is relevant for the language being used and when the labels are either available in the pi/locallang.xlf file or provided by TypoScript setup. See the TypoScript Reference section.
v_dear_male or v_dear_female markers will be used instead of v_dear if a gender has been entered by the user.
Localization of user group title
This extension adds table fe_groups_language_overlay in order to allow localization of the user group title.
en_US localization
If config.language is set to en_US in TS template setup, labels localized to US English will be used in the front end. If not available, default (en_GB) labels will be used.
Labels with variables
Some labels in pi/locallang.xlf have names starting with 'v_'. In those labels, the following variables may be used:
•	%1$s : the title of the pid containing the front end user records created by the extension;
•	%2$s : the user name of the front end user;
•	%3$s : the name of the front end user;
•	%4$s : the email address of the front end user.
•	%5$s : the password of the front end user.

Special functions can be inserted. They will be replaced by the result of the function.
•	{data:<field>}: value of this field of the FE user record
•	{tca:<field>}: value of the marker ###TCA_INPUT_field'###'
•	{meta:<stuff>}: extra stuff functions:
title: page title

Adding extra labels
Property extraLabels in TS setup may specify a list of extra labels that may be used in the HTML template.
The values of these labels are specified in TS setup with the same type of assignment as when overriding localized labels:
plugin.tx_agency._LOCAL_LANG.languageCode.extraLabelName = extraLabelValue
