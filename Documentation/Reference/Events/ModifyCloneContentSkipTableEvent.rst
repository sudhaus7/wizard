.. include:: /Includes.rst.txt

.. _ModifyCloneContentSkipTableEvent:

ModifyCloneContentSkipTableEvent
================================

In this event you can modify the list of tables that should be *ignored* during the clone phase

Tables set by default:

.. code-block:: php

   [
      'pages',
      'sys_domain',
      'sys_file_reference',
      'be_users',
      'be_groups',
      'tx_sudhaus7wizard_domain_model_creator',
      'sys_file',
      'sys_action',
   ];


.. php:namespace:: SUDHAUS7\Sudhaus7Wizard\Events

.. php:class:: ModifyCloneContentSkipTableEvent

   .. php:method:: getSkipList()

      :returns: array with tablenames
      :returntype: array

   .. php:method:: setSkipList($skipList)

      :param array $skipList: the modified skip list
      :returntype: void

