.. include:: /Includes.rst.txt

.. _creatorTask:
.. _creatorProcess:

The CreateProcess class
=======================

This class is the main part of the :ref:`cloning process <workflow>` and is available in all :ref:`events <events>` via the `$event->getCreateProcess()` method. It is instantiated and configured by the CLI tool during the clone process, if a :ref:`task <taskrecord>` is available

In this class are several utility methods available to access the mapping between old UID and new UID in the different tables, as well as other methods.

-- tip::

   the whole process is very event-driven and you should implement the interactions between the cloning process and your template in events as much as possible

.. php:namespace::   SUDHAUS7\Sudhaus7Wizard

.. php:class:: CreateProcess

   .. php:method:: log($message,$info = 'DEBUG', $section, $context)

      use this method to log some information, which will then be send to the LoggerInterface instance in the CreateProcess.

      :param string $message: the message to log
      :param string $info: DEBUG for debug messages (default) INFO for general messages
      :param ?string $section: Additional grouping for the log message. Maybe the classname of the event
      :param array $context: more information
      :returnvalue: void

   .. php:method:: addContentMap($table, $old, $new)

      adds an old -> new mapping to the contentmap for the given table. Normaly only internaly used.

      :param string $table: the tablename
      :param int $old: the old uid
      :param int $new: the new uid
      :returnvalue: void

   .. php:method:: addCleanupInline($table, $uid)

      adds a record to the cleanup task list. only internaly used

      :param string $table: the tablename
      :param int $uid: the uid
      :returnvalue: void

   .. php:method:: getTranslateUid($table, $uid)

      translates an old uid to the new uid for the given table. This does not create records, it only looks up the mapping filled with  addContentMap.

      :param string $table: the tablename
      :param int|string $uid: the old uid
      :returns: the new uid
      :returnvalue: int
