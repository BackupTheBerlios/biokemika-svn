<?php
  // Metasearch update extension feed
  header('Content-Type: text/xml');
  print '<?xml version="1.0"?>';

  $data = file('latest.txt');
  $version = trim($data[0]);
  $update_link = "http://svenk.homeip.net/metasearch2010/firefox/extension/metasearch-$version.xpi";
?>
<r:RDF xmlns:r="http://www.w3.org/1999/02/22-rdf-syntax-ns#"
       xmlns="http://www.mozilla.org/2004/em-rdf#">
<!-- MetaSearch Extension -->
<r:Description about="urn:mozilla:extension:metasearch@biokemika.de">
  <updates>
    <r:Seq>
      <r:li>
        <r:Description>
          <version><?=$version; ?></version>
          <targetApplication>
            <r:Description>
              <id>{ec8030f7-c20a-464f-9b0e-13a3a9e97384}</id> <!-- firefox uuid -->
              <minVersion>0.8</minVersion>
              <maxVersion>1.9</maxVersion>
              <updateLink><?=$update_link; ?></updateLink>
            </r:Description>
          </targetApplication>
        </r:Description>
      </r:li>
    </r:Seq>
  </updates>
  <version><?=$version; ?></version>
  <updateLink><?=$update_link; ?></updateLink>
</r:Description>
</r:RDF>
