.. include:: /Includes.rst.txt

.. _WizardEventInterface:

WizardEventInterface
====================

.. php:namespace::   SUDHAUS7\Sudhaus7Wizard\Interfaces

.. php:interface::   WizardEventInterface

   all :ref:`Events <events>` implement this interface

   .. php:method:: getExtensionKey()

      Returns the template's extension key for which the current clone process is running

      :returntype: string

   .. php:method:: getCreateProcess()

      Returns an instance of the CreateProcess the event is called from

      :returntype: SUDHAUS7\\Sudhaus7Wizard\\CreateProcess


   .. php:method:: getTemplateConfig()

      Returns the templates Template Config that had to be implemented for the template to be able to be cloned

      :returntype: SUDHAUS7\\Sudhaus7Wizard\\Interfaces\\WizardProcessInterface
