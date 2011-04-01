<?php
$translations = array(
  // groupkey if no grouping is done
  'all' => 'All',
  // Month names entries are compared against for sorting issues
  'months' => array('01' => 'january', '02' => 'february',
                    '03' => 'march', '04' => 'april',
                    '05' => 'may', '06' => 'june',
                    '07' => 'july', '08' => 'august',
                    '09' => 'september', '10' => 'october', 
                    '11' => 'november', '12' => 'december'),
                    
  // Representations of entry types used as headlines
  'entrytypes' => array('article'       => 'Articles',
                        'book'          => 'Books',
                        'booklet'       => 'Booklets',
                        'conference'    => 'Conference Papers',
                        'inbook'        => 'In Books',
                        'incollection'  => 'In Collections',
                        'inproceedings' => 'In Proceedings',
                        'manual'        => 'Manuals',
                        'mastersthesis' => 'Master\'s Theses',
                        'misc'          => 'Miscellaneous',
                        'phdthesis'     => 'Dissertations',
                        'proceedings'   => 'Proceedings',
                        'techreport'    => 'Technical Reports',
                        'unpublished'  => 'Unpublished',

                        // Map non-standard types to this type
                        'unknown'       => 'misc')
);
?>
