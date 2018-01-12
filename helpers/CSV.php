<?php
/**
 * The Cart CSV helper
 *
 * @package Omeka\Plugins\Cart
 */

class Cart_CSV {

	private $_csv;
	private $lines;
	private $labels;

	public function __construct($items) {
		$this->_csv = [];
		$this->_labels = [];
		$this->_lines = [];

		// Clean data
		foreach($items as $item) {
			if (get_class($item) == "Item") {
				$this->_sanitizeItem($item);
			}
		}

		$this->_createCSVArray();

		// return csv to download
		$this->_render();
	}

	private function _createCSVArray() {
		array_push($this->_csv, $this->_labels);

		foreach ($this->_lines as &$line) {
			$csvLine = array();
			foreach ($this->_labels as &$label) {
				$csvLine[$label] = array_key_exists($label, $line) ? $line[$label] : '';
			}
			array_push($this->_csv, $csvLine);
		}
	}

	/**
	 * Update labels list if necessary
	 * Add sanitized line to lines list 
	 * @param Item $item The item object
	 */
	protected function _sanitizeItem($item) {
		$line = [];

		// Retrieve elements texts
		$elements = all_element_texts($item, array('return_type' => 'array'));

		// Add identifiers to all_element_texts() results
		$identifiers = metadata($item, array("Dublin Core", "Identifier"), array("all" => true));
		$elements['Dublin Core']['Identifier'] = $identifiers;

		// Display element texts
		foreach ($elements as $elementSetName => $elementTexts) {
			foreach ($elementTexts as $elementName => $elementsText) {
				foreach ($elementsText as $element) {
					$label = str_replace('PDF:', '', __('PDF:'.$elementName));
					$line[$label] = $this->_getValue($element);
					if (!in_array($label, $this->_labels)) {
						array_push($this->_labels, $label);
					}
				}
			}
		}
		array_push($this->_lines, $line);
	}


	/**
	 * Render CSV
	 */
	protected function _render() {
		$res = strtolower(current_user()->name);
		$res = preg_replace('/[^[:alnum:]]/', ' ', $res);
		$filename = 'cart-' . trim($res, '-') . date('-Y-m-d-H-s') . '.csv';

		header("Content-Type: application/csv");
		header("Content-Transfer-Encoding: UTF-8");
		header("Content-Disposition: attachment; filename=" . $filename);
		header('Pragma: no-cache');
		header("Expires: 0");

		$outstream = fopen("php://output", "w");
		foreach($this->_csv as $line) {
			fputcsv($outstream, $line);
		}
	
		fclose($outstream);
		exit;
	}

	/**
	 * Prevent encoding issues
	 */
	protected function _getValue($value)
	{
		if (strlen(trim($value))) {
			$value = strip_tags($value);
			$value = iconv("UTF-8", "ISO-8859-1//TRANSLIT", $value); // Prevent MS-Word copy/paste
		}
		return $value;
	}
}