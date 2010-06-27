<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
       "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
  <title>MetaSearch 2010</title>
  <link type="text/css" rel="stylesheet" href="src/design.css">
  <meta content="text/html; charset=UTF-8" http-equiv="content-type">
  <meta name="robots" content="noindex">
  <link rel="icon" href="src/favicon.ico" type="image/x-icon">
  <link rel="shortcut icon" href="src/favicon.ico" type="image/x-icon"> 
</head>
<body>

<div id="header">
  <h1>MetaSearch 2010</h1>
  <div id="main-navigation">
    <b>Startseite</b> |
	<a href="server">Kommentar-&Uuml;bersicht</a>
  </div>
</div>

<div class="links">

<h1>Private Area</h1>

<p><a href="http://biokemika.uni-frankfurt.de/wiki/BioKemika:Metasearch/2010">MetaSearch 2010</a>
ist der Entwicklungsname der <a href="http://biokemika.uni-frankfurt.de/wiki/BioKemika:MetaSearch/2010">
neuen</a> <a href="http://biokemika.de/metasearch">MetaSearch</a>, und dies ist dazugehörige offizielle Website.
MetaSearch geht im Juni 2010 in die erste öffentliche Alpha-Phase. <b>Diese Seite ist derzeit
nicht für die Öffentlichkeit bestimmt</b>. Aktuelle Informationen, vor allem zur Planung,
gibt es unter
<b><a href="http://biokemika.uni-frankfurt.de/wiki/Benutzer:Sven/Web_annotating_Metasearch">Web Annotating MetaSearch</a></b>
in der BioKemika.</p>

<p>-- <a href="http://biokemika.de/sven">Sven</a></p>

<h2>Dokumente</h2>
<ul>
  <li><b>BioKemika goes Social: MetaSearch 2010</b> - Zusammenfassung der Konzepte der neuen MetaSearch
      &bull; <a href="dokumente/BioKemika MetaSearch 2010.pdf">PDF</a> &bull; <a href="dokumente/BioKemika MetaSearch 2010.odt">ODT</a>
  <li><b>Roadmap</b> - Planung der nächsten Wochen
      &bull; <a href="dokumente/Roadmap.pdf">PDF</a> &bull; <a href="dokumente/Roadmap.odt">ODT</a>
	  <br><i>nicht mehr aktuell zugunsten des folgenden Dokumentes</i>
  <li><b>Web Annotating MetaSearch</b> - Einführung, Diskussionsdokumentation, geklärte Fragen, Roadmap, Probleme
      &bull; <a href="http://biokemika.uni-frankfurt.de/wiki/Benutzer:Sven/Web_annotating_Metasearch">Online-Artikel</a>
</ul>

<h2>Links</h2>
<ul>
  <li><a href="http://biokemika.de/metasearch2010/">biokemika.de/metasearch2010</a> - Alte Umgebung (diese hier ist der Ersatz)
  <li><a href="http://biokemika.de/metasearch">MetaSearch auf BioKemika</a> mit viel Infos zur alten MetaSearch
</ul>
<ul>
  <li><a href="http://biokemika.uni-frankfurt.de/wiki/Portal:Datenbanksuche">Portal:Datenbanksuche</a>,
      Startpunkt der MetaSearch in der <a href="http://biokemika.de">BioKemika</a>.
  <li><a href="http://www.expasy.ch/viralzone/">ViralZone</a> als klassischer trivialer Testfall
</ul>

</div> <!-- links -->
<div class="rechts">

<div class="download">
  <h2>Downloaden und Ausprobieren!</h2>

<div style="float:left; margin: 0 1em 1em 0">
  <?php // ja, folgendes ist idiotisch:
    $conf = file('firefox/extension/latest.txt');
	$date = trim($conf[2]);
	$version = trim($conf[0]);
	$filename = 'firefox/browser/FirefoxPortable.zip';
    #$filename = 'download/' . rtrim($conf[0]);
	#$desc = $conf[1];
	$mb = round(filesize($filename) / 1024 / 1024);
  ?>
  <a class="download-button" href="<?=$filename; ?>">Browser herunterladen
  <span>Version <?=$version; ?></span>
  <span><?=$date.' ('.$mb.' MB)'; ?></span></a>
</div>

<p>Du möchtest MetaSearch einfach ausprobieren, ohne dabei etwas zu riskieren? Dann ist
der BioKemika-Browser das richtige für dich. Einfach runterladen und mit Doppelklick
starten. Und wenn es dir nicht gefällt, löschst du es einfach wieder.</p>

<p>Alternativ kannst du hier die Extension installieren, wenn du Firefox verwendest:
<p>

<a class="download-button" href="firefox/extension/metasearch-<?=$version; ?>.xpi">Extension installieren
 <span>Version <?=$version; ?></span>
 <span><?=$date; ?></span></a>

</div><!--download-->

<div class="download" style="margin-top: 1.5em">
   <h2>Trigger schreiben!</h2>
   
   <p>Der erste <a href="server/">Prototyp des MetaSearch-Servers</a> ist
      seit dem 22.05.2010 online.</p>
</div>

</div><!-- rechte spalte -->

<br style="clear:both;">


<h2>Screenshots</h2>
<p>Everybody loves Screenshots :-)  Hier gibt es einige Fotos vom Browser-Client,
die z.T. für Presse, etc. genutzt wurden. Den Server-Teil kann man
<a href="server">life ausprobieren</a>.

<p><?php

$liste = <<<LISTE
http://biokemika.de/metasearch2010/screenshots/01.%20Start.png
http://biokemika.de/metasearch2010/screenshots/02.%20Auf%20BioKemika.png
http://biokemika.de/metasearch2010/screenshots/03.%20NCBI...%20geht.png
http://biokemika.de/metasearch2010/screenshots/04.%20Viralzone%20traurig.png
http://biokemika.de/metasearch2010/screenshots/05.%20Viralzone%20gluecklich.png
http://svenk.homeip.net/metasearch2010/screenshots/winxp.png
LISTE;

foreach(explode("\n", $liste) as $e) {
	print "<a href='$e'><img src='$e' style='width:200px; margin: 10px; border-color: black;'></a>";
}

?>
<br clear="left"/>
<p>Siehe auch <a href="http://biokemika.uni-frankfurt.de/wiki/BioKemika:Maskottchen">BioKemika:Maskottchen</a>
für eine Übersicht über die vorhandenen BCs.</p>

<h2>Release Notes</h2>
<p>Siehe BioKemika-Mailingliste-Archive</p>

</body>
</html>
