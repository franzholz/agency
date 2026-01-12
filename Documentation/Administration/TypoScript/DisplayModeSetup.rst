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

    

