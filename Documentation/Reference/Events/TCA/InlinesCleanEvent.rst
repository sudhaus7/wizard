.. include:: /Includes.rst.txt

.. _InlinesCleanEvent:

Inlines\\CleanEvent
===================


This event runs during the TCA `clean` phase, per inline record. This event does not have a `pre` or `post` phase, as this event only fires if inline records are discovered that have not been discovered before. It has no special `final` phase as well, as those records will be treated along all other records during the `final` phase.

This Event implements :ref:`WizardEventWriteableRecordInterface<WizardEventWriteableRecordInterface>`



.. php:namespace:: SUDHAUS7\Sudhaus7Wizard\Events\Inlines

.. php:class:: CleanEvent


