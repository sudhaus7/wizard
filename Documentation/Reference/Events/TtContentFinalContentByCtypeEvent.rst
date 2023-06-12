.. include:: /Includes.rst.txt

.. _FinalContentByCtypeEvent:

TtContent\\FinalContentByCtypeEvent
==================================

This Event is called during the clean phase for each record, and runs just before the TCA clean phase

This Event implements :ref:`WizardEventWriteableRecordInterface<WizardEventWriteableRecordInterface>`

.. php:namespace:: SUDHAUS7\Sudhaus7Wizard\Events\TtContent

.. php:class:: FinalContentByCtypeEvent

   additional methods:

   .. php:method:: getCtype()

      :returns: the :php:`CType` of this record
      :returntype: string

   .. php:method:: getListType()

      :returns: the :php:`list_type` of this record, if available
      :returntype: null|string

