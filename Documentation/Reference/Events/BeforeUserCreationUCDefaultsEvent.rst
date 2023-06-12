.. include:: /Includes.rst.txt

.. _BeforeUserCreationUCDefaultsEvent:

BeforeUserCreationUCDefaultsEvent
=================================

If a user gets created, the uc field can be edited here

.. php:namespace:: SUDHAUS7\Sudhaus7Wizard\Events

.. php:class:: BeforeUserCreationUCDefaultsEvent

   .. php:method:: getUc()

      :returns: the uc array of the template user
      :returntype: array

   .. php:method:: setUc($uc)

      :param array $uc: the uc array to be written
      :returntype: void
