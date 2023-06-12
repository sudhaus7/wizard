.. include:: /Includes.rst.txt

.. _BeforeSiteConfigWriteEvent:

BeforeSiteConfigWriteEvent
==========================

This event is called before the site config is written.

.. tip::

   The new site's website title has already been written, either with the Shortname or the ProjectName from the :ref:`TaskRecord<taskrecord>`
   Additionally if you are using an `errorHandling` with the `errorContentSource` option, a :lit:`t3://` reference has been translated already

.. php:namespace:: SUDHAUS7\Sudhaus7Wizard\Events

.. php:class:: BeforeSiteConfigWriteEvent

   .. php:method:: getSiteconfig()

      :returns: the site-config as array to be written into the site-config yaml file
      :returntype: array

   .. php:method:: setSiteconfig($siteconfig)

      :param array $siteconfig: the site-config array
      :returntype: void
