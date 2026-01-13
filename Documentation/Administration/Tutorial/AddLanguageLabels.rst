:navigation-title: Add Language Labels
..  _add-language-labels:

==========================================
Add the language labels for the new fields
==========================================

The language labels for the additional database fields should be added in Step 1 above. 
These will be used by the back end forms. The language labels used by the front end plugin 
should be added in the TS template setup using the method described in the Localization 
section of this document. For each language of interest to you with :php`languageCode`,
which must be replaced by :php`de`, :php`default` or others. All cursive letters in the following 
examples must be replaced with your field names and texts.
You may need the following statements for each additional field with *myNewFieldName*000:
The basic field label:

..  code-block:: typoscript
    :caption: Set the *myNewFieldLabel* into *languageCode* for *myNewFieldName* 

     plugin.tx_agency._LOCAL_LANG.languageCode.myNewFieldName = myNewFieldLabel

The message displayed when the field is required but missing:

..  code-block:: typoscript
    :caption: Set the *missing_myNewFieldName* for *myNewFieldName* 

    plugin.tx_agency._LOCAL_LANG.languageCode.missing_myNewFieldName = missingRequiredFieldMessage

Same as previous but for the invitation form:

..  code-block:: typoscript
    :caption: Set the *missing_invitation_myNewFieldName* for *myNewFieldName* 

    plugin.tx_agency._LOCAL_LANG.languageCode.missing_invitation_myNewFieldName = missingRequiredFieldMessageOnInvitationForm

The following message displayed when a validation rule evalRuleName applicable to the field is not satisfied:

..  code-block:: typoscript
    :caption: Set the *evalErrors_evalRuleName_myNewFieldName* for *myNewFieldName* 

    plugin.tx_agency._LOCAL_LANG.languageCode.evalErrors_evalRuleName_myNewFieldName = errorMessageForEvalRule



