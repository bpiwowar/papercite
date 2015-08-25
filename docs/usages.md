
## Bibliography mode

This is my whole list of publications:

<div class="code">[bibtex file=mypub.bib]</div>

This is my latest conference paper:

<div class="code">[bibtex file=mypub.bib key=CGW2006]</div>

You can also have a list of keys to display more than one paper:

<div class="code">[bibtex file=mypub.bib key=CGW2006,CGW2007]</div>

This is my bibliography maintained at citeulike.org

<div class="code">[bibtex file=http://www.citeulike.org/bibtex/user/username]</div>

This is my bibliography maintained at bibsonomy.org

<div class="code">[bibtex file=http://bibsonomy.org/bib/user/username?items=1000]</div>

This is a bibliography stored in the `papercite_data` custom field:

<div class="code">[bibtex file=custom://data]</div>

## Filtering mode

The `bibfilter` command adds small html form where user can choose from authors and publication types. It does:

*   displays simple form
*   reads data from the form (what is selected)
*   alters parameters (author & type) according to data from the form in the original command
*   passes this modified command as "bibtex" for further processing

Example of use:
 `[bibfilter group=year group_order=desc author=Nahodil|Vítků allow=incollection,mastersthesis sortauthors=0]`

bibfilter uses the same parameters as bibtex command, with these modifications:

*   `sortauthors=0/1`: sort authors alphabetically in the form if equal to 1

**note**: if no selection is made in form, bibtex parameters are not rewritten, this means that you can combine both commands as follows: -if no filter for type is made, bibtex command is called with e.g. type=INPROCEEDINGS|INCOLLECTIONS -the same for authors, the parameter "author" defines: -all authors for bibfilter menu -all authors for bibtex command

**Known limitations**: sorting names in the form does not work with Czech diacritics very well, (e.g. Šafář, Řasa..)

## Citation mode

The second way of using this plug-in (new to papercite), is to use bibcite and bibshow commands

<div class="code">[bibshow file=mybib.bib] Here is one reference [bibcite key=key1] and some others [bibcite key=key2,key3]</div>

You can use `[/bibshow]` to end the bibshow section and print the list of references:

<div class="code">[/bibshow]</div>

<div>Remarks:</div>

*   If the `[/bibshow]` shortcode is not present, then the bibliography is automatically displayed at the end of the page/post.
*   If the option is set, the `[bibshow]` tag can be automatically added when a `[bibcite]` is encountered. In this case, a default bibtex file should be given in the options.

