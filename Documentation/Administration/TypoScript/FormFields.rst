:navigation-title: Typoscript Constants
..  _typoscript-constants:

==========================================
Modify the TS template Constants and Setup
==========================================

Using the Constant Editor, modify the value of the following constants of plugin  agency:

*   formFields: add to the list of fields the names of the fields you have defined and want 
    to be displayed in the front end form. If the field is not in this list, the subpart
:html:`<!--###SUB_INCLUDED_FIELD_myNewFieldName###-->` will be deleted;
*   requiredFields: add to the list of fields the names of the fields you have defined and 
    want to be treated as required fields. If the field is not in the list OR if the field is 
    in this list and is correctly filled, the subpart
    :html:`<!--###SUB_REQUIRED_FIELD_myNewFieldName###-->` will be deleted.

You may also specify in the TS template Setup some default values and validation rules to be
applied by the extension to the additional fields.
If there are no validation rules, you should set:

..  code-block:: typoscript
    :caption: unset create evalValues validation for new field

    plugin.tx_agency.create.evalValues.myNewFieldName =

If your field is a select field and if you wish to enable multiple selection, you should also set:

..  code-block:: typoscript
    :caption: set parseValues for new field

     plugin.tx_agency.parseValues.myNewFieldName  = multiple


