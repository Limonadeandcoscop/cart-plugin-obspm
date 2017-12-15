<?php
/**
 * The Cart CSV helper
 *
 * @package Omeka\Plugins\Cart
 */

class Cart_CSV {

	private $_csv;

	public function __construct($items) {
		$this->_csv = [];

		// Add line in csv for each item 
		foreach($items as $item) {
			if (get_class($item) == "Item") {
				$this->_addLine($item);
			}
		}

		// return csv to download
		$this->_render();
	}

	/**
	 * Add a line to csv array
	 * @param Item $item The item object
	 */
	protected function _addLine($item) {
		$line = [];
		$labels = [];

		// Retrieve elements texts
		$elements = all_element_texts($item, array('return_type' => 'array'));

		// Add identifiers to all_element_texts() results
		$identifiers = metadata($item, array("Dublin Core", "Identifier"), array("all" => true));
		$elements['Dublin Core']['Identifier'] = $identifiers;

		// Display element texts
		foreach ($elements as $elementSetName => $elementTexts) {
			foreach ($elementTexts as $elementName => $elementsText) {
				foreach ($elementsText as $element) {
					array_push($labels, str_replace('PDF:', '', __('PDF:'.$elementName)));
					array_push($line, $this->_getValue($element));
				}
			}
		}

		if (count($this->_csv) === 0) {
			array_push($this->_csv, $labels);
		}
		array_push($this->_csv, $line);
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