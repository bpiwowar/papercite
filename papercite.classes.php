<?php
/** Main Class Papercite moved to this file
 * Created by PhpStorm.
 * User: digfish
 * Date: 01/01/2019
 * Time: 19:17
 */

/*  Copyright 2012-18  Benjamin Piwowarski  (email : benjamim@bpiwowar.net)

    Contributors:
    - Michael Schreifels: auto-bibshow and no processing in post lists options
    - Stefan Aiche: group by year option
    - Łukasz Radliński: bug fixes & handling polish characters
    - Some parts of the code come from bib2html (version 0.9.3) written by
    Sergio Andreozzi.

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/

// Options


include("papercite_options.php");

/**
 * Get string with author name(s) and make regex of it.
 * String with author or a list of authors (passed as parameter to papercite) in the following format:
 * -a1|a2|..|an   - publications including at least one of these authors
 * -a1&a2&..&an   - publications including all of these authors
 *
 * @param unknown $authors - string parsed from papercite after tag: "author="
 */
class PaperciteAuthorMatcher
{
    function __construct($authors)
    {
        // Each element of this array is alternative match
        $this->filters = array();

        if (!isset($authors) || empty($authors)) {
        } elseif (!is_string($authors)) {
            echo "Warning: cannot parse option \"authors\", this is specified by string!<br>";// probably useless..
            // string contains both: & and | => this is not supported
        } else {
            require_once(dirname(__FILE__) . "/lib/bibtex_common.php");
            foreach (preg_split("-\\|-", $authors) as $conjonction) {
                $this->filters[] = PaperciteBibtexCreators::parse($conjonction);
            }
        }
    }

    function matches(&$entry)
    {
        $ok = true;
        $eAuthors = &$entry["author"];
        foreach ($this->filters as &$filter) {
            foreach ($filter->creators as $author) {
                $ok = false;
                foreach ($eAuthors->creators as $eAuthor) {
                    if ($author["surname"] === $eAuthor["surname"]) {
                        $ok = true;
                        break;
                    }
                }
                // Author was not found in publication
                if (!$ok) {
                    break;
                }
            }
            // Everything was OK
            if ($ok) {
                break;
            }
        }
        return $ok;
    }
}

class Papercite
{


    var $parse = false;


    // List of publications for those citations
    var $bibshows = array();

    // Our caches (bibtex files and formats)
    var $cache = array();

    // Array of arrays of current citations
    var $cites = array();

    // Global replacements for cited keys
    var $keys = array();
    var $keyValues = array();

    // bibshow options stack
    var $bibshow_options = array();
    var $bibshow_tpl_options = array();

    // Global counter for unique references of each
    // displayed citation (used by bibshow)
    var $citesCounter = 0;

    // Global counter for unique reference of each
    // displayed citation
    var $counter = 0;

    //digfish: extra textual footnotes
    var $textual_footnotes = array();
    var $textual_footnotes_counter = 0;

    /** Returns filename of cached version of given url
     * @param url The URL
     * @param timeout The timeout of the cache
     */
    function getCached($url, $timeout = 3600, $sslverify = false)
    {
        // check if cached file exists
        $name = strtolower(preg_replace("@[/:]@", "_", $url));
        $dir_path = self::getCacheDirectory('path');
        $file = "$dir_path/$name.bib";

        // check if file date exceeds 60 minutes
        if (!(file_exists($file) && (filemtime($file) + $timeout > time()))) {
            // Download URL and process
            $req = wp_remote_get($url, array('sslverify' => $sslverify));
            if (is_wp_error($req)) {
                $this->addMessage("Could not retrieve remote URL " . htmlentities($url) . ": " . $req->get_error_message());
                return false;
            }

            $code = $req["response"]["code"];
            if (!preg_match("#^2\d+$#", $code)) {
                $this->addMessage("Could not retrieve remote URL " . htmlentities($url) . ": Page not found / {$code} error code");
                return false;
            }

            // Everything is OK: retrieve the body of the HTTP answer
            $body = wp_remote_retrieve_body($req);
            if (!file_exists($dir_path)) {
                mkdir($dir_path);
            }

            if ($body) {
                $f = fopen($file, "wb");
                fwrite($f, $body);
                fclose($f);
            } else {
                $this->addMessage("Could not retrieve remote URL " . htmlentities($url));
                return null;
            }


            if (!$f) {
                $this->addMessage("Failed to write file " . $file . " - check directory permission according to your Web server privileges.");
                return false;
            }
        }

        $dir_url = self::getCacheDirectory('url');

        return array($file, $dir_url . '/' . $name);
    }

    static $bibtex_parsers = array("pear" => "Pear parser", "osbib" => "OSBiB parser");

    // Names of the options that can be set
    static $option_names = array("format", "timeout", "file", "bibshow_template", "bibtex_template", "bibtex_parser",
        "use_db", "auto_bibshow", "use_media", "use_files", "skip_for_post_lists", "process_titles", "checked_files", "show_links", "highlight", "ssl_check");

    // Default value of options
    static $default_options =
        array("format" => "ieee", "group" => "none", "order" => "desc", "sort" => "none", "key_format" => "numeric",
            "bibtex_template" => "default-bibtex", "bibshow_template" => "default-bibshow", "bibtex_parser" => "osbib", "use_db" => false,
            "auto_bibshow" => false, "use_media" => false, "use_files" => true, "skip_for_post_lists" => false, "group_order" => "", "timeout" => 3600, "process_titles" => true,
            "checked_files" => array(array("pdf", "pdf", "", "pdf", "application/pdf")), "show_links" => true, "highlight" => "",
            "ssl_check" => false);

    /**
     * Init is called before the first callback
     */
    function init()
    {

        // i18n
        // http://codex.wordpress.org/I18n_for_WordPress_Developers#Translating_Plugins
        $plugin_dir = basename(dirname(__FILE__));
        load_plugin_textdomain('papercite', false, $plugin_dir);

        // Get general preferences & page wise preferences
        if (!isset($this->options)) {
            $this->options = papercite::$default_options;
            $pOptions = get_option('papercite_options');

            // Use preferences if set to override default values
            if (is_array($pOptions)) {
                foreach (self::$option_names as &$name) {
                    if (array_key_exists($name, $pOptions) && $pOptions[$name] !== "") {
                        $this->options[$name] = $pOptions[$name];
                    }
                }
            }

            // Use custom field values "papercite_options"
            $option_fields = get_post_custom_values("papercite_options");
            if ($option_fields && sizeof($option_fields) > 0) {
                foreach ($option_fields as $field) {
                    $matches = array();
                    preg_match_all("#^\s*([\w\d-_]+)\s*=\s*(.+)$#m", $field, $matches, PREG_SET_ORDER);
                    foreach ($matches as &$match) {
                        $this->options[$match[1]] = trim($match[2]);
                    }
                }
            }


            // Upgrade if needed
            if ($this->options["bibtex_parser"] == "papercite") {
                $this->options["bibtex_parser"] = "osbib";
            }
        }
    }

    /**
     * Get a writeable directory for caching remote bibtex files.
     *
     * @param string $type 'path' for local filepath, or 'url' for URL.
     * @return string
     */
    static function getCacheDirectory($type = 'dir')
    {
        $uploads_dir = wp_upload_dir();

        $base = 'url' === $type ? $uploads_dir['baseurl'] : $uploads_dir['basedir'];
        return apply_filters('papercite_cache_directory', $base . '/papercite-cache');
    }


    static function getCustomDataDirectory()
    {
        global $wpdb;
        $url = WP_CONTENT_URL;
        if (is_multisite()) {
            $subpath = '/blogs.dir/' . $wpdb->blogid . "/files";
            $url .= $subpath;
        }
        return $url . "/papercite-data";
    }

    /**
     * Check the different paths where papercite data can be stored
     * and return the first match, starting by the preferred ones
     * @return either false (no match), or an array with the full
     * path and the URL
     *
     * This method searches:
     * 1) In the wordpress medias
     * 2) In the papercite folders
     *
     * @param $relfile The file name
     * @param $ext The extension for the file (file in folder)
     * @param $folder The folder that contains the file (file in folder)
     * @param $mimetype The mime-type (wordpress media)
     * @return FALSE if no match, an array (path, URL)
     */
    static function getDataFile($relfile, $ext, $folder, $mimetype, $options, $use_files = false)
    {
        global $wpdb;

        if ($options["use_media"]) {
            // Search for files in media
            $filter = array(
                'name' => $relfile,
                'post_type' => 'attachment'
            );
            if (!empty($mimetype)) {
                $filter["post_mime_type"] = $mimetype;
            }
            $posts = get_posts($filter);

            if (sizeof($posts) > 0) {
                // We should have only one match (names are unique) ?
                $path = get_attached_file($posts[0]->ID);
                $url = wp_get_attachment_url($posts[0]->ID);
                return array($path, $url);
            }
        }

        if ($use_files || $options["use_files"]) {
            // Rel-file as usual
            $relfile = "$folder/$relfile.$ext";

            // Multi-site case
            if (is_multisite()) {
                $subpath = '/blogs.dir/' . $wpdb->blogid . "/files/papercite-data/$relfile";
                $path = WP_CONTENT_DIR . $subpath;
                if (file_exists($path)) {
                    return array($path, WP_CONTENT_URL . $subpath);
                }
            }

            if (file_exists(WP_CONTENT_DIR . "/papercite-data/$relfile")) {
                return array(WP_CONTENT_DIR . "/papercite-data/$relfile", WP_CONTENT_URL . "/papercite-data/$relfile");
            }

            $path = plugin_dir_path(__FILE__) . "/$relfile";
            if (file_exists($path)) {
                return array($path, plugin_dir_url($path));
            }
        }

        // Nothin' found
        return false;
    }

    /**
     * Returns the content of a file (disk or post data)
     *
     * @see papercite::getDataFile
     */
    static function getContent($relfile, $ext, $folder, $mimetype, $options, $use_files = false)
    {
        // Handles custom://
        $custom_prefix = "custom://";
        if (papercite::startsWith($relfile, $custom_prefix)) {
            $key = substr($relfile, strlen($custom_prefix));
            $data = get_post_custom_values("papercite_$key");
            if ($data) {
                return $data[0];
            }
        }

        // Normal behavior
        $data = papercite::getDataFile($relfile, $ext, $folder, $mimetype, $options, $use_files);
        if ($data) {
            return file_get_contents($data[0]);
        }

        return false;
    }

    /**
     * Check if a matching file exists, and add it to the bibtex if so
     * @param $entry key
     * @param $types An array of couples (folder, extension)
     */
    function checkFiles(&$entry, $options)
    {

        if (!isset($entry['cite'])) {
            return;
        }
        $id = strtolower(preg_replace("@[/:]@", "-", $entry["cite"]));
        foreach ($options["checked_files"] as &$type) {
            // 0. field, 1. folder, 2. suffix, 3. extension, 4. mime-type
            if (sizeof($type) == 3) {
                $type[3] = $type[2];
                $type[2] = "";
                $type[4] = "";
            }
            $file = $this->getDataFile("$id$type[2]", $type[3], $type[1], $type[4], $options);
            if ($file) {
                $entry[$type[0]] = $file[1];
            }
        }
    }

    static function array_get($array, $key, $defaultValue)
    {
        return array_key_exists($key, $array) ? $array[$key] : $defaultValue;
    }

    static function startsWith($haystack, $needle)
    {
        return !strncmp($haystack, $needle, strlen($needle));
    }

    /**
     * Get the bibtex data from an URI
     */
    function getData($biburis, $options)
    {
        global $wpdb, $papercite_table_name, $papercite_table_name_url;

        $timeout = $options["timeout"];
        $processtitles = $options["process_titles"];
        $sslverify = $options["ssl_check"];

        // Loop over the different given URIs
        $bibFile = false;
        $array = explode(",", $biburis);
        $result = array();

        foreach ($array as $biburi) {
            // (1) Get the context
            $data = false;
            $stringedFile = false;
            $custom_prefix = "custom://";

            // Handles custom:// by adding the post number
            if (papercite::startsWith($biburi, $custom_prefix)) {
                $stringedFile = true;
                $key = substr($biburi, strlen($custom_prefix));
                $biburi = "post://" . get_the_ID() . "/" . $key;
                $data = get_post_custom_values("papercite_$key");
                if ($data) {
                    $data = $data[0];
                }
            }

            if (!Papercite::array_get($this->cache, $biburi, false)) {
                if ($stringedFile) {
                    // do nothing
                } elseif (preg_match('#^(ftp|http)s?://#', $biburi) == 1) {
                    $bibFile = $this->getCached($biburi, $timeout, $sslverify);
                } else {
                    $biburi = preg_replace("#\\.bib$#", "", $biburi);
                    $bibFile = $this->getDataFile("$biburi", "bib", "bib", "application/x-bibtex", $options);
                }


                if ($data === false && !($bibFile && file_exists($bibFile[0]))) {
                    continue;
                }

                // Customize URIs depending on parsing options
                $biburi .= $processtitles ? "#pt=1" : "#pt=0";

                // (2) Parse the BibTeX
                if ($data || file_exists($bibFile[0])) {
                    if (!$data) {
                        $fileTS = filemtime($bibFile[0]);

                        // Check if we don't have the data in cache
                        if ($this->useDb()) {
                            $oldurlid = -1;
                            // We use entrytype as a timestamp
                            $row = $wpdb->get_row($wpdb->prepare("SELECT urlid, ts FROM $papercite_table_name_url WHERE url=%s", $biburi));
                            if ($row) {
                                $oldurlid = $row->urlid;
                                if ($row->ts >= $fileTS) {
                                    $result[$biburi] = $this->cache[$biburi] = array("__DB__", $row->urlid);
                                    continue;
                                }
                            }
                        }

                        $data = file_get_contents($bibFile[0]);
                    }

                    if (!empty($data)) {
                        switch ($this->options["bibtex_parser"]) {
                            case "pear": // Pear parser
                                $this->_parser = new PaperciteStructures_BibTex(array('removeCurlyBraces' => true, 'extractAuthors' => true, 'processTitles' => $processtitles));
                                $this->_parser->loadString($data);
                                $stat = $this->_parser->parse();

                                if (!$stat) {
                                    return $this->cache[$biburi] = false;
                                }
                                $this->cache[$biburi] = &$this->_parser->data;
                                break;

                            default: // OSBiB parser
                                $parser = new PaperciteBibTexEntries();
                                $parser->processTitles($processtitles);
                                if (!$parser->parse($data)) {
                                    $this->cache[$biburi] = false;
                                    continue;
                                } else {
                                    $this->cache[$biburi] = &$parser->data;
                                }
                                break;
                        }


                        // Save to DB
                        if (!$stringedFile && $this->useDb()) {
                            // First delete everything
                            if ($oldurlid >= 0) {
                                $code = $wpdb->query($wpdb->prepare("DELETE FROM $papercite_table_name WHERE urlid=%d", $oldurlid));
                                if ($code === false) {
                                    break;
                                }
                            } else {
                                $code = $wpdb->query($wpdb->prepare("INSERT INTO $papercite_table_name_url(url, ts) VALUES (%s, 0)", $biburi));
                                if ($code === false) {
                                    break;
                                }
                                $oldurlid = $wpdb->insert_id;
                            }

                            $code = true;
                            foreach ($this->cache[$biburi] as &$value) {
                                if (isset($value['year'])) {
                                    $year = is_numeric($value["year"]) ? intval($value["year"]) : -1;
                                } else {
                                    $year = -1;
                                }
                                $statement = $wpdb->prepare(
                                    "REPLACE $papercite_table_name(urlid, bibtexid, entrytype, year, data) VALUES (%s,%s,%s,%s,%s)",
                                    $oldurlid,
                                    isset($value['cite']) ? $value["cite"] : '',
                                    isset($value["entrytype"]) ? $value["entrytype"] : '',
                                    $year,
                                    maybe_serialize($value)
                                );
                                $code = $wpdb->query($statement);
                                if ($code === false) {
                                    break;
                                }
                            }
                            if ($code !== false) {
                                $statement = $wpdb->prepare("REPLACE INTO $papercite_table_name_url(url, urlid, ts) VALUES(%s,%s,%s)", $biburi, $oldurlid, $fileTS);
                                $code = $wpdb->query($statement);
                            }
                        }
                    }
                }
            } // end bibtex processing (not in cache)

            // Add to the list
            if (Papercite::array_get($this->cache, $biburi, false)) {
                $result[$biburi] = $this->cache[$biburi];
            }
        } // end loop over URIs

        return $result;
    }

    /** Returns true if papercite uses a database backend */
    function useDb()
    {
        return $this->options["use_db"];
    }

    // Get the subset of keys present in the entries
    static function getEntriesByKey(&$entries, &$keys)
    {
        global $wpdb, $papercite_table_name;
        $n = 0;
        $a = array();
        $dbs = array();
        $found = array();
        foreach ($entries as $key => &$outer) {
            if (is_array($outer) && $outer[0] == "__DB__") {
                $dbs[] = $outer[1];
            } else {
                foreach ($outer as $entry) {
                    if (in_array($entry["cite"], $keys)) {
                        $a[] = $entry;
                        $found[] = $entry["cite"];
                        $n = $n + 1;
                        // We found everything, early break
                        if ($n == sizeof($keys)) {
                            break;
                        }
                    }
                }
            }
            if ($n == sizeof($keys)) {
                break;
            }
        }

        // Case where we have to check the db
        $unfound = array_diff($keys, $found);
        if ($dbs && sizeof($unfound) > 0) {
            $dbs = papercite::getDbCond($dbs);
            foreach ($unfound as &$v) {
                $v = '"' . $wpdb->escape($v) . '"';
            }
            $keylist = implode(",", $unfound);
            $st = "SELECT data FROM $papercite_table_name WHERE $dbs and bibtexid in ($keylist)";
            $val = $wpdb->get_col($st);
            if ($val !== false) {
                foreach ($val as &$data) {
                    $a[] = maybe_unserialize($data);
                }
            }
        }

        return $a;
    }

    // Get the options to forward to bib2tpl
    function getBib2TplOptions($options)
    {
        return array(
            "anonymous-whole" => true, // for compatibility in the output
            "group" => $options["group"],
            "group_order" => $options["group_order"],
            "sort" => $options["sort"],
            "order" => $options["order"],
            "key_format" => $options["key_format"],
            "limit" => papercite::array_get($options, "limit", 0),
            "highlight" => $options["highlight"]
        );
    }

    /** Main entry point */
    function process(&$matches)
    {
        $r = $this->_process($matches);
        if (current_user_can("edit_post", get_the_ID())) {
            $r .= $this->getAndCleanMessages();
        }
        return $r;
    }

    /**
     * Main entry point: Handles a match in the post
     */
    function _process(&$matches)
    {
        global $wpdb, $papercite_table_name;
        $debug = false;

        $post = null;

        //d($matches);

        // --- Initialisation ---

        // Includes once the bibtex parser
        require_once(dirname(__FILE__) . "/lib/BibTex_" . $this->options["bibtex_parser"] . ".php");

        // Includes once the converter
        require_once("bib2tpl/bibtex_converter.php");

        // Get the command
        $command = $matches[1];

        // Get all the options pairs and store them
        // in the $options array
        $options_pairs = array();
        preg_match_all("/\s*([\w\-:_]+)=(?:([^\"]\S*)|\"([^\"]+)\")(?:\s+|$)/", sizeof($matches) > 2 ? $matches[2] : "", $options_pairs, PREG_SET_ORDER);


        // print "<pre>";
        // print htmlentities(print_r($options_pairs,true));
        // print "</pre>";

        // ---Set preferences
        // by order of increasing priority
        // (0) Set in the shortcode
        // (1) From the preferences
        // (2) From the custom fields
        // (3) From the general options
        // $this->options has already processed the steps 0-2

        //d($matches);


        $options = $this->options;
        $options["filters"] = array();

        foreach ($options_pairs as $x) {
            $value = $x[2] . (sizeof($x) > 3 ? $x[3] : "");

            if ($x[1] == "template") {
                // Special case of template: should overwrite the corresponding command template
                $options["${command}_$x[1]"] = $value;
            } elseif (Papercite::startsWith($x[1], "filter:")) {
                $options["filters"][substr($x[1], 7)] = $value;
            } else {
                $options[$x[1]] = $value;
            }
        }

        // --- Compatibility issues: handling old syntax
        if (array_key_exists("groupByYear", $options) && (strtoupper($options["groupByYear"]) == "TRUE")) {
            $options["group"] = "year";
            $options["group_order"] = "desc";
        }

        $data = null;


        return $this->processCommand($command, $options);
    }


    /** Process a parsed command
     * @param $command The command (shortcode)
     * @options The options of the command
     */
    function processCommand($command, $options)
    {
        global $wpdb, $papercite_table_name, $note_text;

        // --- Process the commands ---
        switch ($command) {
            // display form, convert bibfilter to bibtex command and recursivelly call the same;-)
            case "bibfilter":
                // this should return hmtl form and new command composed of (modified) $options_pairs
                return $this->bibfilter($options);

            // bibtex command:
            case "bibtex":
                $result = $this->getEntries($options);
                return $this->showEntries($result, $options, $this->getBib2TplOptions($options), false, $options["bibtex_template"], $options["format"], "bibtex");

            // bibshow / bibcite commands
            case "bibshow":
                $data = $this->getData($options["file"], $options);
                if (!$data) {
                    return "<span style='color: red'>[Could not find the bibliography file(s)" .
                        (current_user_can("edit_post") ? " with name [" . htmlspecialchars($options["file"]) . "]" : "") . "</span>";
                }

                // TODO: replace this by a method call
                $refs = array("__DB__" => array());
                foreach ($data as $bib => &$outer) {
                    // If we have a database backend for a bibtex, use it
                    if (is_array($outer) && $outer[0] == "__DB__") {
                        array_push($refs["__DB__"], $outer[1]);
                    } else {
                        foreach ($outer as &$entry) {
                            if (isset($entry['cite'])) {
                                $key = $entry["cite"];
                                $refs[$key] = &$entry;
                            }
                        }
                    }
                }

                $this->bibshow_tpl_options[] = $this->getBib2TplOptions($options);
                $this->bibshow_options[] = $options;
                array_push($this->bibshows, $refs);
                $this->cites[] = array();
                break;

            // Just cite
            case "bibcite":
                if (sizeof($this->bibshows) == 0) {
                    if ($options["auto_bibshow"]) {
                        // Automatically insert [bibshow] because of unexpected [bibcite]
                        $generated_bibshow = array('[bibshow]', 'bibshow');
                        $this->process($generated_bibshow);
                        unset($generated_bibshow);
                    } else {
                        return "[<span title=\"Unknown reference: $options[key]\">?</span>]";
                    }
                }

                $keys = preg_split("/,/", $options["key"]);
                $cites = &$this->cites[sizeof($this->cites) - 1];
                $returns = "";

                foreach ($keys as $key) {
                    if ($returns) {
                        $returns .= ", ";
                    }

                    // First, get the corresponding entry
                    $num = Papercite::array_get($cites, $key, false);

                    // Did we already cite this?
                    if (!$num) {
                        // no, register this using a custom ID (hopefully, there will be no conflict)
                        $id = "BIBCITE%%" . $this->citesCounter . "%";
                        $this->citesCounter++;
                        $num = sizeof($cites);
                        $cites[$key] = array($num, $id);
                    } else {
                        // yes, just copy the id
                        $id = $num[1];
                    }
                    $returns .= "$id";
                }

                return "[$returns]";

            case "/bibshow":

                return $this->end_bibshow();


            default:
                return "[error in papercite: unhandled]";
        }
    }

    /** Returns true if the all the regular expression filters are matched */
    static function userFiltersMatch($filters, $entry)
    {
        foreach ($filters as $fieldname => $regexp) {
            $v = array_key_exists($fieldname, $entry) ? $entry[$fieldname] : "";
            if (!preg_match($regexp, $v)) {
                return false;
            }
        }
        return true;
    }

    /** Get entries fullfilling a condition (bibtex & bibfilter) */
    function getEntries($options)
    {
        global $wpdb, $papercite_table_name;
        // --- Filter the data
        $entries = $this->getData($options["file"], $options);
        if ($entries === false) {
            $this->addMessage("[Could not find the bibliography file(s) with name [" . htmlspecialchars($options["file"]) . "]");
            return false;
        }

        if (array_key_exists('key', $options)) {
            // Select only specified entries
            $keys = preg_split("-,-", $options["key"]);
            $a = array();
            $n = 0;

            $result = papercite::getEntriesByKey($entries, $keys);

            if (array_key_exists("allow", $options) || array_key_exists("deny", $options) || array_key_exists("author", $options)) {
                $this->addMessage("[papercite] Filtering by (key argument) is compatible with filtering by type or author (allow, deny, author arguments)", E_USER_NOTICE);
            }
        } else {
            // Based on the entry types
            $allow = Papercite::array_get($options, "allow", "");
            $deny = Papercite::array_get($options, "deny", "");
            $allow = $allow ? preg_split("-,-", $allow) : array();
            $deny = $deny ? preg_split("-,-", $deny) : array();

            $author_matcher = new PaperciteAuthorMatcher(Papercite::array_get($options, "author", ""));

            $result = array();
            $dbs = array();
            foreach ($entries as $key => &$outer) {
                if (is_array($outer) && $outer[0] == "__DB__") {
                    $dbs[] = $outer[1];
                } else {
                    foreach ($outer as &$entry) {
                        $t = &$entry["entrytype"];
                        if ((sizeof($allow) == 0 || in_array($t, $allow)) && (sizeof($deny) == 0 || !in_array($t, $deny)) && $author_matcher->matches($entry) && Papercite::userFiltersMatch($options["filters"], $entry)) {
                            $result[] = $entry;
                        }
                    }
                }
            }


            // --- Add entries from database
            if ($dbs) {
                $dbCond = $this->getDbCond($dbs);

                // Handles year and entry type by direct SQL
                foreach ($allow as &$v) {
                    $v = '"' . $wpdb->escape($v) . '"';
                }
                $allowCond = $allow ? "and entrytype in (" . implode(",", $allow) . ")" : "";
                foreach ($deny as &$v) {
                    $v = '"' . $wpdb->escape($v) . '"';
                }
                $denyCond = $deny ? "and entrytype not in (" . implode(",", $deny) . ")" : "";

                // Retrieve and filter further
                $st = "SELECT data FROM $papercite_table_name WHERE $dbCond $denyCond $allowCond";
                $rows = $wpdb->get_col($st);
                if ($rows) {
                    foreach ($rows as $data) {
                        $entry = maybe_unserialize($data);
                        if ($author_matcher->matches($entry) && Papercite::userFiltersMatch($options["filters"], $entry)) {
                            $result[] = $entry;
                        }
                    }
                }
            }
        }

        return $result;
    }

    //! Add an error message
    var $error_messages = array();

    function addMessage($message)
    {
        if (defined('PHPUNIT_PAPERCITE_TESTSUITE')) {
            print "[message] $message\n";
        }
        $this->error_messages[] = "<div>" . $message . "</div>";
    }

    //! Get all the error messages and clean the stack
    function getAndCleanMessages()
    {
        if (sizeof($this->error_messages) == 0) {
            return "";
        }

        $s = "<div class='papercite_errors'>";
        foreach ($this->error_messages as $message) {
            $s .= $message;
        }
        $s .= "</div>";
        $this->error_messages = array();
        return $s;
    }

    // digfish: get textual footnotes

    function getTextualFootnotes()
    {
        return $this->textual_footnotes;
    }

    //! Get a db condition subquery
    static function getDbCond(&$dbArray)
    {
        global $wpdb;

        $dbs = array();
        foreach ($dbArray as &$db) {
            $dbs[] = "\"" . $wpdb->escape($db) . "\"";
        }
        $dbs = implode(",", $dbs);
        if ($dbs) {
            $dbs = "urlid in ($dbs)";
        }

        return $dbs;
    }

    function end_bibshow()
    {
        global $wpdb, $papercite_table_name;

        // select from cites
        if (sizeof($this->bibshows) == 0) {
            return "";
        }
        // Remove the array from the stack
        $data = array_pop($this->bibshows);
        $cites = array_pop($this->cites);
        $tplOptions = array_pop($this->bibshow_tpl_options);
        $options = array_pop($this->bibshow_options);
        $refs = array();


        $dbs = papercite::getDbCond($data["__DB__"]);


        // Order the citations according to citation order
        // (might be re-ordered latter)
        foreach ($cites as $key => &$cite) {
            // Search
            if ((!isset($data[$key]) || !$data[$key]) && $dbs) {
                $val = $wpdb->get_var($wpdb->prepare("SELECT data FROM $papercite_table_name WHERE $dbs and bibtexid=%s", $key));
                if ($val !== false) {
                    $refs[$cite[0]] = maybe_unserialize($val);
                }
            } else {
                $refs[$cite[0]] = $data[$key];
            }

            $refs[$cite[0]]["pKey"] = $cite[1];
            // just in case
            $refs[$cite[0]]["cite"] = $key;
        }

        ksort($refs);

        return $this->showEntries(array_values($refs), $options, $tplOptions, true, $options["bibshow_template"], $options["format"], "bibshow");
    }


    function showTextualFootnotes()
    {
        $post_id = '0';
        if (func_num_args() == 1) {
            $post_id = func_get_arg(0);
        }
        $buf = '<UL class="ppc_footnotes" style="list-style: none">';
        foreach ($this->getTextualFootnotes() as $id => $content) {
            $buf .= "<li><a class='ppc_footnote' name='fn_".$post_id.'_'.$id."'></a>(<sup>$id</sup>) $content</li>";
        }
        $buf .= '</UL>';
        return $buf;
    }

    /**
     * Show a set of entries
     * @param refs An array of references
     * @param options The options to pass to bib2tpl
     * @param getKeys Keep track of the keys for a final substitution
     */
    function showEntries($refs, $goptions, $options, $getKeys, $mainTpl, $formatTpl, $mode)
    {


        // Get the template files
        $main = $this->getContent("$mainTpl", "tpl", "tpl", "MIMETYPE", $goptions, true);
        $format = $this->getContent("$formatTpl", "tpl", "format", "MIMETYPE", $goptions, true);

        // Fallback to defaults if needed
        if (!$main) {
            $main = $this->getContent(papercite::$default_options["${mode}_template"], "tpl", "tpl", "MIMETYPE", $goptions, true);
            if (!$main) {
                throw new \Exception("Could not find template ${mode}_template");
            }
        }
        if (!$format) {
            $format = $this->getContent(papercite::$default_options["format"], "tpl", "format", "MIMETYPE", $goptions, true);
            if (!$main) {
                throw new \Exception("Could not find template " . papercite::$default_options["format"]);
            }
        }

        $bibtexEntryTemplate = new PaperciteBibtexEntryFormat($format);

        // Gives a distinct ID to each publication (i.e. to display the corresponding bibtex)
        // in the reference list
        if ($refs) {
            foreach ($refs as &$entry) {
                $entry["papercite_id"] = $this->counter++;
            }
        }

        // Convert (also set the citation key)
        $bib2tpl = new BibtexConverter($options, $main, $bibtexEntryTemplate);
        $bib2tpl->setGlobal("WP_PLUGIN_URL", WP_PLUGIN_URL);
        $bib2tpl->setGlobal("PAPERCITE_DATA_URL", Papercite::getCustomDataDirectory());

        // Now, check for attached files
        if (!$refs) {
            // No references: return nothing
            return "";
        }

        foreach ($refs as &$ref) {
            // --- Add custom fields
            $this->checkFiles($ref, $goptions);
        }

        // This will set the key of each reference
        $r = $bib2tpl->display($refs);

        // If we need to get the citation key back
        if ($getKeys) {
            foreach ($refs as &$group) {
                foreach ($group as &$ref) {
                    $this->keys[] = $ref["pKey"];
                    if ($goptions["show_links"]) {
                        $this->keyValues[] = "<a class=\"papercite_bibcite\" href=\"#paperkey_{$ref["papercite_id"]}\">{$ref["key"]}</a>";
                    } else {
                        $this->keyValues[] = $ref["key"];
                    }
                }
            }
        }


        // Process text in order to avoid some unexpected WordPress formatting
        return str_replace("\t", '  ', trim($r["text"]));
    }

    /**
     * This does two things:
     * -dynamically creates html form based on parameters (author and menutype)
     * -rebuilds command which is then sent as the bibtex command
     *
     * @param unknown $options The arguments
     * @return multitype:string The output of the bibfilter shortcode
     */
    function bibfilter($options)
    {
        // create form with custom types and authors
        global $post;

        $selected_author = false;
        $selected_type = false;

        $original_authors = Papercite::array_get($options, "author", "");
        $original_allow = Papercite::array_get($options, "allow", "");

        if (isset($_POST) && (papercite::array_get($_POST, "papercite_post_id", 0) == $post->ID)) {
            if (isset($_POST["papercite_author"]) && !empty($_POST["papercite_author"])) {
                $selected_author = ($options["author"] = $_POST["papercite_author"]);
            }

            if (isset($_POST["papercite_allow"]) && !empty($_POST["papercite_allow"])) {
                $selected_type = ($options["allow"] = $_POST["papercite_allow"]);
            }
        }

        $result = $this->getEntries($options);
        ob_start();
        ?>
        <form method="post" accept-charset="UTF-8">
            <input type="hidden" name="papercite_post_id" value="<?php echo $post->ID ?>">
            <table style="border-top: solid 1px #eee; border-bottom: solid 1px #eee; width: 100%">
                <tr>
                    <td>Authors:</td>
                    <td><select name="papercite_author" id="papercite_author">
                            <option value="">ALL</option>
                            <?php
                            $authors = preg_split("#\s*\\|\s*#", $original_authors);
                            if (Papercite::array_get($options, "sortauthors", 0)) {
                                sort($authors);
                            }

                            foreach ($authors as $author) {
                                print "<option value=\"" . htmlentities($author, ENT_QUOTES, "UTF-8") . "\"";
                                if ($selected_author == $author) {
                                    print " selected=\"selected\"";
                                }
                                print ">$author</option>";
                            }
                            ?>
                        </select></td>

                    <td>Type:</td>
                    <td><select name="papercite_allow" id="papercite_type">
                            <option value="">ALL</option>
                            <?php
                            $types = preg_split("#\s*,\s*#", $original_allow);
                            foreach ($types as $type) {
                                print "<option value=\"" . htmlentities($type, ENT_QUOTES, "UTF-8") . "\"";
                                if ($selected_type == $type) {
                                    print " selected=\"selected\"";
                                }
                                print ">" . papercite_bibtype2string($type) . "</option>";
                            }
                            ?>
                        </select></td>
                    <td><input type="submit" value="Filter"/></td>
                </tr>
            </table>
        </form>

        <?php

        $entries_output = $this->showEntries(
            $result,
            $options,
            $this->getBib2TplOptions($options),
            false,
            $options["bibtex_template"],
            $options["format"],
            "bibtex"
        );


        return ob_get_clean() . $entries_output;
    }


    function processTextualFootnotes(&$matches)
    {
        $post_id = "0";
        if (func_num_args() == 2) {
            $post_id = func_get_arg(1);
        }
        $ft_id = ++$this->textual_footnotes_counter;
        $this->textual_footnotes[$ft_id] = $matches[1];
        //d($this->textual_footnotes);
        return "<A href='#fn_".$post_id.'_'.$ft_id."'>(<sup>$ft_id</sup>)</A>";
    }


}
