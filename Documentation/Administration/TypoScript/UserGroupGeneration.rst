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


..  _usergroup-file

file
----

..  confval:: file
    :name: usergroup-file
    :type: string

    File name of the XML file for member comparison. See example file 
    :file:`Resources/Public/Examples/tx_agency_members.xml`.
    The xml file uses the table field names of fe_users as the leaf names.
    The fields :php:`cnum`, :php:`last_name`, :php:`email` and php:`zip` are required.

When a user registers, then a different user group will be used if the user's customer number (cnum) is already present in a XML file and if the following conditions are matched :
The entered customer number must fit the cnum of the XML file and then it is checked if either the last names are equal or if otherwise both the zip codes and the emails are equal.
The XML uses Row tags inside of the Members tag.
XML format :

..  code-block:: xml
    :caption: EXT:agemcy/Resources/Public/Examples/tx_agency_members.xml

    <?xml version="1.0" ?>
    <Members>
      <Row>
      	<cnum>00001</cnum>
      	<last_name>Mustermann</last_name>
      	<zip>80100</zip>
      	<email>max.mustermann@mail.de</email>
      </Row>
      <Row>
      	<cnum>00002</cnum>
      	<last_name>Musterfrau</last_name>
      	<zip>80200</zip>
      	<email>susi.musterfrau@mail.dk</email>
      </Row>
    </Members>

