.. include:: /Includes.rst.txt

.. _WizardTemplateConfigInterface:

WizardTemplateConfigInterface
=============================

.. php:namespace::   SUDHAUS7\Sudhaus7Wizard\Interfaces

.. php:interface::   WizardTemplateConfigInterface

   This interface musst be implemented in a class in the templates namespace. It provides configurations for the wizards clone task about the template

   .. php:method:: getExtension()

      Returns the extension Name of the template extension for example 'my_template'

      :returntype: string

   .. php:method:: getDescription()

      Returns a description for this template, for example 'Primary school template'

      :returntype: string

   .. php:method:: getSourcePid()

      Returns the DEFAULT page ID of the page tree to clone. This can be changed inside the :ref:`task record <taskrecord>`.

      :returntype: int|string

   .. php:method:: getFlexinfoFile()

      Returns the Path to a Wizard flexform File for additional Template specific Config options for example: :file:`EXT:my_template/Flexforms/Wizard.xml`

      :returntype: string

   .. php:method:: getAddFields()

      in case you decide to EXTEND the :ref:`task record <taskrecord>` with your own fields, then this function can be used to add those fields to the TCA of the taskrecord WHEN your template is used.

      this method does *NOT* support `before:` and `after:` annotations

      this function must return either an empty string or a comma separated list of field-names

      :returntype: string
