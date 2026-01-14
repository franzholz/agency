:navigation-title: Wrap for Select, Check Box and Radio Button
..  _select-check-radio-wrap:

==============================================
Wrap for Select, Check Boxes and Radio Buttons
==============================================

You can have different select boxes defined in TCA, which can also be shown as checkboxes in FE.
You will need this for Mail or overwritten topics. The configuration needs the activity
  (:php:`create`, :php:`edit`, :php:`email` for email, :php:`preview` for preview,
   :php:`input` for the page with input fields) and the field name of fe_users.

activities: EMAIL

**Example Setup:**

..  code-block:: php
    :caption: setup select box wrap

    plugin.tx_agency {
    	select {
    		email {
    			categories.item.wrap = | <br/>
    		}
    	}
    }


Properties
==========

..  contents::
    :local:


..  _item:

item
----

..  confval:: item
    :name: item
    :type: stdWrap

    Wrap around each single item.
    additional property:
    notLast:  if set the last item will not be wrapped


..  _list:

list
----

..  confval:: list
    :name: list
    :type: stdWrap

    list	stdWrap	Wrap around the list of items.

