# TYPO3 extension agency

## What is does

This extension provides methods to register front end users in the TYPO3 table fe_users.
Use the forum at https://www.jambage.com to ask questions and find answers.
A documentation manual.sxw is available in the doc folder.



## Compatibility

TYPO3 12 has new requirement for front end user passwords

* At least 8 chars
* At least one number
* At least one upper case char
* At least one special char

However the password cannot be checked, because it is transfered to the server in an encrypted form.
