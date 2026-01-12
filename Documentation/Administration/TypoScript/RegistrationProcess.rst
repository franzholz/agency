:navigation-title: Registration Process
..  _registration-process:

====================
Registration Process
====================

Use 'setfixed' as the first and the registration process name (upper case) as the second parameter.
**Example:**

  ..  code-block:: php
    :caption: Example setfixed

    setfixed.ACCEPT {
       _FIELDLIST = uid,pid,usergroup
       usergroup = {$plugin.tx_agency.userGroupAfterAcceptation}
       disable = 0
    }

Codes:
======
*  	APPROVE: It starts after the user clicks the confirmation link in the email.
*  	ACCEPT: It starts after the admin clicks the acceptation confirmation link in the email.
*  	DELETE: It starts when the user starts the deletion process.
*  	REFUSE: It starts after the admin clicks the refusal link in the email
*   ENTER: It starts after the user clicks the login link in the email.



Properties
==========

..  contents::
    :local:


..  _field-list:

_FIELDLIST
----------

..  confval:: _FIELDLIST
    :name: _FIELDLIST
    :type: string
    :Default: uid,pid,usergroup

    List of fields to be used by the generated links. The encoding and decoding process wil consider these fields.

    Allows to specify a different list of fields for each PROCESS.


..  _fieldname-placeholder:

*placeholder fieldname*
-----------------------

..  confval:: *placeholder fieldname+
    :name: _FIELDLIST
    :type: string

    Replace *placeholder fieldname* with the database's field name of the table fe_users or tt_address resp. .
    The listed table field names will get a new value after the registration process.


..  _fieldname-dot-placeholder:

*placeholder fieldname.*
-----------------------

..  confval:: *placeholder fieldname+
    :name: _FIELDLIST
    :type: array

    Replace *placeholder fieldname* with the database's field name of the table fe_users or tt_address resp. .
    The listed table field names will get a new value after the registration process.


