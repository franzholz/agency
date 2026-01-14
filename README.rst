TYPO3 extension agency
======================

What is does
------------

This extension provides methods to register front end users in the TYPO3
table ``fe_users``. Use the forum at https://www.jambage.com to ask
questions and find answers. A documentation manual.odt is available in
the doc folder.

Upgrade Requirement
-------------------

Since version 0.15.0 you must execute the Upgrade Wizard to convert the
agency list type plugin into content elements identified by the CType
agency.

Compatibility
-------------

TYPO3 has new requirements for front end user passwords

-  At least 8 chars
-  At least one number
-  At least one upper case char
-  At least one special char

Use create.evalValues.password to set the password evaluation method.

To-Do List
----------

A number of useful features could be added to this extension. Some of
these features are listed below. If you have developed such enhancements
and would like them to be integrated into the base extension, or if you
would be interested in sponsoring any such enhancements, please have a
look to chapter ‘Get Help’ ot the documentation.

Possible feature enhancements:
..............................

•   If email address is used as username and email confirmation request is enabled, send confirmation request when the email address is edited;
•   offer a registration process by regular mail.
