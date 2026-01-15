:navigation-title: Captcha
..  _captcha:

=======
Captcha
=======

It is possible to activate the display of a captcha image and a text field where the user must enter the text displayed on the image.
Extension sr_freecap or captcha must have been installed. The captcha_response field should not be specified as a required field.
Set in TS Constants:

..  code-block:: php
    :caption: activate captcha

    formFields := addToList(captcha_response)

or, using the TS Constant Editor, add captcha_response to the list of fields

Set also in TS Setup:

..  code-block:: php
    :caption: activate captcha

    plugin.tx_agency {
      create.evalValues.captcha_response = freecap
    }

or set in TS Setup:

..  code-block:: php
    :caption: activate captcha in evalValues

    plugin.tx_agency {
        create.evalValues.captcha_response = captcha
    }


