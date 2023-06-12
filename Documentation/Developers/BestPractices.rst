.. include:: /Includes.rst.txt

.. _registering:

Best Practices
==============

.. rst-class:: bignums-xxl

#. manage ids in Typoscript Constants

   When dealing with configurations in TypoScript you should make sure that all relations and configurations are managed in typoscript constants, ideally in your root-sys_template record on your starting page.

   You can then implement an eventlistener in your theme for the :ref:`FinalContentEvent<FinalContentEvent>`. In here you can then easily modify the respecting page and uid for your theme, or other configuration options from your WizardFlexform. Here is an example

   .. literalinclude:: SysTemplateListener.php
      :language: php
      :emphasize-lines: 12,16,18,19,21,26-29,30-33,38,39
      :linenos:

   Don't forget to register your event listener in your :lit:`Services.yaml` with the tag :lit:`event.listener`

   In line 12 we check first if the running process is actually running for our theme/template, because it will be called for other themes as well. Alternatively we could check the extension key as well with :php:`$event->getExtensionKey()==='template'` in our example.

   We check in line 12 as well if we're running the table we want to work on, which is :lit:`sys_template` in our case

   In line 16 we check if we are on the root record. If you want to run it on all sys_template records you will have to check if certain keys are set or not.

   In line 18 we use a helper function to parse the content of our constants into an array, with the dot-notation: :typoscript:`foo.bar.value = 1` becomes :php:`$constants['foo.']['bar.']['value'] = 1`

   .. attention::

      this helper function will do :typoscript:`@includes`, but the information that a certain part was included will be lost. the included content will be written back as plain typoscript later on.

   In line 19 we use a helper function to make the flex information from the task record smaller into a simple key=>value array. If you use tabs or komplex structures in your flexform, you better use the full array.

   Line 21 is an example how we overwrite a setting using the value from the theme provided flexform

   Lines 26 - 29 is an example where we translate a possible page-id list (5,25,256) into the new page-ids, by giving the method :code:`translateIDlist()` the tablename and the original list. Get more information about these helper methods in the :ref:`create process<creatorProcess>` reference.

   In lines 30 - 33 show if we are certain that it is not a list, but a single id, then we can use the simpler :code:`getTranslateUid()` method. Get more information about these helper methods in the :ref:`create process<creatorProcess>` reference.

   In line 38 we translate the constants array back into its textual typoscript representation.

   And finally in line 39 we give the modified record back to the event.


