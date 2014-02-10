# papercite #
**Contributors:** bpiwowar  
**Tags:** formatting, bibtex, bibliography  
**Requires at least:** 3.0  
**Tested up to:** 3.5.2  
**Stable tag:** 0.5.2  
**License:** GPLv2 or later  
**License URI:** http://www.gnu.org/licenses/gpl-2.0.html  

Papercite helps to format bibtex entries to display a bibliography or
cite papers.

## Description ##

**To report bugs or request features, please go to https://github.com/bpiwowar/papercite**

papercite format bibtex entries as HTML so they can be inserted in
WordPress pages and posts. The input data is a bibtex file (either
local or remote) and entries can be formatted by default using various 
predefined styles. Bibtex source file and a link to the publication
are also available from the HTML. 

*Input*: Sources files are BibTex files that can be:

* Stored into a WordPress folder (with multi-site support);
* Stored media
* An URL (e.g., from citeulike.org and bibsonomy.org);
* A custom field when local to a post/page

*Efficiency*:

* *Database backend* to speed up the processing by caching the bibtex entries for big BibTeX files.
* Fast OsBiB-based parser

*Filtering and grouping*:

* Filtering on publication type and authors
* Regular expression filtering on any type
* Publications can be grouped and sorted in various ways

*Output*:

* Access the single bibtex entry source code to enable copy&paste (toggle-enabled visualization)
* Easy output customisation with the use of two templates: one for each entry, one for the list of entries
* Auto-detection of PDF files based on the BibTeX key (or on the PDF entry)
* Citation mode: use references in the text and print the citations at the end of a block, page or post
* Form to let the user dynamically filter the entries

**Documentation can be found from within WordPress plugin list (click on
the documentation link)**. You can see the documentation of the plugin
as installed on my site <a href="http://www.bpiwowar.net/wp-content/plugins/papercite/documentation/index.html">here</a>. 


*Contributors*:

* B. Piwowarski (main developper)
* Jaroslav Vítků: Filtering by author and type; form to filter publications
* Michael Schreifels: auto-bibshow and no processing in post lists options
* Stefan Aiche: group by year option
* Łukasz Radliński: bug fixes & handling polish characters
* Some parts of the code come from bib2html (version 0.9.3) written by Sergio Andreozzi.

## Installation ##

Follow these step or use the plugin installer from WordPress to
install papercite:

1. download the zip file and extract the content of the zip file into a local folder
2. upload the folder papercite into your wp-content/plugins/ directory
3. log in the wordpress administration page and access the Plugins
menu

Then, you should activate papercite, and follow the instructions
given in the *documentation* that you can access through the plugin
list (click on the documentation link).

## Frequently Asked Questions ##

### Where is the documentation? ###

The documentation is now bundled with the plug-in. Go to the plug-in
list page in the WordPress dashboard, and click on the documentation link.

### How can I edit my bibtex files? ###

If your file is local to the blog installation, you have two options:

- via FTP client with text editor
- via Wordpress Admin interface: Manage->Files->Other Files

-- use wp-content/papercite-data/bib/mybibfile.bib as a path

Alternatively, you can maintain your updated biblilography by using systems such as citeulike.org and bibsonomy.org; 
specify the bib file using as a URL (e.g., in citeulike, you should use http://www.citeulike.org/bibtex/user/username)

### How are the entries sorted? ###

Entries are sorted by year by default.

### How can I personalize the HTML rendering? ###

The HTML rendering is isolated in two template files, located in the
subfolders tpl (citation list rendering) and format (entry rendering).

## Screenshots ##

### 1. With the bibshow & bibcite commands ###
![1. With the bibshow & bibcite commands](http://s.wordpress.org/extend/plugins/papercite/screenshot-1.png)

### 2. With the bibtex command ###
![2. With the bibtex command](http://s.wordpress.org/extend/plugins/papercite/screenshot-2.png)

### 3. The bibfilter command ###
![3. The bibfilter command](http://s.wordpress.org/extend/plugins/papercite/screenshot-3.png)


## Changelog ##

### 0.5.2 ###
  * Option to add new files detectors (beyond pdf) - issue #38
  * Option to control title processing (issue #54)
  * Any field can now be used for filters (issue #62)
  * Unparseable year field causes database issues (issue #63)
### 0.5.1 ###
  * The journal field was not parsed with OSBiB (issue #59)
### 0.5.0 ###
  * @conference is now properly handled as @inproceedings (issue #53)
  * Option to limit the number of papers output by bibtex (issue #50)
  * More accents handled (issue #51)
  * Added support for interactive filtering by means of new command (bibfilter). Thanks to Jaroslav Vítků
  * Added support for these additional filtering commands to bibtex command (author and type)
  * Added two new options: auto-bibshow and skip display in post lists (thanks to Michael Schreifels)
  * Fixed quite a few PHP warnings
### 0.4.5 ###
  * Fixed bug #48 (URL as source not working anymore with PHP < 5.4)
### 0.4.4 ###
  * Fix problems with ignored booktitle in books (harvard and ieee styles) - fixes issue #45
  * Fix for newlines by L. Murray (issues #26 and #35)
  * Handles for URL types (issue #41, A. Dyck)
### 0.4.3 ###
  * Maintenance mode plugins support (bug #39)
  * Support for PHP 5.4 (bug #37)
  * Improved accent support - bug #36 (josemmoya)
### 0.4.2 ###
  * Fixes fatal error in PHP 5.4 (bug #37)
  * Improved accent support - bug #36 (josemmoya)
### 0.4.1 ###
  * Post/page BibTeX entries from custom fields
  * Bug fixes and information for database backend

### 0.4.0 ###
  * Optional database backend
  * New style "plain" (thanks to Andrius Velykis)
  * New template "av-bibtex" (thanks to Andrius Velykis)
  * Improved compatibility with the highlight plugin (thanks to Andrius Velykis)

### 0.3.21 ###
  * Fixed issue #26 (newlines stripped from bibtex)
  * Fixed bug #32 (thanks to petrosb)

### 0.3.20 ###
  * OSBib now returns a correct entry type (closes #28)

### 0.3.19 ###
  * Improved parsing for the OSBib parser (closes #29, #27)
  * Handles properly authors initials : closes #31 (thanks to petrosb)

### 0.3.18 ###
  * Enhancement #25 (display the bibliography at the end if no bibshow
  is given)

### 0.3.17 ###
  * Fixed a small bug in the OSBiB parser

### 0.3.16 ###
  * Updated the documentation about how papercite searches for PDFs
  * Added the OSBiB bibtex parser which should be much fadster than
  the previous (pear) one (note that it is not actived by default for
  the moment, so you should go to the plugin preferences page to set
  it as your bibtex parser).

### 0.3.15 ###
  * Corrected "Bootitle" to "booktitle" in all formats (thanks to
  Enkerli@github)
  * Corrected a numbering bug that skipped numbers from 1 to 20 (issue #11)

### 0.3.14 ###
  * The HTML code produced has been cleaned up (valid HTML) [bug 28]

### 0.3.13 ###
  * Enhancement (bug 26): several bibtex files can be given
  * New (optional) bibtex parser handles larger bibtex files (bug 23) 
  * Master thesis is now properly handled (bug 27)

### 0.3.12 ###
  * Fix missing <?php (bug 24 and 25)

### 0.3.11 ###
  * Fix a bug introduced in 0.3.10

### 0.3.10 ###
  * Multiple authors in bibcite (enhancement #2)
  * Ignores @comment entries generated by jabref (bug 22) 
  * Updated the documentation (for the new entry formats, and the
  extensions to bib2tpl)

### 0.3.9 ###
  * Adopted patch given in bug 18 (bibtex source formatting)
  * Fixed function name conflict with Simple Google Analytics plug-in
  (bug 19)

### 0.3.8 ###
  * Fixed bug 14 (group_order not working)
  * Used the proposed enhancement (bug 13) of the function _e2mn
  (parsing a month)
  * Improved bibtex parsing (\&)
  * Improved the entry templates
  * Now uses OSBib for pages formatting

### 0.3.7 ###
  * Improved the OSBib conversion for entry format - now close to perfect

### 0.3.6 ###
  * Bug fix when there are only two authors in the entry
  * Bug fix on nested conditions in templates

### 0.3.5 ###
  * Author are formatted according to the entry template converted
  from OSBib

### 0.3.4 ###
  * Formats are back, translated from OSBib (not perfect, but close to
  the output in version prior to 0.3.0
  * More latex accents handled

### 0.3.3 ###
  * Fixed bug 7: umlaut (still) not handled
  * Fixed bug 12: bug with remote URLs

### 0.3.2 ###
  * Entry format is now in XML to ease the edition

### 0.3.1 ###
  * Fixed bug 7: umlaut not handled
  * Fixed bug 9: template option does nothing for bibshow
  * Bug fix on sort options
  * Sort by author now working

= 0.3.0 = 
  * Complete code overhaul - switched to a new bibtex / template
  system
  * New options to sort & group entries
  * Preference system to set defaults
  * New template based system for entry customisation
  * Multi-site support

= 0.2.14 = 
  * Grouped by year option (patch due to S. Aiche)
  * Now generates an id which does not depend on the key (fix javascript related bugs)

= 0.2.13 = 
  * bug fix: wrong mappings from bibtex fields to arrays have been corrected, link to pdf is now working properly, polish characters
  are almost properly handled (thanks to Łukasz Radliński)

### 0.2.11 ###
  * bug fix: name clash was preventing insertion of medias using the
  WP dialogs

### 0.2.10 ###
  * papercite now looks in the pdf directory at two levels
    (wp-content,  and wp-content/plugins)

### 0.2.9 ###
  * Small bug fix (removes a warning)

### 0.2.8 ###
  * Documentation update
  * New parameter `format`
 
 = 0.2.5 =
  * Fixed a bug with the allow filter

### 0.2.4 ###
  * Small bug fixes (if the file is an URL) and use of WP functions to
  retrieve remote data (useful when you have proxies)

### 0.2.3 ###
 * Fixed a bug introduced in 0.2.2
 * Changed the default folder for data in order to avoid data loss
 when upgrading
 * Finished the encapsulation of all the code, so as to prevent name
 clash with wordpress and/or other plugins

### 0.2.2 ###
 * Removed PHP 5 specific code

### 0.2.1 ###
 * Added the template file

### 0.2 ###
 * Added deny/allow parameters to [bibtex] so the plugin can replace bib2html

### 0.1 ###
 * Adapted the plugin from bib2html 0.9.3
 * Added the bibshow and bibcite commands

## Upgrade Notice ##

### 0.3.17 ###
If you have problems with CPU usage or time to display a page, try this version and choose the OSBiB parser 
in the plugin preferences (in the WP administration page)
### 0.3.14 ###
If you have custom templates, please read. The template generation has been slightly modified - you have to
explicitely markup paragraphs and line breaks, since papercite now
removes any \n or \r in the templates to avoid clashes with WordPress.
### 0.3.13 ###
Bug fixes and new experimental parser (disabled by default) to handle
bibtex files faster (useful for large bibtex files)
### 0.3.11 ###
Should be the last stable release before the 0.4.0 and CoINS support
### 0.3.5 ###
Compatibility with version prior to 0.3.0 is now almost completed. Users who
want to use the new grouping, sorting or template functionalities are advised
to upgrade to this version.
= 0.3.0 = 
Complete overhaul of the bibtex/template system, with a lot of new
options. Please wait until version
0.3.1 if you want to be sure of a bug free papercite (it should not
break WordPress though). Please note that there is
only one citation format (IEEE) and that is only a partial implementation.
= 0.2.9 = 
Removed a PHP warning with bibshow
### 0.2.8 ###
Introduced the `format` parameter to format the entries
### 0.2.5 ###
Bug fix - all users should upgrade
### 0.2.4 ###
Users using remote URLs for their bibliography should upgrade
### 0.2.3 ###
All users must upgrade to this version - Please read the information
about the new location of bibtex and pdfs.
### 0.2.2 ###
Users using PHP 4 should upgrade
= 0.2.1 = 
All users should upgrade - plugin was broken until now
### 0.2 ###
All bib2html users should at least use this version so they don't break their installation
### 0.1 ###
First version

