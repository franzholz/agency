# TYPO3 extension agency

## What is does

This extension provides methods to register front end users in the TYPO3 table `fe_users`.
Use the forum at https://www.jambage.com to ask questions and find answers.
A documentation manual.odt is available in the doc folder.


## Upgrade Requirement

In version 0.15.0 you must execute the Upgrade Wizard to convert the agency list type plugin into content elements identified by the CType agency.


## Compatibility

TYPO3 has new requirements for front end user passwords

* At least 8 chars
* At least one number
* At least one upper case char
* At least one special char

Use create.evalValues.password to set the password evaluation method.
