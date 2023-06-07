.. include:: /Includes.rst.txt

.. _interfaces:

Interfaces
==========

.. php:namespace::   SUDHAUS7\Sudhaus7Wizard\Interfaces


.. php:interface::   WizardEventInterface

   WizardEventInterface

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


.. php:interface::   WizardProcessInterface

   This interface musst be implemented in a class in the templates namespace. It provides information for the wizards clone task about the template


   .. php:method:: getWizardConfig()

      Returns an instance of the Templates Wizard config. this is separated in order for the template to be able to manage its configs in any manner it likes

      :returntype: WizardTemplateConfigInterface

   .. php:method:: checkWizardConfig(array $createRecord)

      this is called during the early stages of the run of the cloning process. This gives the template the chance to stop the process before it even starts, in case the template needs additional parameters other than the createRecords :guilabel:`ready` status

      :param array $createRecord: the create record in the :guilabel:`ready` state
      :returntype: bool


   .. php:method:: getTemplateBackendUser()

      this should return the concrete database record of the template user.

      :returntype: array


   .. php:method:: getTemplateBackendUserGroup()

      this should return the concrete database record of the template group.

      :returntype: array

   .. php:method:: getMediaBaseDir()

      the subdirectory inside fileadmin where in the new filemount will be created and where the files used in the template are cloned into

      :returntype: string

   .. php:method:: finalize()

      this method will be called at the very end after a Site has been cloned and created. This is the chance for the template to finish up this task, like sending an email, or updating a ticket.

      :param CreateProcess $pObj: the the running CreateProcess
      :returntype: void

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
