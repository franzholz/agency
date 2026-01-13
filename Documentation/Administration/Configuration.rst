:navigation-title: Configuration
..  _admin-configuration:

=============
Configuration
=============
  
Configuration steps
====================

These are the steps to configuring the Agency Registration extension:

#.  Install the extension using the Extension Manager. If not already installed, it is recommended that you install the extensions Frontend Login for Website Users (felogin), Salted user password hashes (saltedpasswords), RSA authentication for TYPO3 (rsaauth). And it is required that you install Static Info Tables (static_info_tables) 2.3.0+ and Static Methods since 2007 (div2007) 1.0.3+.
If you want to use the fields of Mail, e.g. the checkbox to send HTML emails, then you must install Mail. Make sure to add the table fields for Mail. You can deinstall Mail afterwards..
#.  Using the TYPO3 Install Tool, frontend (FE) login security level should be configured to 'normal', if using an SSL connection, or to 'rsa' otherwise. When frontend (FE) login security level is configured to 'rsa', the page cannot contain multiple registration/login forms (see: http://forge.typo3.org/issues/34568).
#.  The extension always uses character set utf-8 by default.
#.  Use the status report in Admin Tools->Reports (TYPO3 4.6+ only) to check for possible problems with the installation of the extension (required and conflicting extensions, login security level, salted passwords enablement).
#.  Add the following static template to your TypoScript template: 'Agency Registration'.
#.  Create a folder page that will contain the records of front end users. Set the TS template constant pid to the page id of this folder. In the folder, create two Front End User Groups. Set  the TS template constant userGroupUponRegistration to the uid of the first group and constant userGroupAfterConfirmation to the uid of the second group. The second group gives access to the pages targeted at the registered front end users, while the first group does not. If you use the generation of the user group from an XML file then the user groups will be filled in from uid values in the setfixed setup array.
#.  Insert the login box plugin on any page. Then, on the same page, after the login box, insert the Agency Registration extension, using the default (DE: 'normal') display mode, but setting the Record Storage Page to the folder that will contain the front end user records. Set the TS template constant loginPID to the page id of this page.
#.  Create a second page after the previous one and set the access general options to “Hide at login”. Insert the Agency Registration extension, setting the display mode to Create (DE: 'Anlegen'), and the Record Storage Page to the folder that will contain the front end user records.  Note that the access restriction should be set on the page, not on the content elements. Set the TS template constant registerPID to the page id of this page.
#.  Create a third page limiting access to this page to the second user group you created. Insert the Agency extension, setting the display mode to Edit, and the Record Storage Page to the folder that will contain the front end user records.  Note that the access restriction should be set on the page, not on the content elements. Set the TS template constant editPID to the page id of this page.
#.  Create a fourth page and click the checkbox “Hide in menu”. Insert the Agency Registration plugin, using the Default display mode and setting the Record Storage Page to the folder that will contain the front end user records.  Note that no access restriction should be set on this page, because otherwise unregistered users cannot see the confirmation page, and they cannot click on the confirmation link.  Set the TS template constant confirmPID to the page id of this page.
#.  Decide which fields you want included on the registration form and, among those, which ones you want to be required in order to register. Set TS template constants formFields and requiredFields.
#.  Review the extension constants described below. All these properties may be conveniently edited using the Constant Editor TS template tool.  If you do not use the Constant Editor to configure the extension, please note the form of the constants assignments in the constants section of your TS template:
typoscript:`plugin.tx_agency.property = value`
or if you assign multiple values:

  ..  code-block:: php
      :caption: constants example
  
      plugin.tx_agency { 
         property = value
         ... 
      }
#.  TypoScript Setup:

  ..  code-block:: php
      :caption: EXT:my_extension/ext_localconf.php
  
      config { 
         sys_language_uid = 0
         language = de
         locale_all = german
         typolinkLinkAccessRestrictedPages = NONE
      }
  
Setting an image upload folder compatible with front end login for website users
---------------------------------------------------------------------------------

The path of the image upload folder used by the Agency Registration extension may be set in the installation dialog. The default value is :file:`uploads/tx_agency`. A popular alternative in many configurations is :file:`uploads/pics`. The Agency Registration extension will update the TCA of the fe_users table. Therefore, the back end forms will use the specified path.
It is also possible to show the user image in the user listing produced by the front end login for website users.

