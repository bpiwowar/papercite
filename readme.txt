=== Plugin Name ===
Contributors: bpiwowar
Tags: formatting, bibtex
Requires at least: 1.5
Tested up to: 3.0
Stable tag: 0.2.9

papercite (based on bib2html 0.9.3) format bibtex entries as HTML.

== Description ==

If you need to maintain a bibliography in bibtex format and also write a Web page to publish the list of your publications, then papercite is the right solution for you. 

papercite enables to add bibtex entries formatted as HTML in Wordpress pages and posts. The input data is the bibtex file (either local or remote) and the output is HTML. The entries are formatted by default using the IEEE style. Bibtex source file and a link to the publication are also available from the HTML. 

Features:

* input data directly from the bibtex text file
* source files can be URL (e.g., from citeulike.org and bibsonomy.org)
* automatic HTML generation
* easy inclusion in wordpress pages/posts by means of a dedicated tag
* possibility of filtering the bibtex entries based on their type (allow, deny)
* possibility to access the single bibtex entry source code to enable copy&paste (toggle-enabled visualization)
* expose URL of each document (if network-reachable)
* possibility of editing the bibtex file directly from the wordpress administration page

Features compared to bib2html (0.9.3):

* adds the possibility of writing a text with reference to a bibtex file, and to print the bibliography at the end.
* it checks if a PDF file whose name match the key exists and link to it if this is so
* it uses the DOI information to create an external link
* object oriented redesign of the plugin, so that further extensions are easy to make

The papercite plugin has been developed and tested under Wordpress 3.0. It is based on bib2html version 0.9.3.

== Installation ==

Follow these step or use the plugin installer from WordPress to
install papercite:
1. download the zip file and extract the content of the zip file into a local folder
2. upload the folder papercite into your wp-content/plugins/ directory
3. log in the wordpress administration page and access the Plugins
menu

Then, you should activate papercite, and follow the instructions
given in *other notes*.

== Frequently Asked Questions ==

= How can I edit my bibtex files? =

If your file is local to the blog installation, you have two options:
- via FTP client with text editor
- via Wordpress Admin interface: Manage->Files->Other Files
-- use wp-content/plugins/papercite/data/mybibfile.bib as a path

Alternatively, you can maintain your updated biblilography by using systems such as citeulike.org and bibsonomy.org; 
specify the bib file using as a URL (e.g., in citeulike, you should use http://www.citeulike.org/bibtex/user/username)

= How are the entries sorted? =

Since version 0.1, the entries are sorted by year starting from the most
recent; in future revision, I plan to make this configurable by the user

= How can I personlize the HTML rendering? =

The HTML rendering is isolated in a template file called bibentry-html.tpl.
Just change it.

== Screenshots ==

1. Cite mode
2. bib2html mode

== Changelog ==

= 0.2.9 =
  * Small bug fix (removes a warning)
= 0.2.8 =
  * Documentation update
  * New parameter `format`
 = 0.2.5 =
  * Fixed a bug with the allow filter
= 0.2.4 =
  * Small bug fixes (if the file is an URL) and use of WP functions to
  retrieve remote data (useful when you have proxies)
= 0.2.3 =
 * Fixed a bug introduced in 0.2.2
 * Changed the default folder for data in order to avoid data loss
 when upgrading
 * Finished the encapsulation of all the code, so as to prevent name
 clash with wordpress and/or other plugins
= 0.2.2 =
 * Removed PHP 5 specific code
= 0.2.1 =
 * Added the template file
= 0.2 =
 * Added deny/allow parameters to [bibtex] so the plugin can replace bib2html
= 0.1 =
 * Adapted the plugin from bib2html 0.9.3
 * Added the bibshow and bibcite commands

== Upgrade Notice ==

= 0.2.9 = 
Removed a PHP warning with bibshow
= 0.2.8 =
Introduced the `format` parameter to format the entries
= 0.2.5 =
Bug fix - all users should upgrade
= 0.2.4 =
Users using remote URLs for their bibliography should upgrade
= 0.2.3 =
All users must upgrade to this version - Please read the information
about the new location of bibtex and pdfs.
= 0.2.2 =
Users using PHP 4 should upgrade
= 0.2.1 = 
All users should upgrade - plugin was broken until now
= 0.2 =
All bib2html users should at least use this version so they don't break their installation
= 0.1 =
First version

== A brief Markdown Example ==

= Data structure =

1. Create a folder in `wp-content/papercite-data`, with two
subfolders: `bib` (this will contain the bibtex files) and `pdf` (this
will contain the PDF file).
2. Copy your bibtex files into the `bib` folder, and your pdf files
into the `pdf` folder. To match a bibtex entry with a PDF file, the
PDF file should have be named **KEY**`.pdf` where **KEY** is the
bibtex key in lowercase, where `:` and `/` have been replaced by `-`.

= Bibliography mode =

This is my whole list of publications: 

`[bibtex file=mypub.bib]`

If you want to filter the type of bibtex items, you can use one of the attributes allow, deny and key as follows:

This is my latest conference paper:

`[bibtex file=mypub.bib key=CGW2006]`

You can also have a list of keys to display more than one paper:

`[bibtex file=mypub.bib key=CGW2006, CGW2007]`

This is my bibliography maintained at citeulike.org

`[bibtex file=http://www.citeulike.org/bibtex/user/username]`

This is my bibliography maintained at bibsonomy.org

`[bibtex file=http://bibsonomy.org/bib/user/username?items=1000]`

= Citation mode =

The second way of using this plug-in (new to papercite), is to use bibcite and bibshow commands

`[bibshow file=mybib.bib]
Here is one reference [bibcite key=key1]
and another [bibcite key=key2]`

You end with the following to print the list of references:

`[/bibshow]`

= Formatting the entries =

You can modify the style of the citations by using the `format`
parameter with the `bibshow` and `bibtex` commands.
For example, 

`[bibtex file=mypub.bib format=britishmedicaljournal]`

The following format are currently available: 

* ieee (default)
* apa 
* britishmedicaljournal 
* chicago
* harvard 
* mla
* turabian

