Pattern Lab Fluid Engine: TYPO3 CMS export
==========================================

Package which hooks into the [Pattern Lab Fluid Edition](https://github.com/NamelessCoder/patternlab-fluid-edition) to 
write your patterns as proper TYPO3 CMS Fluid template files into an extension folder - which then is more or less
plug and play.

The basics
----------

Adding this package to a Pattern Lab Fluid Edition means that when you generate templates (either by watching files or
manually generating) your files get copied and adjusted so they fit into a TYPO3 CMS extension's `Resources` folder.

The following rules are in place:

* Atoms are copied to `Resources/Private/Partials/Atoms` that you can easily render from any of your templates.
* Molecules are copied to `Resources/Private/Partials/Molecules` - and are also partials.
* Organisms are copied to `Resources/Private/Partials/Organisms` - and are also partials.
* Templates are copied to `Resources/Private/Templates/Default` - which emulates a "DefaultController" to scope templates.
* Pages are copied to `Resources/Private/Templates/Page` - which emulates a "PageController" to scope templates.

Layouts are handled specially and separately. If any of your `Templates` or `Pages` use a Fluid layout, then this is
also extracted, rewritten and copied to `Resources/Private/Layouts/`.

Note here that while Pattern Lab allows you to use a Layout for every pattern down to the Atoms type, the Layout is only
respected in TYPO3 CMS for the `Templates` and `Pages` types. Though they do get copied, they still contain the Layout
reference, and most likely only can be rendered by rendering a specific section. **This means that in order to render
an Atom, Molecule or Organism which in Pattern Lab uses a Layout, in almost all cases you will have to call `f:render`
with both a `section` AND `partial` argument!**. This package does not correct the render statements in this case;
though that may be added as a feature at a later time.


Defining the export target folder
---------------------------------

Once installed, your Pattern Lab Fluid Edition's `config/config.yml` file can be edited to add:

```yml
fluidTYPO3ExtensionExportPath: /path/to/my_extension
``` 

Define the target path pointing to the *root of the extension folder*. The rest, e.g. `Resources/Private`, is implied.

Attempting to generate your templates without this parameter present will trigger an exception asking you to add the
configuration option.


Using the exported TYPO3 CMS templates
--------------------------------------

Once your templates are exported, how you render them will depend largely on your preferred integration method (either
as controller action templates, as `FLUIDTEMPLATE` objects or other) but there are a couple of hints to achieve the
most transparent integration:

1. Generating templates into an extension which only has this single responsibility, carrying template files, means you
   can add the paths for this extension to those of a secondary extension - for example a customer specific extension.
2. Alternatively, generating into an extension which also contains other template files is possible, but should be done
   carefully as to avoid overwriting any manually added partials. Consider a separate set of folders for the manually
   added templates if this is your use case.
3. The exported templates can also be used as content types with very little adaptation: for example, `Fluid Styled
   Content` allows you to override templates using TypoScript. You can use this to your advantage, by letting Pattern
   Lab generate core content type templates for example as the `Template` pattern type.
4. The safest way to integrate the templates will always be to *pre-process all variables that are used in the template
   so that your assigned variables completely match the structure of the JSON dummy data associated with the pattern.*
   This is the method that involves the absolute minimum effort in terms of having to edit the generated templates.


Limitations / future features
-----------------------------

* Extension manifest files are not generated and must be created manually before the extension can be loaded.
* The Pattern Lab JSON data is not copied, but can be manually copied and assigned as dummy data until real data exists.
  Future versions may do this for you by creating dummy TypoScript and correcting references to variables.
* The exporting logic will indiscriminately overwrite any existing files; future versions may be configurable to either
  ask or selectively overwrite only some of the templates.
* Nothing in terms of configuration (TypoScript, global config, etc.) is currently written and must be manually created.
* Although PatternLab supports writing ViewHelpers into the edition's source files, these classes are currently not
  exported - future versions are very likely to begin doing this for you. Until such time, copy the classes into your
  extension of choice and register that extension's ViewHelper classes folder using the `plio` namespace name.


Credits
-------

This work was kindly sponsored by [Busy Noggin](http://busynoggin.com/).
