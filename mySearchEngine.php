<?php 
header('Content-Type: text/html; charset=utf-8'); 
$limit = 10; 
include 'SpellCorrector.php';
$mySpellCorrector = new SpellCorrector();



$query = isset($_REQUEST['q']) ? $_REQUEST['q'] : false; 

$additionalParametersLucene = array( 'fl' => 'title,og_url,id,description', 'facet' => 'true' );
$additionalParametersPageRank = array( 'sort' => 'pageRankFile desc','fl' => 'title,og_url,id,description', 'facet' => 'true' );

$resultsLucene = false;
$resultsPageRank = false; 

?>

<html>
 <head>
  <title>PHP Solr Client Example</title> 
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
  <script>


$(document).ready(function(){
    $("#q").on('input',function(){
  

var q = $('#q').val();

    var dataListJQ = document.getElementById('json-datalist');
    var dataList = $('#json-datalist');
    var input = document.getElementById('q');

// Create a new XMLHttpRequest.
var request = new XMLHttpRequest();

// Handle state changes for the request.
request.onreadystatechange = function(response) {
  if (request.readyState === 4) {

    if (request.status === 200) {

      // Parse the JSON
      // alert(request.responseText);
        dataList.empty();

      var jsonOptions = JSON.parse(request.responseText);

      // Loop over the JSON array.
      jsonOptions.forEach(function(item) {
        // Create a new <option> element.
        var option = document.createElement('option');
        // Set the value using the item in the JSON array.
        option.value = item.value;
        // Add the <option> element to the <datalist>.

        dataListJQ.appendChild(option);
      });

      // Update the placeholder text.
      input.placeholder = "Enter a query";
    } else {
      // An error occured :(
      input.placeholder = "Couldn't load datalist options :(";
    }
  }
};

// Update the placeholder text.
input.placeholder = "Loading options...";

// Set up and make the request.
request.open('GET', 'autoComp.php?q='+q, true);
request.send();

    });
});

  
  </script>
</head> 
<body> 
  <form accept-charset="utf-8" method="POST">
      <label for="q">Search:</label> 
      <input id="q" name="q" type="text" list="json-datalist" value="<?php echo htmlspecialchars($query, ENT_QUOTES, 'utf-8'); ?>"/><br><br>
      <datalist name="json-datalist" id="json-datalist"></datalist>

      <input type="radio" name="pageranking" value="lucene" checked="checked" > Lucene
      <input type="radio" name="pageranking" value="pagerank" > PageRank<br><br>
      <input type="submit"/> 
    </form>

<?php
if ($query) { 
  require_once('solr-php-client-master/Apache/Solr/Service.php'); 
  $solr = new Apache_Solr_Service('localhost', 8983, '/solr/mySearchEngine/'); if (get_magic_quotes_gpc() == 1) { $query = stripslashes($query); 
  } 
   try 
   { 
    $result="";
    if(isset($_POST['pageranking']) && $_POST['pageranking']=='lucene') 
    {
        $result = $solr->search($query, 0, $limit,$additionalParametersLucene);  
    }
    else
    {
        $result = $solr->search($query,0,$limit,$additionalParametersPageRank);  
    }

$total = (int) $result->response->numFound;
if($total==0)
{
  $qWords=explode(" ", $query);
$corrected_Query="";
foreach ($qWords as $x) {
  $corrected_Query.=" ".$mySpellCorrector->correct($x);
}

if(isset($_POST['pageranking']) && $_POST['pageranking']=='lucene') 
    {
        $result = $solr->search($corrected_Query, 0, $limit,$additionalParametersLucene);  
    }
    else
    {
        $result = $solr->search($corrected_Query,0,$limit,$additionalParametersPageRank);  
    }
echo '<div> <i><b>No results found for : <u>', $query, '</u> <BR/> Showing results for : <u>',$corrected_Query,'</u></b></i></div>';  

$query=$corrected_Query;
}
// else
// {
    writeOutput($result,$query);  
// }

    
  } 
  catch (Exception $e) { 

   die("<html><head><title>SEARCH EXCEPTION</title><body><pre>{$e->__toString()}</pre></body></html>"); 
 }
} 
?> 
  
  

<?php 


function writeOutput($result,$query) 
{


$total = (int) $result->response->numFound;
$limit=10;

  $start = min(1, $total); 
  $end = min($limit, $total); 
  ?>
   <div>Results <?php echo $start; ?> - <?php echo $end;?> of <?php echo $total; ?>:</div> 
    
    <?php  
    foreach ($result->response->docs as $doc) 
      { 
        ?> 
          <table cellpadding="2" cellspacing="2" style="margin: 10px; border: 1px solid black; text-align: left" width=99% >
           <?php 
$id_con=file_get_contents($doc->id);
    


    // if(preg_match_all("(<p[^><]*?class=\"p-text\"[^><]*.*?<\/p>)", $id_con, $matches))
    // {
    //   $id_con="";
    //   foreach ($matches as $match) {
    //   $id_con.=$match[0];

    // }
    // }
    // else
      if
    (preg_match("/(<body[^><]*>.*?<\/body>)/is", $id_con, $match))
    {
      $id_con=$match[0];
    }
    $id_con=strip_tags ($id_con);


$sentenceArray = explode(" ", $id_con);
          

$retSentence="" ;          
foreach ($sentenceArray as $sentence) 
{
  $found=true;
  foreach (explode(" ", $query) as $qTerm)
  {
    if (!preg_match("/\b$qTerm\b/is", $sentence, $match)){
        $found=false;
    } 

  }

  if (!preg_match("/^\W*https?\b/is", $sentence, $match)){

  if($found==true)
  {
    $retSentence=$sentence;
    $sentenceArray=null;
  }
    }

}

echo('<tr><th>Title:</th><td><a href="'.$doc->og_url.'">'.$doc->title."</a></td></tr></body>");
echo('<tr><th>URL:</th><td><a href="'.$doc->og_url.'">'.$doc->og_url."</a></td></tr></body>");
echo('<tr><th>Description:</th><td>'.$doc->description."</td></tr>");
echo('<tr><th>Snippet:</th><td>'.$retSentence."</td></tr>");
      
  }
echo('</table>');

}

?>

       </body>
</html>
   
   