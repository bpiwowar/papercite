=== Plugin Name ===
Contributors: Benjamin Piwowarski
Donate link: 
Tags: formatting, bibtex
Requires at least: 1.5
Tested up to: 3.0
Stable tag: 0.2

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

1. download the zip file and extract the content of the zip file into a local folder
2. local bibtex files should be copied in papercite/data/ directory
3. upload the folder bib2html into your wp-content/plugins/ directory
4. log in the wordpress administration page and access the Plugins menu
5. activate papercite

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

1. This is an example on how to use the tag into a page bib2html-tag.png
2. This is an example of the output from my blog bib2html-output.png

== Changelog ==

= 0.1 =
 * Adapted the plugin from bib2html 0.9.3
 * Added the bibshow and bibcite commands
= 0.2 =
 * Added deny/allow parameters to [bibtex] so the plugin can replace bib2html

== Upgrade Notice ==

= 0.2 =
All bib2html users should at least use this version so they don't break their installation
= 0.1 =
First version

== A brief Markdown Example ==

When writing a page/post, you can use the tags [bibtex], [bibcite] and [bibshow] as follows:

This is my whole list of publications: [bibtex file=mypub.bib]
If you want to filter the type of bibtex items, you can use one of the attributes allow, deny and key as follows:

This is my latest conference paper:
[bibtex file=mypub.bib key=CGW2006]

This is my bibliography maintained at citeulike.org
[bibtex file=http://www.citeulike.org/bibtex/user/username]

This is my bibliography maintained at bibsonomy.org
[bibtex file=http://bibsonomy.org/bib/user/username?items=1000]

The second way of using this plug-in (new to papercite), is to use bibcite and bibshow commands

[bibshow file=mybib.bib]

Here is one reference [bibcite key=Piwowarski2010Exploring-a-Multidimensional]
and another [bibcite key=Piwowarski2009Sound-and-Complete]

You end with the following to print the list of references:

[/bibshow]
