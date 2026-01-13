:navigation-title: Using Frames
..  _using-frames:

============
Using Frames
============


If the registration confirmation page (confirmPID) is designed to be displayed within frames, then add the following lines to your TS template setup:

..  code-block:: typoscript
    :caption: TypoScript for frames

    [globalVar = TSFE:id = {$plugin.tx_agency.confirmPID}]
    config.page.frameReloadIfNotInFrameset = 1
    config.linkVars >
    config.linkVars =  L,agency,fD,cmd,rU,aC
    config.no_cache = 1
    [global]

after setting :typoscript:`plugin.tx_agency.confirmPID` in your TS template constants.

..  note::
    cmd, rU and aC are in the list for compatibility with the Mail extension. Still valid?
