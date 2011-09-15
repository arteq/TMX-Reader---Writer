<?php
require_once '../inc/tmx.class.php';

/* 
 * Création
 */
$tmx = new Tmx('../demo/test2.tmx', true, null, true);
//remplissage
$tmx->setArray(
	array(
		array('test1', 'fr_FR', 'Texte'),
		array('test2', 'fr_FR', 'Texte 2')
	)
);
//sauvegarde
$tmx->write();

/* 
 * Lecture
 */
echo '<pre>';
//Valeur selon id et langue
print_r($tmx->get('test1', 'fr_FR'));
echo '<br />';
//Traductions selon id
print_r($tmx->get('test1'));
//Traductions selon langue
print_r($tmx->getLang('fr_FR'));
echo '</pre>';

/* 
 * Modification
 */
$tmx = new Tmx('../demo/test2.tmx');
$tmx->set('test3', 'fr_FR', 'Texte 3');
$tmx->write();
echo '<pre>';print_r($tmx->get());echo '</pre>';
