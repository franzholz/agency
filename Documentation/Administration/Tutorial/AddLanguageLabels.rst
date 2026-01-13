:navigation-title: Add Language Labels
..  _add-language-labels:

==========================================
Add the language labels for the new fields
==========================================

The language labels for the additional database fields should be added in Step1 above. 
These will be used by the Backend forms. The language labels used by the front end plugin 
should be added in the TS template setup using the method described in  the Localization 
section of this document. For each language of interest to you with languageCode,
which must be replaced by 'de', 'default' or others. All cursive letters in the following 
examples must be replaced with your field names and texts.
You may need the following statements for each additional field with myNewFieldName:
The basic field label:
plugin.tx_agency._LOCAL_LANG.languageCode.myNewFieldName = myNewFieldLabel

The message displayed when the field is required but missing:
plugin.tx_agency._LOCAL_LANG.languageCode.missing_myNewFieldName = missingRequiredFieldMessage

Same as previous but for the invitation form:
plugin.tx_agency._LOCAL_LANG.languageCode.missing_invitation_myNewFieldName = missingRequiredFieldMessageOnInvitationForm

The following message displayed when a validation rule evalRuleName applicable to the field is not satisfied:
plugin.tx_agency._LOCAL_LANG.languageCode.evalErrors_evalRuleName_myNewFieldName = errorMessageForEvalRule



