.. include:: /Includes.rst.txt

.. _TCAColumnTypeFinalEvent:

TCA\\ColumnType\\FinalEvent
===========================

This event runs during the TCA `final` phase, per column type

This Event implements :ref:`WizardEventWriteableRecordInterface<WizardEventWriteableRecordInterface>`

.. tip::

   the final phase is usually your safest bet to interact with the cloning process, as all records should be created by now


.. php:namespace:: SUDHAUS7\Sudhaus7Wizard\Events\TCA\ColumnType

.. php:class:: FinalEvent


   .. php:method:: getColumntype()

      :returns: the TCA type for this column
      :returntype: string

   .. php:method:: getColumn()

      :returns: the name of the column in the table and TCA
      :returntype: string

   .. php:method:: getColumnConfig()

      :returns: the TCA config array for this columnt
      :returntype: array

   .. php:method:: getParameters()

      :returns: an array with additional parameters like table, olduid, oldpid
      :returntype: array

