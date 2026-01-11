:navigation-title: Extension Configuration Variables
..  _admin-configuration:
=================================
Extension Configuration Variables
=================================

The Extension Manager installation dialog allows you to set the following extension configuration variables:

*   **image upload folder**: this is the name of the upload folder for images uploaded by front end users. The default value is **fileadmin/user_upload** . In some configurations, you may prefer to set it to **uploads/tx_agency** . Changes will update the TCA-definition of the image column of the fe_users table;
*   **Maximum image size**: this is the maximum size in kBytes an image may be to be uploaded by front end users. The default value is **250** – changes will update the TCA-definition of the image column of the fe_users table;
*   **Allowed image types**:  this is the list of accepted file extensions for uploaded images. The default value is **png, jpg, jpeg, gif, tif, tiff** . Changes will update the TCA-definition of the image column of the fe_users table.
