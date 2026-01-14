:navigation-title: FAQ

..  _faq:

================================
Frequently Asked Questions (FAQ)
================================

..  accordion::
    :name: faq

    ..  accordion-item:: How can I install this extension?
        :name: installation
        :header-level: 2
        :show:

        See chapter :ref:`installation`.

    ..  accordion-item:: How can I include the TypoScript?
        :name: configuration
        :header-level: 2

        See chapter :ref:`configuration`.

    ..  accordion-item:: Where to get help?
        :name: help
        :header-level: 2

        See chapter :ref:`help`.

    ..  accordion-item:: The confirmation link does not work. It shows just the site url example.com without any parameters needed to confirm the registration on the confirmation page.
        :name: confirmation link not working
        :header-level: 2

        You have set access restrictions on the page for the confirmation (confirmPID).
        The link to this page could not be generated for the email,
        because the user has no rights to see it. So remove the page restrictions (Edit page properties – Access).

    ..  accordion-item:: An error message is shown: Internal error in the Agency Registration.

        No text replacement has been found for **missing_tx_myextension_my_tablefield**
        :name: no marker replacement
        :header-level: 2

        You have to add the marker replacements for the user-defined fields into
        the setup. Follow the steps in chapter “Add the language labels for the new fields”

    ..  accordion-item:: No error is shown. The data entry form does not proceed :
        :name: frozen data entry
        :header-level: 2

        Add the marker :php:`###EVAL_ERROR_saved###` inside of the subpart :php:`<!-- ###TEMPLATE_EDIT### -->`
        of the template file. Then a collected error message will be shown on that place.

    ..  accordion-item:: Form's token is empty (randomly)
        :name: empty form token
        :header-level: 2

        Set :php:`$TYPO3_CONF_VARS['FE']['cookieDomain'] = my.domain.com`.

        See
    `cookieDomain <https://docs.typo3.org/m/typo3/reference-coreapi/main/en-us/Configuration/Typo3ConfVars/SYS.html#confval-globals-typo3-conf-vars-sys-cookiedomain>`_
    for details.


    ..  accordion-item:: The Mail markers are not substituted:
        :php:`###LABEL_CATEGORIES###` :php:`###TCA_INPUT_categories###`
        :php:`###LABEL_MAIL_HTML###` :php:`###TCA_INPUT_mail_html###`
        :php:`###LABEL_MAIL_HTML_CHECKED###`
        :name: empty form token
        :header-level: 2

        You must install Mail, add the Mail tables in the EM or Install Tool.
        Afterwards deinstall Mail or leave it. Then go to the EM of Agency and set
        enableMail to 1 if no Mail is installed or 0 if Mail is installed.

        Other Solution:
        Do not use these markers : Remove all subparts and
        markers for Mail from the HTML template file.



