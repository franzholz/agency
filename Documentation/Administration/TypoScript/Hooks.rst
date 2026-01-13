:navigation-title: Available Hooks
..  _available-hooks:

===============
Available Hooks
===============

Thee sets of hooks may be used by the extension.
The first set of hooks is named confirmRegistrationClass and offers the possibility of the following two hooks:
•	confirmRegistrationClass_preProcess: this hook is invoked just BEFORE the registration confirmation (or so-called setfixed) is processed;
•	confirmRegistrationClass_postProcess: this hook is invoked just AFTER the registration confirmation (or so-called setfixed) is processed.
The second set of hooks is named registrationProcess and offers the possibility of the following four hooks:
•	registrationProcess_beforeConfirmCreate: this hook is invoked just BEFORE the user record is filled for preview by the user;
•	registrationProcess_afterSaveCreate: this hook is invoked just AFTER the user record has been created and saved;
•	registrationProcess_afterSaveEdit: this hook is invoked just AFTER the user record has been edited and saved;
•	registrationProcess_beforeSaveDelete: this hook is invoked just BEFORE the user record is deleted.
The third set of hooks is for global markers and processing.
•	addGlobalMarkers: This hook is invoked in the fillInMarkerArray function of the marker object. You can add your own global markers here. This is used for the markers from the extensions captcha, sr_freecap, voucher and others.
Each set of hooks must be defined within a class, each hook being a method of this class. However, all seven hooks could be defined as methods of the same class.
Some of the hooks receive two parameters: the current front end user record (or marker array, in the case of addGlobalMarkers) and a reference to the invoking object. In the case of registrationProcess_beforeConfirmCreate, the first parameter is also passed as a reference so that some action may be taken on the content of the record.
The hooks are configured by the following assignments which could be included in the ext_localconf.php file of the extension providing the hooks:
$TYPO3_CONF_VARS['EXTCONF']['agency']['confirmRegistrationClass'][] = classReference;
$TYPO3_CONF_VARS['EXTCONF']['agency']['registrationProcess'][] = classReference;

Note that these are arrays, therefore you could configure multiple hooks of each type for various purposes.
File hooks/class.tx_agency_hooksHandler.php of this extension provides a simple example class containing seven hooks doing nothing but provinding the interface you must use. File ext_localconf.php also contains example statements for configuring these example hooks. They are commented out. If you uncomment them, you should see the hooks being invoked when the extension is used in the front end. You must enter some PHP echo lines into the example hooks.
In the case of the confirmRegistrationClass, if the confirmation page is configured to be redirected to auto-login, you may not notice on the front end that the example hooks are being invoked.
In the case of the registrationProcess_beforeConfirmCreate example hook, if the plugin is configured to generate a username, a username is generated based on the first and last names of the user.
For more information on hooks, see: http://typo3.org/documentation/document-library/core-documentation/doc_core_api/4.2.0/view/3/4/#id4198363 .



