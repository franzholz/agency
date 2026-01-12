:navigation-title: Display Mode Specific Setup
..  _display-mode-setup:

===========================
Display Mode Specific Setup
===========================

Use the display mode (lower case) as the first parameter.

**Example:**
..  code-block:: typoscript
    :caption: configure edit.overrideValues.usergroup

    edit.overrideValues.usergroup = 3


Properties
==========

..  contents::
    :local:


..  _fields:

fields
------

..  confval:: fields
    :name: fields
    :type: string
    :Default: See TS constant formFields

    List of fields to be included on the Agency Registration form. Should be a subset of the columns of the :php:`fe_users` table.

    Allows to specify a different list of fields for each CODE.
    
..  _required:

required
--------

..  confval:: required
    :name: required
    :type: string
    :Default: See TS constant formFields

    List of fields that must be filled in on the Agency Registration form. Should be a subset of the list specified
    on the 'formFields' property.

    Allows to specify a different list of required fields for each CODE.

..  _default-values:

defaultValues
-------------

..  confval:: defaultValues
    :name: default-values
    :type: array of strings
    :Default: See TS constant formFields

    Default values for the fields.
    

..  _override-values:

overrrideValues
---------------

..  confval:: overrrideValues
    :name: overrrideValues
    :type: array of strings/stdWrap

    Array of field names for which a fixed value or stdWrap function shall be applied.
    The stdWrap e.g. can be used when FE Users should not be able to change a field, 
    which they must fill out only at registration.

    **Example:**
    ..  code-block:: typoscript
        :caption: Example overrideValues
    
        overrideValues {
            username =
            usergroup >
            disable = 0
            by_invitation >
            user_myfield = {TSFE:fe_user|user|user_myfield}
            user_myfield.insertData = 1
        }


..  _eval-values:

evalValues
-------------

..  confval:: evalValues
    :name: eval-values
    :type: array of strings
    :Default: See TS constant formFields

    Check functions to be applied on the fields.
    

    
    
