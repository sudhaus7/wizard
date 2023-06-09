.. include:: /Includes.rst.txt

.. _WizardProcessInterface:

WizardProcessInterface
======================

.. php:namespace::   SUDHAUS7\Sudhaus7Wizard\Interfaces

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
