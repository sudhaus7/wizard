.. include:: /Includes.rst.txt

.. _AfterContentCloneEvent:

AfterContentCloneEvent
======================

This Event is called per new page inserted during the tree cloning

This Event implements :ref:`WizardEventWriteableRecordInterface<WizardEventWriteableRecordInterface>`

.. php:namespace:: SUDHAUS7\Sudhaus7Wizard\Events

.. php:class:: AfterContentCloneEvent

   additional Methods:

   .. php:method:: getOlduid()

      :returns: the original record uid
      :returntype: int

   .. php:method:: getOldpid()

      :returns: the original page uid
      :returntype: int

