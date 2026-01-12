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


..  _terms-url:

termsUrl
--------

..  confval:: termsUrl
    :name: termsUrl
    :type: string

    Page (id or id,type) or url where the terms of usage are shown.

    ..  note::
        If set, overrides file.termsFile.

    ..  note::
        This is used in conjunction with the field 'terms_acknowledged'.

