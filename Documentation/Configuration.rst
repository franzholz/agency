:navigation-title: Configuration
..  _configuration:

=============
Configuration
=============

..  _site-set:

Include the site set
====================

This extension comes with a site set called `jambagecom/agency-default-plugin-set`. To use it include
this set in your site configuration via

..  code-block:: diff
    :caption: config/sites/my-site/config.yaml (diff)

     base: 'https://example.com/'
     rootPageId: 1
    +dependencies:
    +  - jambagecom/agency-default-plugin-set

See also: `TYPO3 Explained, Using a site set as dependency in a site <https://docs.typo3.org/permalink/t3coreapi:site-sets-usage>`_.


