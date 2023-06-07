.. include:: /Includes.rst.txt

.. _workflow:

Workflow
========

The "*sudhaus7_wizard*" works in two steps:

Creating the order record
-------------------------

the :ref:`Sudhaus7 Wizard Task Record <taskrecord>` is used to create an order for a new site. Once a record is configured and has the manually set status :guilabel:`ready` it will be picked up by the:

Running the command-line tool
-----------------------------

Ideally this runs as a cronjob process. With the command

.. code-block:: bash

   ./vendor/bin/typo3 sudhaus7:wizard next

the next available :ref:`Sudhaus7 Wizard Task Record <taskrecord>` with the status :guilabel:`ready`. The tool will then switch the status to :guilabel:`processing` and the record can not be edited anymore. Only one record will be worked on during one run.

During the run of the wizard task the process will create an internal mapping of all records created between the original uid and the new uid. This mapping can be accessed by site-packages, theme-extensions and other extensions in the form of :ref:`PSR 14 Events <core:extension-development-events>`.

The following phases will be visited during the cloning run for a site:

.. graphviz::

   graph {

      initialize [
         shape=box;
         width=4;
         label=<<B>Initialization</B><BR/>Reading the configuration from the task<BR/>and the theme/template>;
      ]
      createFilemount [
         shape=box;
         width=4;
         label=<<B>Create the new filemount</B><BR/>Event CreateFileMountEvent (only if filemount does not exist)>;
      ]
      createGroup [
         shape=box;
         width=4;
         label=<<B>create the new backend-user group</B><BR/>Event CreateBackendUserGroupEvent (only if group does not exist)>;
      ]
      createUser [
         shape=box;
         width=4;
         label=<<B>create the new backend-user</B><BR/>Event BeforeUserCreationUCDefaultsEvent (only if group does not exist)<BR/>Event CreateBackendUserEvent (only if group does not exist)>;
      ]
      buildTree [
         shape=box;
         width=4;
         label=<<B>Building the page tree</B><BR/>in memory>;
      ]
      cloneTree [
         shape=box;
         width=4;
         label=<<B>Cloning the page tree</B><BR/>Event BeforeClonedTreeInsertEvent (for each tree-node/page)<BR/>Event AfterClonedTreeInsertEvent (for each tree-node/page)>;
      ]
      cloneContent [
         shape=box;
         width=4;
         label=<<B>Start cloning content</B><BR/>Event ModifyCloneContentSkipTableEvent>;
      ]
      cloneContentPerTable [
         shape=box;
         width=4;
         label=<<B>Cloning content per table</B><BR/>all tables in TCA will be checked, except those defined during the skiptable event<BR/>Event BeforeContentCloneEvent (per table per row)>;
      ]
      runTCApre [
         shape=box;
         width=4;
         label=<<B>PRE phase of the TCA events</B><BR/>the pre event does not hold a lot of mapping entropy yet.<BR/>It is mainly used to prepare additional data or pre-emptive cleanup if needed<BR/>In the pre phase the creation of a record can be prevented>;
      ]
      runTCApre2 [
         shape=box;
         width=4;
         label=<<B>PRE phase of the TCA events</B>>;
      ]

      runTCApreColumn [
         shape=box;
         width=4;
         label=<Event Column\BeforeEvent<BR/>Per TCA column and table for the old record<BR/>use this if you need to target every column>;
      ]

      runTCApreColumnType [
         shape=box;
         width=4;
         label=<Event ColumnType\BeforeEvent<BR/>Per TCA column and table for the old record and the column type<BR/>use this if you need to target specific column types>;
      ]

      addContentMap [
         shape=box;
         width=4;
         label=<<B>mapping of old UID to new UID per table</B>>;
      ]

      addCleanupInline [
         shape=box;
         width=4;
         label=<<B>TODO List for inline cleanups and relations will be created</B>>;
      ]

      runTCApost [
         shape=box;
         width=4;
         label=<<B>POST phase of the TCA events</B><BR/>the post phase is after inserting the new record.<BR/>Some mapping information is available here,<BR/>but the cloning process is still ongoing at this phase.<BR/>This phase can be used to fix stuff for individual records if needed>;
      ]

      runTCApost2 [
         shape=box;
         width=4;
         label=<<B>POST phase of the TCA events</B>>;
      ]

      runTCApostColumn [
         shape=box;
         width=4;
         label=<Event Column\AfterEvent<BR/>Per TCA column and table for the old record<BR/>use this if you need to target every column>;
      ]

      runTCApostColumnType [
         shape=box;
         width=4;
         label=<Event ColumnType\AfterEvent<BR/>Per TCA column and table for the old record and the column type<BR/>use this if you need to target specific column types>;
      ]

      afterContentCloneEvent [
         shape=box;
         width=4;
         label=<<B>After content has been cloned and TCA Events PRE/POST ran</B><BR/>Event AfterContentCloneEvent>;
      ]
      cloneInlines [
         shape=box;
         width=4;
         label=<<B>Inline Content will be cloned</B><BR/>Event ModifyCloneInlinesSkipTablesEvent>;
      ]
      cleanInlineRow [
         shape=box;
         width=4;
         label=<<B>during this phase missing records will be created</B><BR/>Event Inlines\CleanEvent (per table and NEW record) >;
      ]

      runTCAclean [
         shape=box;
         width=4;
         label=<<B>Clean phase of the TCA events</B><BR/>the clean phase might create additional mapping information,<BR/>especially from inline records and other references>;
      ]

      runTCAcleanColumn [
         shape=box;
         width=4;
         label=<Event Column\CleanEvent<BR/>Per TCA column and table for the new record<BR/>use this if you need to target every column>;
      ]
      runTCAclean2 [
         shape=box;
         width=4;
         label=<<B>POST phase of the TCA events</B>>;
      ]

      runTCAcleanColumn2 [
         shape=box;
         width=4;
         label=<Event Column\CleanEvent>;
      ]

      runTCAnewInlineRecord [
         shape=box;
         width=4;
         label=<<B>In case a new relation has been detected</B><BR/>Event CleanContentEvent (with the foreign table as table and the new record)<BR/>In this case an additional runTCA Phase clean will run for this record>;
      ]
      runTCAnewInlineRecord2 [
         shape=box;
         width=4;
         label=<<B>In case a new relation has been detected</B>>;
      ]


      runTCAnewInlineRecordKnown [
         shape=box;
         width=4;
         label=<<B>In case a known relation has been detected</B><BR/>Event CleanContentEvent (with the foreign table as table and the new record)<BR/>In this case an additional runTCA Phase clean will run for this record>;
      ]

      runTCAnewInlineRecordNew [
         shape=box;
         width=4;
         label=<<B>In case a known relation has been detected</B><BR/>Event BeforeContentCloneEvent (with the foreign table as table and the old record)<BR/>In this case an additional runTCA Phase pre and post will run for this record<BR/>Event AfterContentCloneEvent (for the new record)>;
      ]

      runTCAcleanColumnType [
         shape=box;
         width=4;
         label=<Event ColumnType\CleanEvent<BR/>Per TCA column and table for the new record and the column type<BR/>use this if you need to target specific column types>;
      ]

      cleanPhase [
         shape=plaintext;
         width=4;
         label=<Final Phase - at this point all mapping information for all pages,<BR/>content, records and relations should exist<BR/>This is the phase you usually want to hook into for normal sites<BR/>and tasks>;
         style="";
      ]

      cleanPages [
         shape=box;
         width=4;
         label=<<B>final pages</B><BR/>Event FinalContentEvent (for the current new page)>;
      ]
      runTCAfinal [
         shape=box;
         width=4;
         label=<<B>TCA Events</B><BR/>Event FinalContentEvent (for the current new page)>;
      ]
      runTCAfinalColumn [
         shape=box;
         width=4;
         label=<Event Column\FinalEvent (for the current new record an)<BR/>use this if you need to target every column>;
      ]
      runTCAfinalColumnType [
         shape=box;
         width=4;
         label=<Event ColumnType\FinalEvent (for the current new record an)<BR/>use this if you need to target a certain columntype>;
      ]
      finalContent [
         shape=box;
         width=4;
         label=<<B>final Content</B><BR/>For each other table in the TCA<BR/>Event ModifyCleanContentSkipListEvent<BR/>Event FinalContentByCtypeEvent (if you need to target certain CTypes or list_types)<BR/>Event FinalContentEvent>;
      ]
      pageSort [
         shape=box;
         width=4;
         label=<<B>Site sorting in</B><BR/>the new Site is sorted into the Tree-list,<BR/>ascending by title inside its siblings.<BR/>The sorting of the tree below the site stays identical to its source>;
      ]
      finalGroup [
         shape=box;
         width=4;
         label=<<B>Final Group update</B><BR/>updates the group with the translated db mountpoints>;
      ]

      finalUser [
         shape=box;
         width=4;
         label=<<B>Final User update</B><BR/>updates the user with the translated db mountpoints>;
      ]

      finalYaml [
         shape=box;
         width=4;
         label=<<B>Create the site.yaml in /config/sites</B><BR/>Event GenerateSiteIdentifierEvent (default: derived by config info)<BR/>Event BeforeSiteConfigWriteEvent (last chance to change the contents of the site-config)>;
      ]

      done [
         shape=box;
         width=4;
         label=<<B>done</B><BR/>the finalize method in your template's WizardProcess will be called>;
      ]

      initialize -- createFilemount -- createGroup -- createUser -- buildTree -- cloneTree -- cloneContent -- cloneContentPerTable -- runTCApre -- runTCApreColumn -- runTCApreColumnType -- addContentMap -- addCleanupInline -- runTCApost -- runTCApostColumn -- runTCApostColumnType -- afterContentCloneEvent -- cloneInlines -- cleanInlineRow -- runTCAclean -- runTCAcleanColumn -- runTCAnewInlineRecord --  runTCAnewInlineRecordKnown -- runTCAclean2 -- runTCAcleanColumn2 -- runTCAnewInlineRecord2 -- runTCAcleanColumnType -- runTCAnewInlineRecordNew -- runTCApre2 -- runTCApost2 -- cleanPhase -- cleanPages -- runTCAfinal -- runTCAfinalColumn -- runTCAfinalColumnType -- finalContent -- pageSort -- finalGroup -- finalUser -- finalYaml -- done;







   }
