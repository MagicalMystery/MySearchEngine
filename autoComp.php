<?php

if (isset($_REQUEST['q'])) {
    $query = $_REQUEST['q'];


  $qWords=explode(" ", $query);
  $query=array_pop($qWords);
  $origQuery=join(" ",$qWords);


$solr_req = "http://localhost:8983/solr/mySearchEngine/suggest?q=".$query;
$conJSON=json_decode(file_get_contents($solr_req));
$suj= $conJSON->suggest->suggest->$query->suggestions;

            $array = array();

foreach ($suj as $row) {
        $array[] = array (
            'value' => trim($origQuery." ".$row->term),
        );
    }
    //RETURN JSON ARRAY
    echo json_encode ($array);

}
?>