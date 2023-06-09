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

      translates an old uid to the new uid for the given table. This does not create records, it only looks up the mapping filled with addContentMap.

      This method supports the TYPO3 table prefixed notations as well, in this case the table-parameter will be ignored. For example: 'tt_content_10' will be translated to 'tt_content_55' if the lookup-table for tt_content points from 10 to 55, and 'tt_content_55' will be the returned value.

      If the uid is not found in the lookup tables, the old uid will be returned. This is intended behaviour as it could mean that either the content has not been cloned yet, or the uid references a record outside of the current site (for example a page linked to a page inside another site in the same TYPO3)

      :param string $table: the tablename
      :param int|string $uid: the old uid
      :returns: the new uid
      :returnvalue: int

   .. php:method:: translateIDlist($table,$list)

      This method translates comma separated lists of ids or table-prefixed ids. The table prefixes can be mixed.

      :param string $table: the tablename
      :param string $list: the old idlist, a comma separated string
      :returns: the translated id list, as a comma separated string
      :returnvalue: string

   .. php:method:: translateT3LinkString($link)

      This method translates TYPO3 style URI links, for example created by the RTE. For example :samp:`t3://page?uid=6#212` or :samp:`t3://file?uid=2`, but older notations like `file:2` are supported as well

      :param string $link: the URI string
      :returns: the translated URI
      :returnvalue: string

   .. php:method:: translateTypolinkString($link)

      This method will translate a Typolink created by the link-wizard. For example this :samp:`t3://page?uid=10#20 _blank cssclass "My great link"` will be translated to :samp:`t3://page?uid=25#55 _blank cssclass "My great link"`

      :param string $link: the Typolink string
      :returns: the translated Typolink
      :returnvalue: string
