:navigation-title: Typoscript Constants
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

    Comma separated list of user groups to be selectable. If 'ALL', then all groups are allowed.

    ..  note::
        See also constant **allowUserGroupSelection**.


..  _allowed-sub-groups:

allowedSubgroups
----------------

..  confval:: allowedSubgroups
    :name: allowed-user-groups
    :type: string

    Comma separated list of subgroups from which all user groups shall be selectable. 
    The user groups from allowedUserGroups shall be added also.

    ..  note::
        See also constant **allowedUserGroups**.


..  _denied-user-groups:

deniedUserGroups
-----------------

..  confval:: deniedUserGroups
    :name: denied-user-groups
    :type: string

    Comma separated list of user groups to be never selectable.
    These usegroups supersede the resulted user grous from allowedUserGroups and allowedSubgroups.

    ..  note::
        See also constants **allowedUserGroups** and **allowedSubgroups**.


..  _keep-unselectable-user-groups:

keepUnselectableUserGroups
--------------------------

..  confval:: keepUnselectableUserGroups
    :name: keep-unselectable-user-groups
    :type: boolean
    :Default: 0 (false)

    If set und if a FE user is changing his FE user group then all the other FE user groups which 
    are not shown in the select box will remain. If unset then the user will only keep the 
    user group selected by himself and the reserved user groups which consist of the temporal
    user groups from the registration process: userGroupUponRegistration, userGroupAfterConfirmation, 
    userGroupAfterAcceptation .
    This is only useful if allowUserGroupUpdate has been set, too.

Note: See also the constants allowedUserGroups., allowedSubgroups and deniedUserGroups

    ..  note::
        See also constants **allowedUserGroups**, **allowedSubgroups** and **deniedUserGroups**..


..  _enable-email-on-approve:

enableEmailOnApprove
--------------------

..  confval:: enableEmailOnApprove
    :name: enable-email-on-approve
    :type: boolean
    :Default: 1 (true)

    Enable email on approval. If set, an email will be sent to the front end user when he confirms his registration.
    Effective only if email confirmation request is enabled.


..  _enable-email-on-refuse:

enableEmailOnRefuse
-------------------

..  confval:: enableEmailOnRefuse
    :name: enable-email-on-refuse
    :type: boolean
    :Default: 1 (true)

    Enable email on refusal: If set, an email will be sent to the prospective front end user 
    when he refuses to confirm his registration. Effective only if email confirmation request is enabled.


..  _enable-email-on-admin-refuse:

enableEmailOnAdminRefuse
------------------------

..  confval:: enableEmailOnAdminRefuse
    :name: enable-email-on-admin-refuse
    :type: boolean
    :Default: 1 (true)

    Enable email on denial: If set, an email will be sent to the prospective front end user
    when the site administrator refuses to confirm his registration.


..  _enable-email-on-register:

enableEmailOnRegister
---------------------

..  confval:: enableEmailOnRegister
    :name: enable-email-on-register
    :type: boolean
    :Default: 1 (true)

    Enable email on registration: If set, an email will be sent to the front end user 
    confirming his registration. Ignored if email confirmation request is enabled.


..  _enable-email-on-update:

enableEmailOnUpdate
-------------------

..  confval:: enableEmailOnUpdate
    :name: enable-email-on-update
    :type: integer
    :Default: 1

    Enable email on profile update. If set to 1, an email will be sent to the front end user to confirm 
    the update of his profile. If set to 2, only the changed fields will be sent.


..  _enable-email-on-delete:

enableEmailOnDelete
-------------------

..  confval:: enableEmailOnDelete
    :name: enable-email-on-delete
    :type: boolean
    :Default: 1 (true)

    Enable email on delete. If set, an email will be sent to the front end user to confirm the deletion of his account.


..  _enable-email-on-unsubscribe:

enableEmailOnUnsubscribe
------------------------

..  confval:: enableEmailOnUnsubscribe
    :name: enable-email-on-unsubscribe
    :type: boolean
    :Default: 1 (true)

    Enable email on unsubscribe: If set, an email will be sent to the front end user end its subscription
    to newsletters through a link from a mailing application.


..  _enable-email-password:

enableEmailPassword
-------------------

..  confval:: enableEmailPassword
    :name: enable-email-password
    :type: boolean
    :Default: 0 (false)

    Enable email for password creation: If set, an email can be sent to the front end user 
    who has forgotten his password.


..  _enable-admin-notify-confirmation:

enableAdminNotifyConfirmation
-----------------------------

..  confval:: enableAdminNotifyConfirmation
    :name: enable-admin-notify-confirmation
    :type: boolean
    :Default: 1 (true)

    If set, an email will be sent to the site administrator when a request for confirmation of registration
    is sent to a user.


..  _enable-admin-notify-on-approve:

enableAdminNotifyOnApprove
--------------------------

..  confval:: enableAdminNotifyOnApprove
    :name: enable-admin-notify-on-approve
    :type: boolean
    :Default: 1 (true)

    Enable admin notification on approval: If set, an email will be sent to the site administrator 
    when a user confirms his registration.


..  _enable-admin-notify-on-refuse:

enableAdminNotifyOnRefuse
-------------------------

..  confval:: enableAdminNotifyOnRefuse
    :name: enable-admin-notify-on-refuse
    :type: boolean
    :Default: 1 (true)

    Enable admin notification on refusal. If set, an email will be sent to the site administrator 
    when a user refuses to confirm his registration.


..  _enable-admin-notify-on-register:

enableAdminNotifyOnRegister
---------------------------

..  confval:: enableAdminNotifyOnRegister
    :name: enable-admin-notify-on-register
    :type: boolean
    :Default: 1 (true)

    Enable admin notification on registration. If set, an email will be sent to the site administrator 
    when a user registers. This is ignored if email confirmation request is enabled.


..  _enable-admin-notify-on-update:

enableAdminNotifyOnUpdate
-------------------------

..  confval:: enableAdminNotifyOnUpdate
    :name: enable-admin-notify-on-update
    :type: boolean
    :Default: 1 (true)

    Enable admin notification on update: If set, an email will be sent to the site administrator 
    when a user updates his profile.


..  _enable-admin-notify-on-delete:

enableAdminNotifyOnDelete
-------------------------

..  confval:: enableAdminNotifyOnDelete
    :name: enable-admin-notify-on-delete
    :type: boolean
    :Default: 1 (true)

    Enable admin notification on deletion: If set, an email will be sent to the site administrator 
    when a user deletes his account.


..  _enable-admin-notify-on-enter:

enableAdminNotifyOnEnter
------------------------

..  confval:: enableAdminNotifyOnEnter
    :name: enable-admin-notify-on-enter
    :type: boolean
    :Default: 0 (false)

    Enable admin notification on entering: If set, an email will be sent to the site administration
    when a user clicks on the link to log in.


..  _enable-admin-notify-on-admin-accept:

enableAdminNotifyOnAdminAccept
------------------------------

..  confval:: enableAdminNotifyOnAdminAccept
    :name: enable-admin-notify-on-admin-accept
    :type: boolean
    :Default: 1 (true)

    Enable admin notification on approval by admin: If set, an email will be sent to 
    the site administration when a registration is accepted by the administration.


..  _enable-admin-notify-on-admin-refuse:

enableAdminNotifyOnAdminRefuse
------------------------------

..  confval:: enableAdminNotifyOnAdminRefuse
    :name: enable-admin-notify-on-admin-refuse
    :type: boolean
    :Default: 1 (true)

    Enable admin notification on refusal by admin: If set, an email will be sent to 
    the site administration when a registration is refused by the administration.


..  _enable-admin-notify-on-unsubscribe:

enableAdminNotifyOnUnsubscribe
------------------------------

..  confval:: enableAdminNotifyOnUnsubscribe
    :name: enable-admin-notify-on-unsubscribe
    :type: boolean
    :Default: 1 (true)

    Enable admin notification on unsubscribe: If set, an email will be sent to 
    the site administration when the user ends his membership through a link from a mailing application.


..  _enable-admin-notify-on-password:

enableAdminNotifyOnPassword
---------------------------

..  confval:: enableAdminNotifyOnPassword
    :name: enable-admin-notify-on-password
    :type: boolean
    :Default: 1 (true)

    Enable admin notification on lost password: If set, an email will be sent to 
    the site administration when the user requests to recreate his lost password..


..  _mail-checked-upon-registration:

mailCheckedUponRegistration
---------------------------

..  confval:: mailCheckedUponRegistration
    :name: mail-checked-upon-registration
    :type: boolean
    :Default: 0 (false)

    Check all the checkboxes for Mail categories in creation dialogues.


..  _user-group-upon-registration:

userGroupUponRegistration
-------------------------

..  confval:: userGroupUponRegistration
    :name: user-group-upon-registration
    :type: string

    User group(s) upon registration: The value of the user group field. Must be a list of integers 
    WITHOUT spaces between the comma separated values! Furthermore, these integers MUST point
    to actual fe_groups records!


..  _user-group-after-confirmation:

userGroupAfterConfirmation
--------------------------

..  confval:: userGroupAfterConfirmation
    :name: user-group-after-confirmation
    :type: string

    User group(s) after confirmation: The value of the user group(s) assigned to
    the front end user after receipt of his confirmation.


..  _user-group-after-acceptation:

userGroupAfterAcceptation
--------------------------

..  confval:: userGroupAfterAcceptation
    :name: user-group-after-acceptation
    :type: string

    User group(s) after acceptation: The value of the user group(s) assigned to 
    the front end user after acceptation of his(her) registration, when the administrative review is enabled.

    ..  note::
        If administrative review is not enabled, this property is ignored.


..  _password-at-least:

passwordAtLeast
---------------

..  confval:: passwordAtLeast
    :name: password-at-least
    :type: string

    Minimum password length: The minimum number of characters that the password must contain.


..  _password-at-most:

passwordAtMost
---------------

..  confval:: passwordAtMost
    :name: password-at-most
    :type: string

    Maximum password length: The maximum number of characters that the password may contain.


..  _username-at-least:

usernameAtLeast
---------------

..  confval:: usernameAtLeast
    :name: password-at-least
    :type: string

    Minimum username length: The minimum number of characters that the username must contain.

..  _username-at-most:

usernameAtMost
---------------

..  confval:: usernameAtMost
    :name: username-at-most
    :type: string

    Maximum username length: The maximum number of characters that the username may contain.


..  _name-at-most:

nameAtMost
----------

..  confval:: nameAtMost
    :name: name-at-most
    :type: integer
    :Default: 80

    Maximum name length: The maximum number of characters that the name of the front end user may contain.


..  _slpha-specials:

alphaSpecials
-------------

..  confval:: alphaSpecials
    :name: slpha-specials
    :type: string

    Allow special characters to alpha: The parse values for the alpha check normally contain alphabethical letters. 
    Here you can add also special characters (e.g. German Umlauts) to the username.

     :typoscript:`alphaSpecials = äöüß`

..  _force-file-delete:

forceFileDelete
---------------

..  confval:: forceFileDelete
    :name: force-file-delete
    :type: boolean
    :Default: 1 (true)

    If set, on record deletion, related images and files will be deleted even if the record in
    the database is only marked :code:`deleted` .


..  _max-images:

maxImages
---------

..  confval:: maxImages
    :name: max-images
    :type: integer
    :Default: 6

    Number of images that may be uploaded.
    Value: An integer larger than 0 and smaller than 7.

    ..  note::
        Has no effect if the field 'image' is not in the list formFields.


..  _salutation:

salutation
----------

..  confval:: salutation
    :name: salutation
    :type: string

    If set, should be either 'formal' or 'informal'.

    ..  note::
        This property will change the salutation mode used in the labels displayed in the front end, 
        provided that this is meaningful in the language being displayed and that
        translators have provided the informal version.

..  _use-short-urls:

useShortUrls
------------

..  confval:: useShortUrls
    :name: use-short-urls
    :type: boolean
    :Default: 1 (true)

    Enable the use of short URLs in email messages sent to users is enabled. Do not disable this
    if possible, because a short URL provides the highest security level.


..  _use-localization:

useLocalization
---------------

..  confval:: useLocalization
    :name: use-localization
    :type: boolean
    :Default: 0 (false)

    Enable the localization for selectable items.

..  _use-local-country:

useLocalCountry
---------------

..  confval:: useLocalCountry
    :name: use-local-country
    :type: boolean
    :Default: 0 (false)

    Show the country names in their local languages.


..  _short-url-life:

shortUrlLife
------------

..  confval:: shortUrlLife
    :name: short-url-life
    :type: int+
    :Default: 30

    Short URL cache lifespan. This is the number of days that short URL's will be kept in the DB. 
    After this delay, the short URL's will be removed. Confirmation URL's included in email messages 
    older than this number of days will not work.

..  _date-format:

dateFormat
----------

..  confval:: dateFormat
    :name: date-format
    :type: string
    :Default: d-m-Y

    date format
    You should also change the error message output if you change this.

    day:
    *   d - day of the month, 2 digits with leading zeros; i.e. "01" to "31"
    *   j - day of the month without leading zeros; i.e. "1" to "31"
    month:
    *   m - month; i.e. "01" to "12"
    *   n - month without leading zeros; i.e. "1" to "12"
    year:
    *   Y - year, 4 digits; e.g. "1999"
    *   y - year, 2 digits; e.g. "99"


..  _date-split:

dateSplit
---------

..  confval:: dateSplit
    :name: date-split
    :type: string
    :Default: -

    split characters for the dateFormat.  This is a regular expression and might need the escape character '\'!
    Use :typoscript:`dateSplit = \.` to separate the date with a dot :php:`.` .


..  _merge-labels:

mergeLabels
-----------

..  confval:: mergeLabels
    :name: merge-labels
    :type: boolean
    :Default: 1 (true)

    If set then the labels for select boxes found in setup are merged to the labels already in the TCA, 
    which might come from TCEFORM.fe_users altLabels or :php:`addItems`. If :typoscript:`0`, then only the labels from 
    :php:`plugin.tx_agency._LOCAL_LANG.de.fe_users.x.I.x`  are taken.


