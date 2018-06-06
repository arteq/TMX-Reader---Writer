<?php
/**
 * Reader et Writer de fichiers de TMX en PHP
 *
 * La classe permet de lire, de modifier et créer un fichier de traduction au format TMX en PHP
 *
 * @author     Artur Grącki <arteq@arteq.org>
 * @version    1.1.0
 * @link       https://github.com/arteq/TMX-reader-writer
 * 
 * @author     Maxime Maupeu <maxime.maupeu@gmail.com>
 * @version    1.0
 * @link       https://github.com/Stormfaint
 */

namespace ArteQ\CSX;

class Tmx 
{
	/**
     * Nom du fichier TMX
     *
     * @var string
     */
	private $_file = null;

	/**
     * Traductions extraites de fichier TMX
     *
     * @var array
     */
	private $_data = array();
	
	/**
	 * Configure options
	 * @var array
	 */
	private $_config = [
		'adminlang' => 'en',
		'creationtool' => 'TMX reader-writer',
		'creationtoolversion' => '1.1.0.',
		'datatype' => 'xml',
		'o-tmf' => 'XLIFF',
		'segtype' => 'block',
	];

	/**
     * Contexte : fichier existant ou non (création)
     *
     * @var boolean
     */
	private $_creation = false;

	/**
     * Mode debug (retour d'exceptions)
     *
     * @var boolean
     */
	private $_debug = false;

	/**
     * Version par défaut d'XML utilisée
     */
	const DOCUMENT = '1.0';

	/**
     * Version par défaut de TMX utilisé
     */
	const VERSION = '1.4';

	/**
     * Encodage par défaut du fichier
     */
	const ENCODAGE = 'UTF-8';

	/**
     * Sauvegarde d'un backup du fichier avant modification
     */
	const BACKUP = true;

	/**
     * Sauvegarde réalisée à chaque modification à l'aide d'un timestamp
     */
	const MULTIBACKUP = false;

	/**
     * Constructeur
     *
     * @param  string $file
     * @param  string $srcLang
     * @param  boolean $create
	 * @param  null|string $encodage
     * @param  boolean $debug
     * @throws Exception si l'extension libxml n'est pas activée dans PHP, nécessaire pour XMLReader et XMLWriter
     * @throws Exception si le fichier n'est accessible en écriture
     * @throws Exception si le répertoire lors de la création d'un fichier n'est pas accessible en écriture
     * @throws Exception si le fichier n'existe pas
     * @return boolean
     */
	public function __construct($file, $create = false, $encodage = null, $debug = false, $config = []){
		$this->_debug = $debug;
		$this->_config = array_merge($this->_config, $config);

		if(!class_exists('XMLReader') || !class_exists('XMLWriter')){
			if($this->_debug)
				throw new Exception('PHP extension libxml is required : http://www.php.net/manual/fr/book.libxml.php');
			else return false;
		}
		if(file_exists($file)){
			if(!is_readable($file)){
				if($this->_debug)
					throw new Exception('File exist but not readable.');
				return false;
			}
			$this->_file = $file;
			$this->read($encodage);
		}elseif($create){
			if(!$parent = trim(substr_replace($file . ' ', '', strripos($file, '/'), -1))){
				$parent = dirname(__FILE__);
			}
			if(!is_writable($parent)){
				if($this->_debug)
					throw new Exception('Directory exist but not writable.');
				return false;
			}
			 @fopen($file, 'w');
			@chmod($this->_file, 0755);
			$this->_file = $file;
			$this->_creation = true;
		}else{
			if($this->_debug)
				throw new Exception('File not exist.');
			return false;
		}
		return true;
	}

	/**
     * Méthode permettant de lire le fichier et charger les traductions
     *
     * @param  null|string $encodage
     * @throws Exception si le fichier n'existe pas
     * @return Tmx
     */
	private function read($encodage = null){
		if($encodage === null) $encodage = self::ENCODAGE;
		if($this->_file === null){
			if($this->_debug)
				throw new Exception('No file.');
			else return false;
		}
		$reader = new XMLReader();
		$reader->open($this->_file, $encodage);
		while($reader->read()){
			if($reader->nodeType == XMLReader::ELEMENT){
				switch($reader->localName){
					case 'tu': $tuid = $reader->getAttribute('tuid'); break;
					case 'tuv': $xmlLang = $reader->xmlLang; break;
					case 'seg':
						if($reader->read()){
							if(
								($reader->nodeType == XMLReader::TEXT || $reader->nodeType == XMLReader::CDATA)
								&& $tuid && $xmlLang
							){
								$this->_data[$tuid][$xmlLang] = $reader->value;
							}
						}
					break;
				}
			}
		}
		$reader->close();
		return $this;
	}

	/**
	 * Méthode d'écrire dans un fichier TMX et de l'enregistrer
     *
     * @param  null|string $encodage
     * @throws Exception si le fichier n'existe pas
     * @return Tmx
     */
	public function write($encodage = null){
		if($this->_file === null){
			if($this->_debug)
				throw new Exception('No file.');
			else return false;
		}
		if($encodage === null) $encodage = self::ENCODAGE;
		$writer = new XMLWriter();
		$writer->openMemory();
		$writer->startDocument(self::DOCUMENT, $encodage);
		$writer->startElement('tmx');
		$writer->writeAttribute('version', self::VERSION);
		$writer->setIndentString("\t");
		$writer->setIndent(true);
		$writer->startElement('header');
		foreach ($this->_config as $key => $value)
		{
			$writer->writeAttribute($key, $value);			
		}		
		$writer->endElement();
		$writer->startElement('body');
		foreach($this->_data as $tuid => $tuvs){
			$writer->startElement('tu');
			$writer->writeAttribute('tuid', $tuid);
			if (isset($tuvs['_attributes']))
			{
				foreach ($tucs['_attributes'] as $attrName => $attrValue)
				{
					$writer->writeAttribute($attrName, $attrValue);
				}
				unset($tuvs['_attributes']);
			}
			foreach($tuvs as $xmlLang => $value){
				$writer->startElement('tuv');
				$writer->writeAttribute('xml:lang', $xmlLang);
				$writer->writeElement('seg', $value);
				$writer->endElement();
			}
			$writer->endElement();
		}
		$writer->endElement();
		$writer->endElement();
		$writer->endDocument();
		if(self::BACKUP && $this->_creation === false){
			$copy = $this->_file . '.bak';
			if(self::MULTIBACKUP) $copy .= '.' . time();
			@copy($this->_file, $copy);
		}
		$file = @fopen($this->_file, 'w');
		@fwrite($file, $writer->outputMemory(true));
		return $this;
	}

	/**
	 * Méthode permettant d'ajouter une traduction
     *
     * @param  string $tuid
     * @param  string $xmlLang
	 * @param  string $value
     * @return Tmx
     */
	public function set($tuid, $xmlLang = false, $value = false){
		if(is_array($tuid)) return $this->setArray($tuid);
		if($xmlLang != false && $value != false) $this->_data[$tuid][$xmlLang] = $value;
		return $this;
	}

	/**
	 * Add additional attributes to 'tu' element
	 * 
	 * @param string $tuid
	 * @param string $name
	 * @param string $value
	 * @throws Exception when no 'tu' element in data array for given $tuid
	 */ 
	public function setAttribute($tuid, $name, $value)
	{
		if (!isset($_data[$tuid]))
			throw new Exception('No such tuid element.');

		$_data[$tuid]['_attributes'][$name] = $value;
	}

	/**
	 * Méthode permettant d'ajouter une ou plusieurs traductions à l'aide d'un tableau
     *
     * @param  array $data
     * @return Tmx
     */
	public function setArray(array $data){
		foreach($data as $_data){
			if(is_array($_data)){
				if(count($_data) > 2){
					$this->set($_data[0], $_data[1], $_data[2]);
				}
			}
		}
		return $this;
	}

	/**
	 * Méthode permettant de supprimer une traduction
     *
     * @param  string $tuid
     * @param  string $xmlLang
     * @return Tmx
     */
	public function delete($tuid, $xmlLang = false){
		if($xmlLang){
			if(isset($this->_data[$tuid]) && isset($this->_data[$tuid][$xmlLang])){
				unset($this->_data[$tuid][$xmlLang]);
				if(empty($this->_data[$tuid])){
					unset($this->_data[$tuid]);
				}
			}
		}else{
			if(isset($this->_data[$tuid])){
				unset($this->_data[$tuid]);
			}
		}
		return $this;
	}

	/**
	 * Méthode permettant de récupérer les traductions d'un fichier préalablement chargé,
	 * selon un identifiant, un identifiant et une langue, ou l'ensemble d'un fichier
     *
     * @param  boolean|string $tuid
     * @param  boolean|string $xmlLang
     * @return boolean|string|array
     */
	public function get($tuid = false, $xmlLang = false){

		if($xmlLang && $tuid){
			if(array_key_exists($tuid, $this->_data)){
				if(array_key_exists($xmlLang, $this->_data[$tuid])){
					return $this->_data[$tuid][$xmlLang];
				}
				return false;
			}
			return false;
		}
		if($tuid){
			if(array_key_exists($tuid, $this->_data)){
				return $this->_data[$tuid];
			}
			return false;
		}
		return $this->_data;
	}

	/**
	 * Méthode permettant de récupérer l'ensemble des traductions en une langue donnée
     *
     * @param  string $xmlLang
     * @return array
     */
	public function getLang($xmlLang){
		$data = $this->_data;
		foreach($data as $_tuid => $_data){
			foreach($_data as $_xmlLang => $_value){
				if($_xmlLang != $xmlLang){
					unset($data[$_tuid][$_xmlLang]);
				}
			}
		}
		return $data;
	}
}
