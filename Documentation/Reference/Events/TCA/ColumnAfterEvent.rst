.. include:: /Includes.rst.txt

.. _TCAColumnAfterEvent:

TCA\\Column\\AfterEvent
=======================


This event runs during the TCA `post` phase, per column

This Event implements :ref:`WizardEventWriteableRecordInterface<WizardEventWriteableRecordInterface>`

.. php:namespace:: SUDHAUS7\Sudhaus7Wizard\Events\TCA\Column

.. php:class:: AfterEvent


   .. php:method:: getColumn()

      :returns: the name of the column in the table and TCA
      :returntype: string

   .. php:method:: getColumnConfig()

      :returns: the TCA config array for this columnt
      :returntype: array

   .. php:method:: getParameters()

      :returns: an array with additional parameters like table, olduid, oldpid
      :returntype: array

