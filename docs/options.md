Here are the list of options that can be given to papercite.

<a name="global_options"></a>

# Global options

*   **Bibtex parser** (papercite >= 0.3.16): you can choose the pear parser (deprecated) or the OSBiB-based parser (default).
*   **Database backend** (papercite >= 0.4.0): The database backend is used to store the result of bibtex parsing in a database, allowing to speed up the processing of shortcodes when the BibTeX file is big.

<a name="local_options"></a>

# Local options

Some of these options can be set at a global level (through preferences) and page/post level. These options are shown <span class="goptions">like this</span>. To set options at the post/page level, use the `papercite_options` field with one line per option in the format:

<pre>    **field_name** = **field_value**
  </pre>

First, some options are necessary to tell which bibtex file should be used:

*   `file`: The default bibtex URL(s) separated by commas (see [what are the valid bibtex URLs](#location)).
*   `timeout`: The default time-out before reloading an external resource

You can modify how publications are displayed using several options:

*   `key_format`: How to format the citing key of the publication
*   `template`: How to format the publication. At a global level, `bibshow_template` and `bibcite_template` are used to distinguish between the templates used respectively for `bibshow` and `bibcite`
*   `format`: Template used to format one BibTeX entry
*   `show_links`: Enable (1, default) or disable (0) use of hyperlinks from `bibcite` entries to the resulting list of publications
*   `highlight`: Highlight name(s) of specific author(s)
*   `ssl_check`: Enable (1) or disable (0, default) the verification of SSL certificates when downloading resources. This can be useful when fetching from a site with a self-signed certificate.

Filtering options

*   The publication type can be fitered using `allow` and `deny`.
*   Publications can be filtered by author using the following syntax: `author=name`, `author=name1|name2|name3`, or `author=name1&name2&name3`. Filtering can match also authors whose name is substring of some other name (e.g. name "Su"). and is case sensitive in names which start with diacritics (e.g. Řehoř, Šimon..)
*   The number of items to be displayed can be limited with the `limit` argument. Its value, if greater than 0, gives the maximum number of items to be displayed

Grouping and order options

*   `group`: How to group publications
*   `group-order`: Group sort order
*   `sort`: How to order publications within groups
*   `order`: Sort order within groups

Each of these options are described next. Finally, the template language used to format entries is described on the [bib2tpl site](http://lmazy.verrech.net/bib2tpl/templates/), with the following modifications:

*   @#**field**@ gives the number of entries in a field (author or editor)
*   Conditions can be more expressive, e.g. `@?**field**=abc@` is true if the **field** is equal to abc (numeric comparisons are possible with > and <)

<a name="formatting"></a>

# Formatting

Some options modify the bibtex processing:

*   `process_titles` can be set to 1 (normal BibTeX behaviour - lowercase everything which is not between braces) and 0 (no processing)

You can modify the style of the citations by using the `format` For example,

<div class="code">[bibtex file=mypub.bib format=ieee template=default-bibtex key_format=number]</div>

The `key_format` controls how a key is associated to a BibTeX entry. From within a entry template, the value of the key is given by `@key@`. The following values are accepted:

*   `numeric` (default) gives a unique increasing number to each entry (1, 2, ...)
*   `cite` uses the bibtex key

The format described how an entry is displayed. The following formats are currently available:

*   `ieee` (default)
*   `apa`
*   `britishmedicaljournal`
*   `chicago`
*   `harvard`
*   `ieee`
*   `mla`
*   `turabian`
*   `plain`

Feel free to contribute new formats, but note that in the future the citation style might be written using the [Citation Style Language (CSL)](http://citationstyles.org/).

Eventually, the `template` option controls which template is used to format the entries. It is based on the `tpl` code. For the moment, the following templates are defined:

*   `default-bibtex` is the default template used for the `bibtex` command
*   `av-bibtex` is another template for the `bibtex` command. It adds support for `abstract` field (toggled like the bibtex entry), explicit `doi:` link and a _Download PDF_ link for `url` field.
*   `default-bibshow` is the default template used for the `bibshow` command

From a pratical point of view, the final format follows [bib2tpl](http://lmazy.verrech.net/bib2tpl/templates/). In order to create a bib2tpl template, templates and formats are merged: in the template, `@#entry@` is replaced by the content of the format file. This This to decouple the formatting of entries with the formatting of the full bibliography.

With respect to the _bib2tpl_ code, some extra variables are defined:

*   `@WP_PLUGIN_URL@` will be replaced by the plugin URL.
*   `@papercite_id@` is a unique id within the page/post
*   `@papercite_title@` is the title sanitized for use in code or urls. It is sanitized with `[sanitize title()](http://codex.wordpress.org/Function_Reference/sanitize_title)`, specifically, HTML and PHP tags are stripped.
*   `@key@` is a the key as formatted with `key_format`.
*   `@pdf@` is the URL to the auto-detected PDF (or to the URL specified in the PDF field).
*   `@positionInList@` gives the position of the entry within the full list
*   `@positionInGroup@` gives the position of the entry within the list for the current group

Here are some more general modifications:

*   More generally, `@#**fieldname**` prints the number of entries in the field name (works with authors, editor and pages)
*   A general if-then-else structure can be encoded by `@?condition@ ... @:@ ... @;@`, where the "else" and "end if" can be used in a short version (no need to specify the condition), and where the condition can be more elaborate than in bib2tpl: you can use the operators `>`, `<`, `=` and `||` with the same semantics as in main programming languages.
*   HTML in fields is preserved by default. All fields are stripped for html code by default, this behaviour can be disabled in the template on a field by field basis by adding the modifier `:html` to the field.
    I.e. an abstract field that contains `This is the <b>greatest</b> book in the world` will show:
    *   `@abstract@` or `@abstract:html@` = This is the **greatest** book in the world
    *   `@abstract:strip@` = This is the greatest book in the world
    *   `@abstract:protect@` = This is the <b>greatest</b> book in the world
    *   `@abstract:sanitize@` = This is the <b>greatest</b> book in the world
    **This should only be used when with fully trusted bibtex sources, as it can be used to embed malicious code on the website**

CSS can be used to customize the display of entries. Again, **do not customize directly papercite files**, this would be overwritten with plugin updates. The best solution is to use a plugin such as [Simple custom CSS](http://wordpress.org/plugins/simple-custom-css/) that allows custom CSS to be written and stored within the WordPress database.

In the default templates, the following classes are used:

*   `papercite` for headers like the `h3` header for grouping key display
*   `papercite_entry` for a single bibtex
*   `papercite_bibtex` for the bibtex code
*   `papercite_pdf` for the PDF link
*   `papercite_doi` for the DOI link

The `highlight` option can be used to highlight specific parts of an author or editor list (see [here](http://www.martinhenze.de/publications/) for an example). You can specify arbitrary regular expressions. Each match of the regular expression will then be highlighted. For example,

<div class="code">[bibtex highlight="M. Mouse"]</div>

will highlight all occurences of _M. Mouse_. Similarly,

<div class="code">[bibtex highlight="M. Mouse|D. Duck"]</div>

will highlight all occurences of _M. Mouse_ or _D. Duck_.<a name="grouping"></a>

# Grouping

You can group the citations using the `group` option with values `none` (by default), `firstauthor`, `entrytype` or any other valid bibtex field. You can order the groups using the `group_order` option which can take values among `asc`, `desc` or `none` (none by default).

Example:

<div class="code">[bibtex file=mypub.bib group=year group_order=desc]</div>

The grouping is defined by the `group` value (`year`, `firstauthor`, `entrytype` or `none`). Group are sorted depending on the `group_order` value:

*   `none`: No order
*   `asc`: Ascendant order
*   `desc`: Descendant order

<a name="sorting"></a>

# Sorting

You can sort the citations using the `sort` option together by a description of the sorting key. Note that the sort

<div class="code">[bibtex file=mypub.bib sort=year order=asc]</div>

Entries (within groups) are sorted depending on the `sort` key (see grouping for the list of possible keys and the possibility to sort by `firstauthoreditor`, which takes the first editor, if no author is given). The ordering is also influenced by the `order` value.

*   `none`: No order
*   `asc`: Ascendant order
*   `desc`: Descendant order

