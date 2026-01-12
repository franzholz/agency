:navigation-title: Usergroup Generation
..  _usergroup-generation:

====================
Usergroup Generation
====================

Use :typoscript:`setfixed` as the first, the registration process name (upper case) as the second parameter and 
':typoscript:`usergroup` as the third parameter.
  
**Example:**

..  code-block:: typoscript
    :caption: Example setfixed.ACCEPT.usergroup

    setfixed.ACCEPT.usergroup {
       10.uid = 3
       10.file = fileadmin/Mitglieder.xml  
    }

Meanings of the array lines :

Properties
==========

..  contents::
    :local:


..  _usergroup-uid

uid
---

..  confval:: uid
    :name: usergroup-uid
    :type: integer

    The database table field uid of fe_groups. This is the uid of the FE user group.

