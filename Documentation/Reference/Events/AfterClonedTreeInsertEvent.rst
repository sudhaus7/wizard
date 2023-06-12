.. include:: /Includes.rst.txt

.. _AfterClonedTreeInsertEvent:

AfterClonedTreeInsertEvent
==========================


This Event is called per new page inserted during the tree cloning

This Event is readonly

.. php:namespace:: SUDHAUS7\Sudhaus7Wizard\Events

.. php:class:: AfterClonedTreeInsertEvent


   .. php:method:: getOldid()

      :returns: the original page id
      :returntype: string|int

   .. php:method:: getRecord()
      :returns: the page-record of the new page
      :returntype: array
