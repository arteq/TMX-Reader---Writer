<?php
require __DIR__.'/../src/Tmx.php';

$file = 'out.tmx';

$tmx = new \ArteQ\CSX\Tmx($file, $create = true, $encoding = null, $debug = true);

$headerProps = [
	'xxx' => 123,
	'yyy' => 'zzz',
];
$tmx->setHeaderProperties($headerProps);


$tmx->set('id-123', 'pl_PL', 'tekst po polsku');
$tmx->set('id-123', 'en_EN', 'english text');
$tmx->setAttribute('id-123', 'changedate', date('Ymd\THis\Z') );
$tmx->setAttribute('id-123', 'creationdate', date('Ymd\THis\Z') );
$tmx->setAttribute('id-123', 'creationid', 'user-123');

$tmx->setProperty('id-123', 'client', 'ACME Ltd.');

$tmx->write();

$content = file_get_contents($file);
echo $content;

@unlink($file);