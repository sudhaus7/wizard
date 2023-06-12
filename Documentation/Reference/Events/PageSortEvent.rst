.. include:: /Includes.rst.txt

.. _PageSortEvent:

PageSortEvent
=============

This Event will be called just before everything is finished and is intended to be used to sort the site-tree in the backend. There is a default SiteSorter Listener available that can be enabled in the extension configuration

.. php:namespace:: SUDHAUS7\Sudhaus7Wizard\Events

.. php:class:: PageSortEvent

   .. php:method:: getRecord()

      :returns: array with new root-page record
      :returntype: array

   .. php:method:: getOldpid()

      :returns: the old pid (pid of the source)
      :returntype: int

