## Customizing templates

<dl class="faq">

<dt>Adding extra text at the end of each publication (e.g. the number of citations, etc.)</dt>

<dd>Basically, in the `$DATA/tpl` folder, you can copy the default template (default-bibtex.tpl and default-bibshow.tpl depending on which command you use) and modify it by adding after `@#entry@` the command

<pre>@?citations@@citations@ citations@;citations@.</pre>

Then you can use a custom field citations in your bibtex file, e.g.

<pre>@inproceedings{...
   citations = {3},
   ...
}</pre>

</dd>

</dl>