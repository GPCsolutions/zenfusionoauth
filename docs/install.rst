Installation guide
==================


Prerequisites
-------------

- Dolibarr ≥ 3.2
- PHP ≥ 5.3
- PHP extensions
    * CURL
    * JSON


Installation
------------


Recommended: "custom" directory
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Make sure the custom directory is enabled [#custom]_.

Unzip the archive in :file:`htdocs/custom` directory.


Legacy: "htdocs" directory
~~~~~~~~~~~~~~~~~~~~~~~~~~

Unzip archive in :file:`htdocs/` directory.

.. note::

    Make sure that your webserver's user has read and directory traversal permissions on the folder and it's content.

.. rubric:: Footnotes

.. [#custom] Edit the file :file:`htdocs/conf/conf.php` and uncomment the lines :samp:`$dolibarr_main_url_root_alt`
    and :samp:`$dolibarr_main_document_root_alt`.
