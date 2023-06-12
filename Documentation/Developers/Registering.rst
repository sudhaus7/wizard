.. include:: /Includes.rst.txt

.. _registering:

Registering a theme or template
===============================

.. rst-class:: bignums-xxl

#. Create a class implementing :ref:`WizardTemplateConfigInterface<WizardTemplateConfigInterface>`

   this class will read your settings and configurations to pass on to the creating process. Most of these options will be used as configuration options and defaults in the :ref:`task record<taskrecord>`, for example something like this:

   .. literalinclude:: WizardConfig.php
      :language: php
      :linenos:

   .. tip::

      the value returned :code:`getSourcePid()` is usually overwritten by the task configuration. This should be your standard site you want to clone

   .. tip::

      ideally these methods have configurable values, like from a :ref:`ext_conf_template.txt<core:extension-configuration>` file or similar means.

   .. tip::

      you should prefer :code:`getFlexinfoFile()` over :code:`getAddFields()` to add your own configuration options

#. Create a class implementing :ref:`WizardProcessInterface<WizardProcessInterface>`

   this class is the main connector to the :ref:`create process<creatorProcess>` when cloning the site.

   .. literalinclude:: WizardProcess.php
      :language: php
      :linenos:

   In line 12 an object of the class implementing the :ref:`WizardTemplateConfigInterface<WizardTemplateConfigInterface>` needs to be returned. We defined that class in the previous step. This is an extra step so you can implement variations of your theme or other means to generate this configuration

   In line 15 the :code:`checkWizardConfig()` can be used to prevent creation of a site because of other reasons (ticket missing, other business logic). This is the last line of defense for having the create process running this task

   :code:`getTemplateBackendUser()` and :code:`getTemplateBackendUserGroup()` return the corresponding record for the template users. These can as well be configured dynamically for example by using a ref:`ext_conf_template.txt<core:extension-configuration>` file.

   :code:`getMediaBaseDir()` returns a directory *INSIDE* fileadmin, where in the new directory for the new sites files will be located.

   :code:`finalize()` this is the last chance to do anything. The site has been created at this point and everything is finished. This is the last method called in the whole create process.

   .. tip::

      Both interfaces can of course be implemented by a single class, :code:`getWizardConfig()` would then need to return :code:`$this` of course.


#. Registering the theme/sitepackage

   Now you have to register your process class with the wizard, by adding this line to your themes :lit:`ext_tables.php` or :lit:`ext_localconf.php`:

   .. code-block:php::

      use SUDHAUS7\Sudhaus7Template\Wizard\WizardProcess;
      use SUDHAUS7\Sudhaus7Wizard\Tools;
      use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

      if (ExtensionManagementUtility::isLoaded('sudhaus7_wizard')) {
          Tools::registerWizardProcess(WizardProcess::class);
      }

   the :php:`WizardProcess` class is of course your class you implemented in the previous step

   From this point on your theme will be available in the Themes drop down inside a :ref:`task record<taskrecord>` as an option, and you can start to clone sites implementing this theme.


