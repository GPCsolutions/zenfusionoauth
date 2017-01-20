.. _Google Developers Console: https://console.developers.google.com/project


Administrator's guide
=====================

.. note::

    This module is designed to authenticate your request against Google services. An Internet access is required.

    You can't use this module if it is not fully configured.


Activation
----------

You need to have admin privileges to activate the module.

Navigate to :menuselection:`Configuration --> Modules --> Interfaces --> ZenFusion OAuth` and switch it to
:guilabel:`ON`

.. todo:: Add screenshot


Setup
-----

You must register Dolibarr in the `Google Developers Console`_ to allow it to communicate with Google.


Google Cloud Console
~~~~~~~~~~~~~~~~~~~~


Google API Configuration
++++++++++++++++++++++++

Sign in with your Google account.

.. todo:: Add screenshot

Create a new project with the button :guilabel:`Create project`.

Choose a :guilabel:`Project Name` (for example Dolibarr).

Eventually accept the terms of service.

Click :guilabel:`Create`"`

Wait until the project opens automatically.

.. todo:: Add screenshot

Use :guilabel:`Enable an API` from the dashboard or the  :menuselection:`API & AUTH --> APIs`"` menu.

Enable the required APIs for your modules (:guilabel:`Status ON`).

==================  ===================
Module              APIs
==================  ===================
ZenFusion Contacts  - Contacts API
ZenFusion Drive     - Drive API
                    - Google Picker API
==================  ===================


Client ID
+++++++++

Go to :menuselection:`APIs & AUTH --> Credentials --> OAuth`.

Click :guilabel:`Create new client ID`.

.. todo:: Add screenshot

Use the following parameters:

:Application type: Web application
:Authorized Javascript origins: Dolibarr's adress
:Authorized redirect URI: Copy and paste URL using the :guilabel:`Copy to clipboard` button (:guilabel:`Configuration` tab from Dolibarr's ZenFusion OAuth module).

.. todo:: Add screenshot


Configuration file
++++++++++++++++++

.. todo:: Add screeshot

Download the file using the :guilabel:`Download JSON` button from the new :guilabel:`Client ID` and upload it using the form (:guilabel:`Setup` tab from Dolibarr's ZenFusion OAuth module).


**Congratulations!**

You just enabled Dolibarr communicating with Google.

.. warning::
    For security reasons, destroy the downloaded file after use.
    Or at least make sure it's stored in a safe way.


Permissions
-----------

Module provides a user permission. This allows or denies access to all functions.

It is only enabled for admin by default and should be enabled for each desired user or group.

You can find it in the user card :guilabel:`User permissions` tab or group card :guilabel:`Group permissions` tab.


User setup
----------

See :doc:`user`
