.. include:: /Includes.rst.txt

.. _ModifyCleanContentSkipListEvent:

ModifyCleanContentSkipListEvent
===============================

In this event you can modify the list of tables that should be *ignored* during the clean phase

Tables set by default:

.. code-block:: php

   [
      'pages',
      'sys_domain',
      'sys_action',
      'sys_file',
      'sys_file_metadata',
      'be_users',
      'be_groups',
      'sys_news',
      'sys_log',
      'sys_registry',
      'sys_history',
      'sys_redirect',
      'sys_filemounts',
      'tx_sudhaus7wizard_domain_model_creator',
   ];


.. php:namespace:: SUDHAUS7\Sudhaus7Wizard\Events

.. php:class:: ModifyCleanContentSkipListEvent

   .. php:method:: getSkipList()

      :returns: array with tablenames
      :returntype: array

   .. php:method:: setSkipList($skipList)

      :param array $skipList: the modified skip list
      :returntype: void
