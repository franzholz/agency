:navigation-title: Display of Front End User Data
..  _display-front-end-user-data:

==============================
Display of Front End User Data
==============================

If you want to display the FE users data without any edit functionality, you can use TypoScript for setting this up.
*by Peter Klein*

**Example Setup:**

..  code-block:: php
    :caption: Setup for Display of Front End User Data

    lib.userinfo = COA_INT
    lib.userinfo {
      10 = TEXT
      10.data = TSFE:fe_user|user|username
      10.wrap = Username:&nbsp;|<br />
      10.required = 1
    
      # Use "first_name" and "last_name", if present,
      # otherwise use the "name" field
      20 = COA
      20.1 = TEXT
      20.1.data = TSFE:fe_user|user|first_name
      20.1.required = 1
      20.2 = TEXT
      20.2.data = TSFE:fe_user|user|last_name
      20.2.wrap = &nbsp;|
      20.2.required = 1
      20.stdWrap.ifEmpty.data = TSFE:fe_user|user|name
      20.stdWrap.wrap = Name:&nbsp;|<br />
      20.stdWrap.required = 1
    
      30 <.10
      30.data = TSFE:fe_user|user|address
      30.wrap = Address:&nbsp;|<br />
    
      40 <.10
      40.data = TSFE:fe_user|user|city
      40.wrap = City:&nbsp;|<br />
    
      50 <.10
      50.data = TSFE:fe_user|user|zip
      50.wrap = Zip:&nbsp;|<br />
    
      60 <.10
      60.data = TSFE:fe_user|user|country
      60.wrap = Country:&nbsp;|<br />
    
      70 <.10
      70.data = TSFE:fe_user|user|company
      70.wrap = Company:&nbsp;|<br />
    
      80 <.10
      80.data = TSFE:fe_user|user|telephone
      80.wrap = Telephone:&nbsp;|<br />
    
      90 <.10
      90.data = TSFE:fe_user|user|fax
      90.wrap = Fax:&nbsp;|<br />
    
      100 <.10
      100.data = TSFE:fe_user|user|email
      100.typolink.parameter.data = TSFE:fe_user|user|email
      100.wrap = Email:&nbsp;|<br />
    
      110 <.10
      110.data = TSFE:fe_user|user|www
      110.typolink.parameter.data = TSFE:fe_user|user|www
      110.typolink.extTarget = _blank
      110.wrap = WWW:&nbsp;|<br />
    
      200 < .10
      200.data = TSFE:fe_user|user|usergroup
      200.split {
        token = ,
        cObjNum = 1 || 2
        1 {
         10 = CONTENT
         10.table = fe_groups
          # ID 13 is the folder where I store my FE users and FE groups.
          # Remember to change it to the ID of the folder
          # where YOU store your FE data!!
         10.select.pidInList = 13
         10.select.andWhere.current = 1
         10.select.andWhere.wrap = uid=|
         10.renderObj = TEXT
         10.renderObj.field = title
         10.renderObj.wrap = |,
        }
        2 < .1
        2.10.renderObj.wrap >
      }
      200.wrap = Member of group(s):&nbsp;|<br />
    
      300 < .10
      300.data = TSFE:fe_user|user|image
      300.split {
        token = ,
        cObjNum = 1
        1 {
         10 = IMAGE
         10.file.import.current = 1
         10.file.import = uploads/tx_agency/
         10.file.width = 100
        }
      }
      300.wrap = Image(s):&nbsp;|<br />
    }
    page.100 < lib.userinfo
