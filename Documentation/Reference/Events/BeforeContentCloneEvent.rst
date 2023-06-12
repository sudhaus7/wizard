.. include:: /Includes.rst.txt

.. _BeforeContentCloneEvent:

BeforeContentCloneEvent
=======================

This Event is called during the clone process before a record is written to the database

This Event implements :ref:`WizardEventWriteableRecordInterface<WizardEventWriteableRecordInterface>`

-- tip::

   `getRecord()` and `setRecord()` deal with the record before it is inserted, but the pid has already been translated. If there is a t3_origuid field available in this table, the source-uid will have been written to this field. This event runs *before* the PRE phase of the TCA Events.

.. php:namespace:: SUDHAUS7\Sudhaus7Wizard\Events

.. php:class:: BeforeContentCloneEvent

   Additional methods:

   .. php:method:: getOlduid()

      :returns: the original record uid
      :returntype: int

   .. php:method:: getOldpid()

      :returns: the original page uid
      :returntype: int
