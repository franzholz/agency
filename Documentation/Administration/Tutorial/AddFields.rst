:navigation-title: Adding Fields
..  _adding-fields:

==================================
Adding Fields to Registration Form
==================================

Extend the fe_users table
=========================

The simplest way to extend the fe_users table is to create a small extension that will define the required fields 
in the database and the TCA. Your small extension will not contain any plugin or other processing.
Extension Kickstarter is a wizard that will help you create this small extension. 
If not yet installed, install the extension using the Extension Manager. Once installed, you access the 
Kickstarter Wizard through the Extension Manager back end module. Just remember that you will not need to create
any plugin or TypoScript. Once your extension is created, install it. This will make the fields you have 
defined available to the TYPO3 backend. In this tutorial the name of the extension is :php:`mynewext`.

Add the fields in the HTML template
===================================

You need to update the HTML template in order to include the fields you have defined in the proper subparts.
The subparts of interest are:
:php:`###TEMPLATE_CREATE###`
:php:`###TEMPLATE_CREATE_PREVIEW###`
:php:`###TEMPLATE_INVITE###`
:php:`###TEMPLATE_INVITE_PREVIEW###`
:php:`###TEMPLATE_EDIT###`
:php:`###TEMPLATE_EDIT_PREVIEW###`
and perhaps some of the email subparts.
Have a look at how the predefined fields are included in each of the subparts, and do the same for the 
fields you are adding. The HTML for a field named myNewFieldName in the :php:`CREATE`, :php:`INVITE` and :php:`EDIT` subparts 
would look like:

..  code-block:: html
    :caption: Enhancement of the HTML template by self defined field
  
    <!-- ###SUB_INCLUDED_FIELD_myNewFieldName### -->
    <dt>
    	<label for="agency-myNewFieldName">###LABEL_MYNEWFIELDNAME###</label>
    	<span class="agency-required">###REQUIRED_MYNEWFIELDNAME###</span>
    </dt>
    <dd>
    	<!-- ###SUB_ERROR_FIELD_myNewFieldName### -->
    	<p class="agency-error">###EVAL_ERROR_FIELD_myNewFieldName###</p>
    	<!-- ###SUB_ERROR_FIELD_myNewFieldName### -->
    	<!-- ###SUB_REQUIRED_FIELD_myNewFieldName### -->
    	<p class="agency-error">###MISSING_MYNEWFIELDNAME###</p>
    	<!-- ###SUB_REQUIRED_FIELD_myNewFieldName### -->
    	<input id="agency-myNewFieldName" type="text" size="40" maxlength="50" title="###TOOLTIP_MYNEWFIELDNAME###" name="###NAME_MYNEWFIELDNAME###" class="agency-text" />
    </dd>
    <!-- ###SUB_INCLUDED_FIELD_myNewFieldName### -->
    
In the case of a field of type textarea, check, radio and select, the HTML for the field should rather look like the following:

..  code-block:: html
    :caption: Enhancement of the HTML template by self defined field of type textarea, check, radio and select

    <!-- ###SUB_INCLUDED_FIELD_myNewFieldName### -->
    <dt>
    	<label for="agency-myNewFieldName">###LABEL_MYNEWFIELDNAME###</label>
    	<span class="agency-required">###REQUIRED_MYNEWFIELDNAME###</span>
    </dt>
    <dd>
    	<!-- ###SUB_ERROR_FIELD_myNewFieldName### -->
    	<p class="agency-error">###EVAL_ERROR_FIELD_myNewFieldName###</p>
    	<!-- ###SUB_ERROR_FIELD_myNewFieldName### -->
    	<!-- ###SUB_REQUIRED_FIELD_myNewFieldName### -->
    	<p class="agency-error">###MISSING_MYNEWFIELDNAME###</p>
    	<!-- ###SUB_REQUIRED_FIELD_myNewFieldName### -->
    	###TCA_INPUT_myNewFieldName###
    </dd>
    <!-- ###SUB_INCLUDED_FIELD_myNewFieldName### -->

 In each PREVIEW subpart, you need to add lines like:

..  code-block:: html
    :caption: Enhancement of the HTML template by self defined field in preview
        
    <!-- ###SUB_INCLUDED_FIELD_myNewFieldName### -->
    <dt>###LABEL_MYNEWFIELDNAME###</dt>
    <dd>###FIELD_MYNEWFIELDNAME###</dd>
    <!-- ###SUB_INCLUDED_FIELD_myNewFieldName### -->

+++


