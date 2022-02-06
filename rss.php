<?php

// HOST
$host = "https://[YOUR DOMAIN].[TLD]/";

// BLOG TITLE
$title = "[TITLE OF YOUR RSS PAGE]";

// BLOG FOLDER
$folder = "blog";

// IGNORE
$ignore = array('.git', 'node_modules', '_assets', '_mixins', '_snippets', '_drafts');

// ----------------------------------------------------------------------------------

$directory = $_SERVER["CONTEXT_DOCUMENT_ROOT"] . "//" . $folder . "//";

$filter = function ($file, $key, $iterator) use ($ignore) {
    if ($iterator->hasChildren() && !in_array($file->getFilename(), $ignore)) {
        return true;
    }
    return $file->isFile();
};

$crawlDir = new RecursiveDirectoryIterator(
    $directory,
    RecursiveDirectoryIterator::SKIP_DOTS
);

$crawlFile = new RecursiveIteratorIterator(
    new RecursiveCallbackFilterIterator($crawlDir, $filter)
);

$directory .= "\\";
$feed = array($host);

// BUILD RSS FEED
foreach ($crawlFile as $path => $file) {

    $relPath = substr($file, strlen($directory)-1, strlen($file));

    if (strpos($relPath, '\\')) {
      
      $convertPath = str_replace("\\", "/", substr($relPath, 0, strripos($relPath, '\\')) . "\\");
      $relFolderPath = $host . $folder . "/" . $convertPath;
      array_push($feed, $relFolderPath);
    }
}

$feed = array_unique($feed);
sort($feed);
array_shift($feed);

// BUILD XML HEADER
$xml  = "<?xml version='1.0' encoding='UTF-8'?>\n";
$xml .= "<rss version='2.0' xmlns:atom='http://www.w3.org/2005/Atom'>\n\n";
$xml .= "<channel>\n";
$xml .= "\t<atom:link href='" . $host . "rss.xml' rel='self' type='application/rss+xml' />\n";

// SET RSS FEED HEADER
$xml .= "\t<title>" . $title . "</title>\n";
$xml .= "\t<link>" . $host . $folder . "/</link>\n";
$xml .= "\t<image>";
$xml .= "\t\t<title>" . $title . "</title>\n";
$xml .= "\t\t<url>" . "[ABSOLUTE URL TO SHARE IMAGE]" . "</url>\n";
$xml .= "\t\t<link>" . $host . $folder . "/</link>\n";
$xml .= "\t</image>\n";
$xml .= "\t<description></description>\n";
$xml .= "\t<language>de-de</language>\n";

// LOOP RSS FEED
foreach($feed as $page) {

  // GET META CONTENT BY ACTAULLY CRAWLING THE PAGE
  $relPath = substr($page, strlen($host), strlen($page)) . 'index.html';
  $title = '';
  $description = '';

  $html = file_get_contents($relPath);
  //Create a new DOM document
  $dom = new DOMDocument();

  //Parse the HTML. The @ is used to suppress any parsing errors
  //that will be thrown if the $html string isn't valid XHTML.
  //@$dom->loadHTML($html);
  //@$dom->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
  @$dom->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'ISO-8859-1'));

  //Get all links. You could also use any other tag name here,
  //like 'img' or 'table', to extract other tags.
  $metas = $dom->getElementsByTagName('meta');

  //Iterate over the extracted links and display their URLs
  foreach ($metas as $meta){

    if ($meta->getAttribute('property') === 'og:title') {
      $title =  $meta->getAttribute('content');
    }

    if ($meta->getAttribute('property') === 'og:description') {
      $description = $meta->getAttribute('content');
    }
  }
  
  $xml .= "\t<item>\n";
  $xml .= "\t\t<title>" . utf8_decode($title) . "</title>\n";
  $xml .= "\t\t<link>" . $page . "</link>\n";
  $xml .= "\t\t<guid>" . $page . "</guid>\n";
  $xml .= "\t\t<description>" . htmlspecialchars(utf8_decode($description)) . "</description>\n";
  $xml .= "\t</item>\n";
}

// FINALIZE XML
$xml .= "</channel>\n";
$xml .= "</rss>";

// WRITE RSS FEED
$feed = fopen("rss.xml", "w") or die("No rss.xml!");
fwrite($feed, $xml);
fclose($feed);

// SHOW RSS FEED
header("Content-type: text/xml; charset=utf-8");
echo $xml;
