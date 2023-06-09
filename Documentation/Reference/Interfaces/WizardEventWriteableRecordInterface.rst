.. include:: /Includes.rst.txt

.. _WizardEventWriteableRecordInterface:

WizardEventWriteableRecordInterface
===================================

.. php:namespace::   SUDHAUS7\Sudhaus7Wizard\Interfaces

.. php:interface::   WizardEventWriteableRecordInterface


   Normally the :ref:`events <events>` are readonly unless otherwise stated. Many allow to write the record though. This interface is implemented in those :ref:`events <events>`

   .. php:method:: getTable()

      :returns: the database table name (and TCA key) relating to the record
      :returntype: string

   .. php:method:: getRecord()

      :returns: the current record for the current table
      :returntype: array

   .. php:method:: setRecord($record)

      Writes the record back to the event

      :param array $record: the record
      :returntype: void

   .. php:method:: getTemplateConfig()

      Returns the templates Template Config that had to be implemented for the template to be able to be cloned

      :returntype: SUDHAUS7\\Sudhaus7Wizard\\Interfaces\\WizardProcessInterface

