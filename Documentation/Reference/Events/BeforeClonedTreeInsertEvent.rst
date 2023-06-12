.. include:: /Includes.rst.txt

.. _BeforeClonedTreeInsertEvent:

BeforeClonedTreeInsertEvent
===========================

This Event is called during the clone process before a page-record is written to the database

This Event implements :ref:`WizardEventWriteableRecordInterface<WizardEventWriteableRecordInterface>`

-- tip::

   `getRecord()` and `setRecord()` deal with the page record before it is inserted, but the pid has already been translated, if available. As well, user and group ids have been added as owners if the original owner was *NOT* an admin user.

.. php:namespace:: SUDHAUS7\Sudhaus7Wizard\Events

.. php:class:: BeforeClonedTreeInsertEvent

   additional methods:

   .. php:method:: getOldpid()

      :returns: the original page uid
      :returntype: int

