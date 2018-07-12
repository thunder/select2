# Drupal 8 - Select2
This is a [select2](https://select2.org/) integration for Drupal 8. It provides a render element (for usage in forms) and two field widgets.
One for simple select fields and another for entity reference fields.

When the field widget is used in the entity reference context this module provides several features:

* Autocomplete: The select options will not be rendered in the page and instead fetched by API during typing.
* Autocreate: Like core's entity reference field this widget can create new entities on the fly.


## Installation

Install this module like every other Drupal module. Also it's needed to get the select2 library.
Download it from the [release page](https://github.com/select2/select2/releases) and place it in Drupal's library folder.
