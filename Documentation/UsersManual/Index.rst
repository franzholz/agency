===========
User Manual
===========

First, the extension must be installed and configured: see the Configuration section.
Second, the HTML template needs to be tailored to your site: see the Administration section.
Then, visitors can start registering as front end users.

  
  Registration Process
The default procedure is the following:
•	Just below the login box, the visitor is presented a link to a registration form or, if the user is already logged in, a link to a profile editing form;
•	The first time visitor completes the registration form and clicks on the submit button. He is presented with a preview form in order to verify the registration information before creating the account. Hitting 'Cancel', he may go back to the form to make any desired correction; he will have to re-enter his password. Upon submitting the verified registration information, the new user is informed that an email is being sent to him to complete the registration process and his account is assigned to a user group preventing any special access;
•	An email is sent to the registering visitor. The message contains two links. One link allows the visitor to confirm the registration and the other link cancels the registration (in case somebody has used his email address);
•	Clicking on either link brings the visitor to a message page displayed in his browser. If he has confirmed his registration, he is presented with a login box and may log into the site. When the user confirms his registration, his account is assigned a user group allowing him to access whatever pages are targeted at registered users;
•	When logged in, the user may edit his account information or delete his account;
•	Upon each event (registration, confirmation, cancellation, update, or deletion), an email may be sent to the user to confirm the action. An email notification may also be sent to the administrator of the site. The email to the user may be in HTML format, if desired (the email will always include a plain text version).
Invitation
The extension may also be configured so that a front end user can create an account for another person and send an invitation to register. The invitation email allows the invited person to accept or decline the invitation. If the invited person accepts the invitation, she will be presented a form and requested to enter twice the same password. Consider to set enableAutoLoginOnInviteConfirmation to 1 if you want to enable this quick process. Otherwise you must send the invited person his password in the registration email or by other means.
Administrative Review
The extension may also be configured so that an email is sent to the site administrator when a visitor confirms his(her) registration.
In such case, the visitor is informed by email that his(her) registration needs to be reviewed and accepted by the site administration before he(she) may login.
The email sent to the site administrator contains all the information provided by the visitor (except the password). It also contains two links allowing the site administrator to accept or refuse the registration.
When the site administrator accepts or refuses the registration, an email is sent to inform the visitor of the decision of the site administration. The visitor may login only after the registration has been accepted by the site administrator.
The administrative review is bypassed when the registration was made by invitation.
