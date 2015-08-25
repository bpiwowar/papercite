
# Location of BibTeX entries

BibTex files can be stored:

*   In a special folder `$DATA/bib`. In that case, the file can be referred to by using directly the file name (with the extension).
*   Anywhere on the Internet - papercite will download any file if the `file` begins by `http://`
*   As a custom field prefixed by `papercite_`. For example, the URL `custom://data` will use the data in the `papercite_**data**` custom field

# Attached files

For each bibliographic entry, files can be automatically matched to retrieve e.g. the paper PDF, or the slides.

First, the key of the bibtex entry is transformed - lowercased, and the characters `:` and `/` are replaced by `-`. The **field** determines the bibtex field that will be populated when matching. Then,

<dl>

<dt>Filesystem matching</dt>

<dd>A file will match if it is contained in the **folder** and its name is **[key]****[suffix]**.**extension**</dd>

<dt>WordPress media matching</dt>

<dd>A file will match if its mime-type corresponds (or is empty) and its permalink name matches **[key]****[suffix]**</dd>

</dl>


The URL of the matched file will be stored in the field `FIELD` of the bibtex entry, and will be available by templates. For example, this can be inserted to display a link to a presentation:

     @?ppt@ <a href="@ppt@" title='Download PPT' class='papercite_pdf'>
     <img src='@PAPERCITE_DATA_URL@/img/ppt.png' alt="[ppt]"/></a> @;@

The `FIELD`, `FOLDER`, `EXT` and mime-type can all be set in the preferences. Papercite will process the list of such triplets, and will set the bibtex field to the **last** matched file (if any).<a name="usage"></a>

# Using the Media Library

It is possible to use the WordPress media library. In this case, the name of the file is controlled by the permalink. By default, the permalink is a processed version of the file name: the name is lowercased, extension is removed, etc. **Due to WordPress limitations, it is necessary to attach the media file to a post before being able to edit the permalink**.

The matching process is determined by the papercite option page. See [the help on attached files](#attached_files).

Note that bibtex files are matched using the same process, with `application/x-bibtex` as the mime-type and no suffix.

## Using direct access to the WordPress files

The data folder where all custom files (bibtex, PDF, templates) will be denoted **`$DATA`** in this document. This folder is either `wp-content/papercite-data` or `wp-content/blogs.dir/**XXX**/files/papercite-data` (in case of multiple sites hosted on WordPress).

1.  Within the folder `$DATA`, the subfolders
    *   `bib` contains your bibtex files
    *   `pdf` contains your pdf files (_by default, but this can be customized_)
    *   `tpl` contains your custom templates
    *   `format` contains your custom entry formats
2.  Copy your bibtex files into the `bib` folder, and your pdf files into the `pdf` folder. To match a bibtex entry with a PDF file, the PDF file should have be named **KEY**`.pdf` where **KEY** is the bibtex key in lowercase, where `:` and `/` have been replaced by `-`.

<div class="warning">Do not customize the files directly in the main plugin folder: when updating the plug-in, the full `papercite` plugin folder will be replaced by the new version.
 <u>Use the `papercite-data` folder</u>.</div>
