.. include:: /Includes.rst.txt

.. _utilities:

Extra Utilities
===============

There are additional utility functions that can be used when writing events

.. php:namespace::   SUDHAUS7\Sudhaus7Wizard\Services

.. php:class:: TyposcriptService

   .. php:method:: parse($typoScript)

      used as static method: :code:`\SUDHAUS7\Sudhaus7Wizard\Services\TyposcriptService::parse()` to parse TypoScript into an array. Basically a wrapper function to `TYPO3\CMS\Core\TypoScript\Parser\TypoScriptParser`

      :param string $typoScript: the typoscript text
      :returns: parsed array structure (with 'key.' notation)
      :returnValue: array

   .. php:method:: fold($typoScriptArray)

      used as static method: :code:`\SUDHAUS7\Sudhaus7Wizard\Services\TyposcriptService::fold()`. This method will create a typoscript string representation for a typoscript array.
      *Attention* this method will not reproduce the original typoscript parsed by :code:`TYPO3\CMS\Core\TypoScript\Parser\TypoScriptParser` as information has been lost during the parsing. Scopes, comments and includes will be lost, as this information is not persisted in the array itself. This method is ideally being used on constants or very simple setup fields.

      This method is usually used to de-compile an array during an clone-event.

      :param string $typoScriptArray: the typoscript config as array (key. notation)
      :returns: de-compiled typoscript-text representation of that array
      :returnValue: string

.. php:class:: TyposcriptService

   .. php:method:: flatten($data)

      This method will take a parsed flexform and flatten the created data. As tabs/sections in the flexform are not persisted, depending on the flexform data might be lost!

      :param array $data: the flexform array
      :returns: flattened array as simple 'key -> value'
      :returnValue: array

   .. php:method:: blowup($data)

      This method tries to recreate the flexform array from a simple `key -> value` array. It does not do any check against the XML structure!

      *not used in the moment*

      :param array $data: simple key -> value array
      :returns: array in the sDEF/lDEF/vDEF format
      :returnValue: array

.. php:namespace::   SUDHAUS7\Sudhaus7Wizard

.. php:class:: Tools

   .. :php:method:: resolveFieldConfigurationAndRespectColumnsOverrides($table, $field, $record)

      if you are unsure if there are overrides for a field config, then you can use this method to get a TCA config array for that field with the overrides implemented. A typical case would be if you have a field of the type :lit:`text` and you are unsure if it has been overriden to have the Richtext Editor enabled, then this function will help you.

      :param string $table: the tablename
      :param string $field: the fieldname in the TCA
      :param array $record: the DB record of the content
      :returns:
      :returntype: array


