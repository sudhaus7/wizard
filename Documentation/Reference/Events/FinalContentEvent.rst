.. include:: /Includes.rst.txt

.. _FinalContentEvent:

FinalContentEvent
=================

This runs for every record during the final phase, just before the TCA final phase

This Event implements :ref:`WizardEventWriteableRecordInterface<WizardEventWriteableRecordInterface>`

.. php:namespace:: SUDHAUS7\Sudhaus7Wizard\Events

.. php:class:: FinalContentEvent

   .. php:method:: getTable()

      :returns: returns the tablename for this record
      :returntype: string
