:navigation-title: Display Mode Specific Setup
..  _display-mode-setup:

===========================
Display Mode Specific Setup
===========================

Use the display mode (lower case) as the first parameter.

**Example:**
..  code-block:: typoscript
    :caption: EXT:my_extension/ext_localconf.php

    defined('TYPO3') or die();

edit.overrideValues.usergroup = 3


Properties
==========

..  contents::
    :local:


..  _extra-labels:

extraLabels
-----------

..  confval:: extraLabels
    :name: extra-labels
    :type: string

    Comma-separated list of additional labels to use in the HTML template.

    See section Labels and localisation about adding extra  labels.
