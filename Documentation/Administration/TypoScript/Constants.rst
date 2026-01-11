:navigation-title: Configuration
..  _typoscript-constants:

====================
TypoScript Constants
====================



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


..  _template-file:

file.templateFile
-----------------

..  confval:: file.templateFile
    :name: file-templateFile
    :type: file[html,htm,tmpl,txt]
    :Default: EXT:agency/Resources/Private/Templates/AgencyRegistrationTemplate.html

    File name of the HTML template

file.attachmentFile
-------------------

..  confval:: file.attachmentFile
    :name: file-attachmentFile
    :type: file[pdf,doc,txt]
    :Default: EXT:agency/Resources/Public/Examples/tx_agency_sample.txt

    File name of a file to be attached to the registration confirmation email.


..  _terms-file:

file.termsFile
--------------

..  confval:: file.termsFile
    :name: file-termsFile
    :type: file[pdf,doc,sxw,txt]
    :Default: EXT:agency/Resources/Public/Examples/tx_agency_terms.txt

    File name of the terms of usage file.


    ..  note::
        This is used in conjunction with the field 'terms_acknowledged'.


..  _privacy-policy-file:

file.privacyPolicyFile
----------------------

..  confval:: file.privacyPolicyFile
    :name: file-privacyPolicyFile
    :type: file[pdf,doc,sxw,txt]
    :Default: EXT:agency/Resources/Private/Templates/AgencyPrivacyPolicy.txt

    File to be shown as the privacy policy.


..  _privacy-policy-url:

privacyPolicyUrl
----------------

..  confval:: privacyPolicyUrl
    :name: privacyPolicyUrl
    :type: string

    Page (id or id,type) or url where the privacy policy is shown.
    If set, it overrides the privacy policy file. This is needed for compliance with DSGVO / GDPR .


..  _enable-html-mail:

enableHTMLMail
--------------

..  confval:: enableHTMLMail
    :name: enableHTMLMail
    :type: boolean
    :Default: 1 (true)

    If set, emails sent to the front end user will be sent in HTML format. A plain text version will always be included in the emails.


..  _enable-email-attachment:

enableEmailAttachment
---------------------

..  confval:: enableEmailAttachment
    :name: enableEmailAttachment
    :type: boolean
    :Default: 0 (false)

    If set, and if enableHTMLMail is also set, the attachment file - specified by file.attachmentFile -
    will be attached to the registration confirmation HTML email.


..  _enable-auto-login-on-confirmation:

enableAutoLoginOnConfirmation
-----------------------------

..  confval:: enableAutoLoginOnConfirmation
    :name: enableAutoLoginOnConfirmation
    :type: boolean
    :Default: 0 (false)

    If set, the user will be automatically logged in upon confirmation of his registration.

..  _enable-auto-login-on-create:

enableAutoLoginOnCreate
-----------------------

..  confval:: enableAutoLoginOnCreate
    :name: enableAutoLoginOnCreate
    :type: boolean
    :Default: 0 (false)


    Enable auto-login on account creation: if set and if email confirmation is not set, the user will be automatically logged in upon creation of his(her) account.


..  _enable-auto-login-on-confirmation:

enableAutoLoginOnInviteConfirmation
-----------------------------------

..  confval:: enableAutoLoginOnInviteConfirmation
    :name: enableAutoLoginOnInviteConfirmation
    :type: boolean
    :Default: 1 (true)


    If set, the user will be automatically logged in upon confirmation of his (her) invitation.

..  _auto-login-redirect-url:

autoLoginRedirect_url
---------------------

..  confval:: autoLoginRedirect_url
    :name: autoLoginRedirect-url
    :type: string

    When auto login is enabled, URL to which the user may be redirected upon login.


..  _html-mail-css:

HTMLMailCSS
-----------

..  confval:: HTMLMailCSS
    :name: HTMLMailCSS
    :type: string
    :Default: EXT:agency/template/tx_agency_htmlmail_xhtml.css

    File name of  the HTML emails style sheet. If HTML emails are enabled, this file contains the CSS style sheet to be incorporated in these emails.


..  _email:

email
-----

..  confval:: email
    :name: email
    :type: string
    :Default: MyTypo3Site@mydomain.org

    Administration email address. This email address will be the sender email address and the recipient of administrator notifications.


..  _site-name:

siteName
--------

..  confval:: siteName
    :name: siteName
    :type: string
    :Default: My Typo3 Site

    Name of the registering site. If set, this will be used as the email address name in all sent emails and may be used as a signature on the mails.


..  _form-fields:

formFields
----------

..  confval:: formFields
    :name: formFields
    :type: positive integer / :ref:`stdWrap <stdwrap>`
    :Default: username, password, gender, first_name, last_name, status, date_of_birth, email, address, city, zone, static_info_country, zip, telephone, fax, language, title, company, www, mail_html, categories, image, comments, terms_acknowledged, privacy_policy_acknowledged, disable

    List of fields to be included on the Agency Registration form. Should be a subset of the columns of the 'fe_users' table.

    ..  note::
        If the Mail (mail) extension is not installed, fields categories and mail_html are ignored (removed from the list).

    ..  note::
        Check your HTML template for the presence of the markers of the fields



..  _required-fields:

requiredFields
--------------

..  confval:: requiredFields
    :name: requiredFields
    :type: positive string
    :Default: username,password,first_name,last_name,email

    List of fields that must be filled in on the Agency Registration form. Should be a subset of the list specified on the 'formFields' property.

    ..  note::
        captcha_response should not be set as a required field.



..  _do-not-enforce-username:

doNotEnforceUsername
--------------------

..  confval:: doNotEnforceUsername
    :name: doNotEnforceUsername
    :type: boolean
    :Default: 0 (false)

    If set, field username is not forced to be part of formFields and requiredFields.

..  _unsubscribe-allowed-fields:

unsubscribeAllowedFields
~~~~~~~~~~~~~~~~~~~~~~~~

..  confval:: unsubscribeAllowedFields
    :name: unsubscribeAllowedFields
    :type: string
    :Default: module_sys_dmail_newsletter

    Unsubscribe allowed fields: List of fields that are allowed to be updated by an UNSUBSCRIBE link without any login.

    ..  note::
        Applies to setfixed links from mailing applications with the following query string values: &agency[cmd]=setfixed&sFK=UNSUBSCRIBE.
        A value for a field listed may be specified with a string of the form: &fD[fieldName]=value. The value of the field of the will be updated when the link is processed.


..  _code-length:

codeLength
~~~~~~~~~~

..  confval:: codeLength
    :name: codeLength
    :type: int
    :Default: 8

    Length of the authentication codes.

    ..  note::
        Direct Mail extension uses only 8 in its calculations.


..  _formName:

formName
~~~~~~~~

..  confval:: formName
    :name: formName
    :type: string

    Name of the HTML form. The name is also referenced on the  :typoscript:`onChangeCountryAttribute`. See below.


..  _on-change-country-attribute:

onChangeCountryAttribute
~~~~~~~~~~~~~~~~~~~~~~~~

..  confval:: onChangeCountryAttribute
    :name: on-change-country-attribute
    :type: string
    :Default: javascript:window.document.forms['fe_users_form'].submit();

    Javascript to execute when the selected country is changed in the country selector box.


..  _default-code:

defaultCODE
-----------

..  confval:: defaultCODE
    :name: default-code
    :type: string

    Default CODE, when not specified on the inserted plugin record. May be CREATE or EDIT or empty.


..  _pid:

pid
---

..  confval:: pid
    :name: pid
    :type: page_id

    Front end user records PID. If the records edited/created are located in another page than the 'current,' the PID of that page should be specified here.


..  _user-groups-pid-iist:

userGroupsPidList
-----------------

..  confval:: userGroupsPidList
    :name: user-groups-pid-iist
    :type: list of page_id's

    User groups records PID list: List of page id's on which user groups records are located.

    ..  note::
        Note: If specified, user groups records will be assumed to be located on one of the specified pages, rather than on the page specified by 'pid'.


..  _categories-pidlist:

categories_PIDLIST
------------------

..  confval:: categories_PIDLIST
    :name: categories-pidlist
    :type: positive integer / :ref:`stdWrap <stdwrap>`
    :Default: 0

    PID list for Direct Mail categories. The Direct mail categories used by the plugin will be restricted to those found on the pages identified by the PID's in this list.


..  _pid-title-override:

pidTitleOverride
----------------

..  confval:: pidTitleOverride
    :name: pid-title-override
    :type: string

    The specified string will override the title of the System Folder page specified by the pid property.
    The title of the System Folder is used in some online and email messages in the default HTML template.


..  _register-pid:

registerPID
-----------

..  confval:: registerPID
    :name: register-pid
    :type: page_id

    Registration page PID: PID of the page on which the extension is inserted with the intent of serving as the Agency Registration page.
    If not set, will default to 'current' page.


..  _edit-pid:

editPID
-------

..  confval:: editPID
    :name: edit-pid
    :type: page_id

    Profile editing page PID: PID of the page on which the extension is inserted with the intent of serving as the 
    front end user profile editing page. If not set, will default to 'current' page.


..  _link-to-pid:

linkToPID
---------

..  confval:: editPID
    :name: link-to-pid
    :type: page_id

    Link to after edit PID: PID of a page to be linked to after the user has completed the editing of his/her profile. 
    See also linkToPIDAddButton.


..  _link-to-pid-add-button:

linkToPIDAddButton
------------------

..  confval:: linkToPIDAddButton
    :name: link-to-pid-add-button
    :type: page_id

    Add a button to link to after edit. If set, an additional button is displayed on the profile editing page,
    or on the profile editing preview page, to save the changes and link to the page specified by linkToPID.


..  _confirm-pid:

confirmPID
-----------

..  confval:: confirmPID
    :name: confirm-pid
    :type: page_id

    Confirmation page PID: PID of the page on which the extension is inserted with the intent of serving as the 
    front end user confirmation page (or setfixed page!).


..  _confirm-invitation-pid:

confirmInvitationPID
--------------------

..  confval:: confirmInvitationPID
    :name: confirm-invitation-pid
    :type: page_id

    Confirmation of invitation page PID: PID of the page on which the extension is inserted with the intent of serving as the
    front end user confirmation page (or setfixed page!) when replying to an invitation. Meaningful only if email confirmation request is enabled.

    ..  note::
        If not set, will take the same value as confirmPID.


..  _password-pid:

passwordPID
-----------

..  confval:: passwordPID
    :name: password-pid
    :type: page_id

    Password page PID: PID of the page on which the plugin is inserted with the intent of entering a new password.


..  _confirm-type:

confirmType
-----------

..  confval:: confirmType
    :name: confirm-type
    :type: int
    :Default: 0

    Confirmation page Type: Type (or pageNum) of the confirmation page. Meaningful only
    if email confirmation request is enabled.

..  _login-pid:

loginPID
--------

..  confval:: loginPID
    :name: login-pid
    :type: page_id

    Login page PID: PID of the page on which the New login box extension is inserted with the intent of serving 
    as the front end user login page. If not set, will default to 'current' page.


..  _enable-preview-register:

enablePreviewRegister
---------------------

..  confval:: enablePreviewRegister
    :name: enable-preview-register
    :type: boolean
    :Default: 1 (true)

    Enable preview on registration. If set, the registration dialog will include a preview of the 
    front end user data before it is saved.


..  _enable-preview-edit:

enablePreviewEdit
-----------------

..  confval:: enablePreviewEdit
    :name: enable-preview-edit
    :type: boolean
    :Default: 1 (true)

    Enable preview on profile update. If set, the profile update dialog will include a preview of the 
    front end user data before it is saved.


..  _enable-admin-review:

enableAdminReview
-----------------

..  confval:: enableAdminReview
    :name: enable-admin-review
    :type: boolean
    :Default: 0 (false)

    Enable administrative review. If set, the site administrator will be asked to accept the registration 
    before it becomes enabled.


..  _enable-email-confirmation:

enableEmailConfirmation
-----------------------

..  confval:: enableEmailConfirmation
    :name: enable-email-confirmation
    :type: boolean
    :Default: 1 (true)

    Enable email confirmation request: If set, an email will be sent to the prospective
    front end user requesting a confirmation of registration.


..  _use-email-as-username:

useEmailAsUsername
------------------

..  confval:: useEmailAsUsername
    :name: use-email-as-username
    :type: boolean
    :Default: 0 (false)

    Enable the use of the email address as username.

    ..  note::
        If enableEmailConfirmation is also set, the email field will NOT be included in the front end user profile editing form.


..  _generate-username:

generateUsername
----------------

..  confval:: generateUsername
    :name: generate-username
    :type: boolean
    :Default: 0 (false)

    Generate the username. If set, the username is assumed to be generated.

    ..  note::
        Hook registrationProcess_beforeConfirmCreate must be configured.


..  _generate-password:

generatePassword
----------------

..  confval:: generatePassword
    :name: generate-password
    :type: integer
    :Default: 0

    Generate the password: If non-zero, a random password is generated. The number of characters
    in the password is given by this parameter.



..  _allow-user-group-selection:

allowUserGroupSelection
-----------------------

..  confval:: allowUserGroupSelection
    :name: allow-user-group-selection
    :type: boolean
    :Default: 0 (false)

    Allow selection of usergroup on registration. If set, the user may select to adhere to
    user group(s) when registering.

    ..  note::
        The selectable usergroups must be located in the page identified by the **pid** constant.

    ..  note::
        If constants **userGroupUponRegistration** and **userGroupAfterConfirmation** are set, 
        the usergroups they specify are not selectable.

    ..  note::
        Field **usergroup** must be included in the list specified by constant **formFields**.


..  _allow-user-group-update:

allowUserGroupUpdate
---------------------

..  confval:: allowUserGroupSelection
    :name: allow-user-group-update
    :type: boolean
    :Default: 0 (false)

    Allow selection of usergroup on editing. If set, the user may edit the list of user groups 
    to which he(she) belongs.

    ..  note::
        See also constant **allowUserGroupSelection**.



..  _allow-multiple-user-group-selection:

allowMultipleUserGroupSelection
-------------------------------

..  confval:: allowMultipleUserGroupSelection
    :name: allow-multiple-user-group-selection
    :type: boolean
    :Default: 0 (false)

    Allow selection of multiple usergroups. If set, the user may select to adhere to multiple user groups.

    ..  note::
        See also constants **allowUserGroupSelection** and **allowUserGroupUpdate**.



..  _allowed-user-groups:

allowedUserGroups
-----------------

..  confval:: allowedUserGroups
    :name: allowed-user-groups
    :type: string
    :Default: 0 (false)

    Allow selection of multiple usergroups. If set, the user may select to adhere to multiple user groups.

    ..  note::
        See also constants **allowUserGroupSelection** and **allowUserGroupUpdate**.



TODO ++++


..  confval:: splitRendering.[array]
    :name: gifbuilder-text-splitRendering-array
    :type: integer

    With keyword being [charRange, highlightWord].

    *   **splitRendering.[array] = keyword** with keyword being
        [:ref:`charRange <gifbuilder-text-splitRendering-charRange>`,
        :ref:`highlightWord <gifbuilder-text-splitRendering-highlightWord>`]

    *   **splitRendering.[array] {**

        *   **fontFile:** Alternative font file for this rendering.

        *   **fontSize:** Alternative font size for this rendering.

        *   **color:** Alternative color for this rendering, works *only*
            without :ref:`gifbuilder-text-niceText`.

        *   **xSpaceBefore:** x space before this part.

        *   **xSpaceAfter:** x space after this part.

        *   **ySpaceBefore:** y space before this part.

        *   **ySpaceAfter:** y space after this part.

        **}**

    ..  _gifbuilder-text-splitRendering-charRange:

    **Keyword: charRange**

    :typoscript:`splitRendering.[array].value` = Comma-separated list of
    character ranges (for example, :typoscript:`100-200`) given as Unicode
    character numbers. The list accepts optional starting and ending points,
    for example, :typoscript:`- 200` or :typoscript:`200 -` and single values,
    for example, :typoscript:`65, 66, 67`.

    ..  _gifbuilder-text-splitRendering-highlightWord:

    **Keyword: highlightWord**

    :typoscript:`splitRendering.[array].value` = Word to highlight, makes a case
    sensitive search for this.

    **Limitations:**

    *   The pixel compensation values are not corrected for scale factor used
        with :ref:`gifbuilder-text-niceText`. Basically this means
        that when :typoscript:`niceText` is used, these values will have only
        the half effect.

    *   When word spacing is used the :typoscript:`highlightWord` mode does not
        work.

    *   The color override works only without :typoscript:`niceText`.

    **Example:**

    ..  code-block:: typoscript
        :caption: EXT:site_package/Configuration/TypoScript/setup.typoscript

        10.splitRendering.compX = 2
        10.splitRendering.compY = -2
        10.splitRendering.10 = charRange
        10.splitRendering.10 {
          value = 200-380 , 65, 66
          fontSize = 50
          fontFile = EXT:core/Resources/Private/Font/nimbus.ttf
          xSpaceBefore = 30
        }
        10.splitRendering.20 = highlightWord
        10.splitRendering.20 {
          value = TheWord
          color = red
        }


..  _gifbuilder-text-splitRendering-compX:

compX
~~~~~

..  confval:: splitRendering.compX
    :name: gifbuilder-text-splitRendering-compX
    :type: integer

    Additional pixel space between parts, x direction.


..  _gifbuilder-text-splitRendering-compY:

compY
~~~~~

..  confval:: splitRendering.compY
    :name: gifbuilder-text-splitRendering-compY
    :type: integer

    Additional pixel space between parts, y direction.


..  _gifbuilder-text-text:

text
----

..  confval:: text
    :name: gifbuilder-text-text
    :type: string / :ref:`stdWrap <stdwrap>`

    This is text on the image file. The item is rendered only, if this string is
    not empty.

    The :php:`$cObj->data` array is loaded with the page record, if, for
    example, the :typoscript:`GIFBUILDER` object is used in TypoScript.


..  _gifbuilder-text-textMaxLength:

textMaxLength
-------------

..  confval:: textMaxLength
    :name: gifbuilder-text-textMaxLength
    :type: integer
    :Default: 100

    The maximum length of the :ref:`gifbuilder-text-text`. This is just a
    natural break that prevents incidental rendering of very long texts!


..  _gifbuilder-text-wordSpacing:

wordSpacing
-----------

..  confval:: wordSpacing
    :name: gifbuilder-text-wordSpacing
    :type: positive integer / :ref:`stdWrap <stdwrap>`
    :Default: :ref:`spacing <gifbuilder-text-spacing>` * 2

    The pixel distance between words.
