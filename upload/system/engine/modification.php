<?php
class Modification {
	private $path;
	private $data = array();
	
	public function __construct() {
		$this->path = realpath(str_replace('\'', '/', dirname(__FILE__)) . '/../../') . '/';
	}
	
	public function getFile($filename) {
		$file = DIR_MODIFICATION . str_replace('/', '_', substr($filename, strlen($this->path)));
		
		if (file_exists($file)) {
			return $file;
		} else {
			return $filename;
		}
	}
		
	public function addModification($xml) {
		$this->data[] = $xml;
	}
			
	public function load($filename) {
		if (file_exists($filename)) {
			$xml = file_get_contents($filename);

			$this->addModification($xml);
		} else {
			trigger_error('Error: Could not load modification ' . $filename . '!');
			exit();
		}
	}

	public function write() {
		$modification = array();
		
		foreach ($this->data as $xml) {
			$dom = new DOMDocument('1.0', 'UTF-8');
			$dom->loadXml($xml);
			
			$files = $dom->getElementsByTagName('modification')->item(0)->getElementsByTagName('file');		
			
			foreach ($files as $file) {
				$files = glob($this->path . $file->getAttribute('name'));
				$operations = $file->getElementsByTagName('operation');
				
				if ($files) {
					foreach ($files as $file) {
						if (!isset($modification[$file])) {
							$modification[$file] = file_get_contents($file);
						}
						
						foreach ($operations as $operation) {
							$search = $operation->getElementsByTagName('search')->item(0)->nodeValue;
							$index = $operation->getElementsByTagName('search')->item(0)->getAttribute('index');
							$add = $operation->getElementsByTagName('add')->item(0)->nodeValue;
							$position = $operation->getElementsByTagName('add')->item(0)->getAttribute('position');
								
							if (!$index) {
								$index = 1;
							}
							
							switch ($position) {
								default:
								case 'replace':
									$replace = $add;
									break;
								case 'before':
									$replace = $add . $search;
									break;
								case 'after':
									$replace = $search . $add;
									break;
							}
			
							$i = 0;
							$pos = -1;
							$result = array();
			
							while (($pos = strpos($modification[$file], $search, $pos + 1)) !== false) {
								$result[$i++] = $pos; 
							}
							
							// Only replace the occurance of the string that is equal to the index					
							if (isset($result[$index - 1])) {
								$modification[$file] = substr_replace($modification[$file], $replace, $result[$index - 1], strlen($search));
							}
						}
					}
				}
			}
		}
		
		// Write all modification files
		foreach ($modification as $key => $value) {
			/*
			$path = '';
			
			$directories = explode('/', dirname(str_replace('../', '', $key)));
			
			foreach ($directories as $directory) {
				$path = $key . '/' . $directory;
				
				if (!file_exists(DIR_MODIFICATION . $path)) {
					@mkdir(DIR_MODIFICATION . $path, 0777);
				}		
			}
			*/
						
			$file = DIR_MODIFICATION . str_replace('/', '_', substr($key, strlen($this->path)));
			
			$handle = fopen($file, 'w');
	
			fwrite($handle, $value);
	
			fclose($handle);			
		}
	}
	
	public function clear() {
		$files = glob(DIR_MODIFICATION . '{*.php,*.tpl}', GLOB_BRACE);

		if ($files) {
			foreach ($files as $file) {
				if (file_exists($file)) {
					unlink($file);
				}
			}
		}
	}
}
?>