:navigation-title: Extension Configuration Variables
..  _admin-configuration:
=================================
Extension Configuration Variables
=================================

The Extension Manager installation dialog allows you to set the following extension configuration variables:

*   **image upload folder**: this is the name of the upload folder for images uploaded by front end users. The default value is **fileadmin/user_upload** . In some configurations, you may prefer to set it to **uploads/tx_agency** . Changes will update the TCA-definition of the image column of the fe_users table;
*   **Maximum image size**: this is the maximum size in kBytes an image may be to be uploaded by front end users. The default value is **500** – changes will update the TCA-definition of the image column of the fe_users table;
*   **Allowed image types**:  this is the list of accepted file extensions for uploaded images. The default value is **png, jpg, jpeg, gif, tif, tiff** . Changes will update the TCA-definition of the image column of the fe_users table.
*   **Gender is set by default**: This will force the gender to be set to 0 for male and 1 for female. If set no undefined gender is allowed.  The default value is **0** .
*   **Endtime Year**: Enter the year for the endtime field. Some Microsoft Windows systems are limited to 2038 for the PHP function mktime. The default value is **2030** .
