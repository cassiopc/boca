<?php
$simple = file_get_contents('../doc/test.dat');
$p = xml_parser_create();
xml_parser_set_option($p, XML_OPTION_CASE_FOLDING, 1);
xml_parser_set_option($p, XML_OPTION_SKIP_WHITE, 1);
xml_parse_into_struct($p, $simple, $vals, $index);
xml_parser_free($p);
echo "Index array\n";
print_r($index);
echo "\nVals array\n";
print_r($vals);

$aa = "aaa";
$$aa = "a";
echo "==>" . $aa . " " . $aaa . "\n";
?>
