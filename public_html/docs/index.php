<?php
// Markdown parsing libraries
require_once('parsedown/Parsedown.php');
require_once('parsedown-extra/ParsedownExtra.php');

// Get the docs markdown sources in order
require_once("../../Spyc.php");
$md = file_get_contents('../../multiqc/docs/README.md');
$pages = [];
$md_parts = explode('---', $md, 3);
if(count($md_parts) == 3){
  $pages = spyc_load($md_parts[1]);
}
if(count($pages) == 0){ die("Error - couldn't find documentation source."); }

// Get the serach patterns
$search_patterns = spyc_load_file('../../multiqc/multiqc/utils/search_patterns.yaml');

// Loop over the markdown files and build the HTML content
$content = '';
foreach (array_keys($pages) as $section) {
  $sid = strtolower(str_replace(' ', '-', $section));
  $content .= '<div class="docs_section">'."\n".'<h1 class="section-header" id="'.$sid.'"><a href="#'.$sid.'" class="header-link"><span class="glyphicon glyphicon-link"></span></a>'.$section."</h1>\n";
  // Module documentation
  if($section == 'MultiQC Modules'){
    foreach ($pages[$section] as $subsect_name => $subsection){
      $content .= '<div class="docs_block modules_block" id="'.strtolower(str_replace(' ', '-', $subsect_name)).'-section"><h1>'.$subsect_name.'</h1>';
      foreach ($subsection as $name => $fn){
        $md = file_get_contents('../../multiqc/docs/'.trim($fn));
        $md_parts = explode('---', $md, 3);
        $mod_yaml = spyc_load($md_parts[1]);
        $content .= '<h2>'.$mod_yaml['Name'].'</h2>';
        $content .= '<p class="mod_ext_link"><a href="'.$mod_yaml['URL'].'" target="_blank">'.$mod_yaml['URL'].'</a></p>';
        $markdown = $md_parts[2];
        $pd = new ParsedownExtra();
        $content .= $pd->text($markdown);
        // Find matching search patterns
        $matched_sp = array();
        $modkey = str_replace('.md', '', str_replace('modules/', '', $fn));
        foreach($search_patterns as $key => $sp){
          if(preg_match("/^".$modkey."(\/|$)/i", $key)){
            $matched_sp[$key] = $sp;
          }
        }
        if(count($matched_sp) > 0){
          $content .= '
          <div class="panel panel-default search-patterns-panel">
            <div class="panel-heading">
                '.$mod_yaml['Name'].' file search patterns
                <a href="#module-search-patterns">See docs <i class="fa fa-chevron-up"></i></a>
            </div>
            <pre class="panel-body"><code class="language-yaml">' . spyc_dump($matched_sp) . '</code></pre>
          </div>';
        }
      }
      $content .= '</div>';
    }
  }
  // Core documentation
  else {
    foreach ($pages[$section] as $name => $fn){
      if(basename($fn) == 'README.md'){ continue; }
      $md = file_get_contents('../../multiqc/docs/'.trim($fn));
      $pd = new ParsedownExtra();
      $content .= '<div class="docs_block" id="'.basename($fn).'">' . $pd->text($md) . '</div>';
    }
  }
  $content .= '</div>';
}

// Add ID attributes to headers
$hids = Array();
$content = preg_replace_callback(
  '~<h([1234])>([^<]*)</h([1234])>~Ui', // Ungreedy by default, case insensitive
  function ($matches) {
    global $hids;
    $id_match = strtolower( @preg_replace('/[^\w\-\.]/', '', str_replace(' ', '-', $matches[2])));
    $id_match = str_replace('---', '-', $id_match);
    $hid = $id_match;
    $i = 1;
    while(in_array($hid, $hids)){
      $hid = $id_match.'-'.$i;
      $i += 1;
    }
    $hids[] = $hid;
    return '<h'.$matches[1].' id="'.$hid.'"><a href="#'.$hid.'" class="header-link"><span class="glyphicon glyphicon-link"></span></a>'.$matches[2].'</h'.$matches[3].'>';
  },
  $content);

// Build the ToC
$toc = '';
$curr_level = 0;
$id_regex = "~<h([123])([^>]*)id\s*=\s*['\"]([^'\"]*)['\"][^>]*>(.*)</h[123]>~Ui";
preg_match_all($id_regex, $content, $matches, PREG_SET_ORDER);
if($matches){
  foreach($matches as $match){
    $level = $match[1];
    $class = $match[2];
    if(!strpos($class, 'section-header')){
      $level += 1;
    }
    $id = $match[3];
    $name = str_replace('&nbsp;','', htmlentities(strip_tags($match[4]) ));
    if($level > $curr_level){
      $toc .= "\n".'<ul class="nav nav-stacked">'."\n";
    } else if($level == $curr_level) {
      $toc .= "</li>\n";
    } else {
      while($level < $curr_level){
        $toc .= "</li>\n</ul>\n</li>\n";
        $curr_level -= 1;
      }
    }
    $curr_level = $level;
    $toc .= '<li><a href="#'.$id.'">'.$name.'</a>';
  }
}
while($curr_level > 0){
  $toc .= '</li></ul>';
  $curr_level -= 1;
}

?><!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Documentation: MultiQC</title>
    <meta name="description" content="">
    <meta name="author" content="">
    <link rel="apple-touch-icon" sizes="57x57" href="../apple-icon-57x57.png">
    <link rel="apple-touch-icon" sizes="60x60" href="../apple-icon-60x60.png">
    <link rel="apple-touch-icon" sizes="72x72" href="../apple-icon-72x72.png">
    <link rel="apple-touch-icon" sizes="76x76" href="../apple-icon-76x76.png">
    <link rel="apple-touch-icon" sizes="114x114" href="../apple-icon-114x114.png">
    <link rel="apple-touch-icon" sizes="120x120" href="../apple-icon-120x120.png">
    <link rel="apple-touch-icon" sizes="144x144" href="../apple-icon-144x144.png">
    <link rel="apple-touch-icon" sizes="152x152" href="../apple-icon-152x152.png">
    <link rel="apple-touch-icon" sizes="180x180" href="../apple-icon-180x180.png">
    <link rel="icon" type="image/png" sizes="192x192"  href="../android-icon-192x192.png">
    <link rel="manifest" href="../manifest.json">
    <meta name="msapplication-TileColor" content="#ffffff">
    <meta name="msapplication-TileImage" content="../ms-icon-144x144.png">
    <meta name="theme-color" content="#ffffff">

    <link href="../css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.4.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="../fonts/font-mfizz.css">
    <link rel="stylesheet" href="../css/code_highlighting/github.css">
    <link rel="stylesheet" href="../css/styles.css">

    <!-- HTML5 shim and Respond.js for IE8 support of HTML5 elements and media queries -->
    <!--[if lt IE 9]>
      <script src="https://oss.maxcdn.com/html5shiv/3.7.2/html5shiv.min.js"></script>
      <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
    <![endif]-->
  </head>

  <body id="mqc_docs" data-spy="scroll" data-target=".toc" data-offset="100">

    <div class="header">

      <!-- Static navbar -->
      <nav class="navbar navbar-default navbar-inverse navbar-static-top">
        <div class="container">
          <div class="navbar-header">
            <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#navbar" aria-expanded="false" aria-controls="navbar">
              <span class="sr-only">Toggle navigation</span>
              <span class="icon-bar"></span>
              <span class="icon-bar"></span>
              <span class="icon-bar"></span>
            </button>
            <a class="navbar-brand visible-xs" href="#">MultiQC</a>
          </div>
          <div id="navbar" class="navbar-collapse collapse">
            <ul class="nav navbar-nav navbar-right">
              <li><a href="../">Home</a></li>
              <li class="active"><a href="#">Docs</a></li>
              <li><a href="../plugins.html">Plugins</a></li>
              <li><a href="../logos">Logo</a></li>
              <li class="dropdown">
                <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">Example Reports <span class="caret"></span></a>
                <ul class="dropdown-menu">
                  <li class="dropdown-header">Analysis Types</li>
                  <li><a href="../examples/rna-seq/multiqc_report.html">RNA-Seq</a></li>
                  <li><a href="../examples/wgs/multiqc_report.html">Whole Genome</a></li>
                  <li><a href="../examples/bs-seq/multiqc_report.html">Bisulfite</a></li>
                  <li><a href="../examples/hi-c/multiqc_report.html">Hi-C</a></li>
                  <li class="dropdown-header">MultiQC Customisation</li>
                  <li><a href="../examples/ngi-rna/Test_NGI_Project_multiqc_report.html">MultiQC_NGI Plugin</a></li>
                </ul>
              </li>
            </ul>
          </div><!--/.nav-collapse -->
        </div>
      </nav>
      <div class="container">
        <div class="row">
          <div class="col-sm-6">
            <object type="image/svg+xml" title="MultiQC" data="../images/Docs_logo.svg">
              <img src="../images/Docs_logo.png" title="MultiQC">
            </object>
          </div>
          <div class="col-sm-6" style="margin-top:40px;">
            <p class="lead">Welcome to the MultiQC docs.</p>
            <p>These docs are bundled with the MultiQC download for your convenience,
               so you can also read in your installation or on <a href="https://github.com/ewels/MultiQC/tree/master/docs">Github</a>.
            </p>
          </div>
        </div>
      </div>
    </div>

    <div class="container docs-container">
      <div class="row">
        <div class="col-sm-3 col-md-push-9" id="toc_column">
          <h3 id="toc_header">Table of Contents</h3>
          <div class="toc" data-spy="affix" data-offset-top="254">
            <?php echo $toc; ?>
            <p class="backtotop"><a href="http://gitter.im/ewels/MultiQC">Discuss on Gitter</a></p>
            <p class="backtotop"><a href="#">Back to top</a></p>
          </div>
        </div>
        <div class="col-sm-9 col-md-pull-3">
          <?php echo $content; ?>
        </div>
      </div>
    </div> <!-- /container -->

    <footer id="footer">
      <div class="container">
        <p>Created by Phil Ewels:
          <a target="_blank" href="https://github.com/ewels"><i class="fa fa-github"></i> ewels</a> |
          <a target="_blank" href="https://twitter.com/tallphil"><i class="fa fa-twitter"></i> tallphil</a> |
          <a target="_blank" href="https://se.linkedin.com/in/philewels">LinkedIn</a> |
          <a target="_blank" href="https://www.researchgate.net/profile/Philip_Ewels">ResearchGate</a>
        </p>
        <a target="_blank" href="https://seqera.io/" class="seqera-footer">
          <img src="../images/seqera-logo.png">
        </a>
      </div>
    </footer>


    <!-- Placed at the end of the document so the pages load faster -->
    <script src="../js/jquery-1.11.3.min.js"></script>
    <script src="../js/bootstrap.min.js"></script>
    <script src="../js/highlight.pack.js"></script>
    <script>hljs.initHighlightingOnLoad();</script>
    <script src="../js/docs.js"></script>

  <!-- Google Analytics -->
  <script>
    (function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
    (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
    m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
    })(window,document,'script','//www.google-analytics.com/analytics.js','ga');

    ga('create', 'UA-68098153-1', 'auto');
    ga('send', 'pageview');

  </script>

  </body>
</html>
