<?php

	require_once FACE . '/interface.exportablefield.php';
	require_once FACE . '/interface.importablefield.php';

	class FieldNumber extends Field implements ExportableField, ImportableField {
		public function __construct() {
			parent::__construct();
			$this->_name = __('Number');
			$this->_required = true;
			$this->set('required', 'no');
		}

	/*-------------------------------------------------------------------------
		Setup:
	-------------------------------------------------------------------------*/

		public function isSortable() {
			return true;
		}

		public function canFilter() {
			return true;
		}

		public function allowDatasourceOutputGrouping() {
			return true;
		}

		public function allowDatasourceParamOutput() {
			return true;
		}

		public function canPrePopulate() {
			return true;
		}

		public function createTable() {
			return Symphony::Database()->query(
				"CREATE TABLE IF NOT EXISTS `tbl_entries_data_" . $this->get('id') . "` (
				  `id` int(11) unsigned NOT NULL auto_increment,
				  `entry_id` int(11) unsigned NOT NULL,
				  `value` double default NULL,
				  PRIMARY KEY  (`id`),
				  UNIQUE KEY `entry_id` (`entry_id`),
				  KEY `value` (`value`)
				) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci"
			);
		}

	/*-------------------------------------------------------------------------
		Settings:
	-------------------------------------------------------------------------*/

		public function displaySettingsPanel(XMLElement &$wrapper, $errors = null) {
			parent::displaySettingsPanel($wrapper, $errors);

			$div = new XMLElement('div', NULL, array('class' => 'two columns'));
			$this->appendRequiredCheckbox($div);
			$this->appendShowColumnCheckbox($div);
			$wrapper->appendChild($div);
		}

		public function commit() {
			if (!parent::commit()) return false;

			$id = $this->get('id');

			if ($id === false) return false;

			return FieldManager::saveSettings($id, $fields);
		}

	/*-------------------------------------------------------------------------
		Input:
	-------------------------------------------------------------------------*/

		public function displayPublishPanel(XMLElement &$wrapper, $data = null, $flagWithError = null, $fieldnamePrefix = null, $fieldnamePostfix = null, $entry_id = null){

			$value = $data['value'];
			$label = Widget::Label($this->get('label'));
			if($this->get('required') != 'yes') {
				$label->appendChild(new XMLElement('i', __('Optional')));
			}
			$label->appendChild(
				Widget::Input(
					'fields'.$fieldnamePrefix.'['.$this->get('element_name').']'.$fieldnamePostfix,
					(strlen($value) != 0 ? $value : NULL)
				)
			);

			if($flagWithError != NULL) {
				$wrapper->appendChild(Widget::Error($label, $flagWithError));
			}
			else {
				$wrapper->appendChild($label);
			}
		}

		public function checkPostFieldData($data, &$message, $entry_id = null) {
			$message = NULL;

			if($this->get('required') == 'yes' && strlen($data) == 0){
				$message = __('‘%s’ is a required field.', array($this->get('label')));
				return self::__MISSING_FIELDS__;
			}

			if(strlen($data) > 0 && !is_numeric($data)) {
				$message = __('Must be a number.');
				return self::__INVALID_FIELDS__;
			}

			return self::__OK__;
		}

		public function processRawFieldData($data, &$status, &$message=null, $simulate = false, $entry_id = null) {
			$status = self::__OK__;

			if (strlen(trim($data)) == 0) return array();

			$result = array(
				'value' => $data
			);

			return $result;
		}

	/*-------------------------------------------------------------------------
		Import:
	-------------------------------------------------------------------------*/

		public function getImportModes() {
			return array(
				'getValue' =>		ImportableField::STRING_VALUE,
				'getPostdata' =>	ImportableField::ARRAY_VALUE
			);
		}

		public function prepareImportValue($data, $mode, $entry_id = null) {
			$message = $status = null;
			$modes = (object)$this->getImportModes();

			if($mode === $modes->getValue) {
				return $data;
			}
			else if($mode === $modes->getPostdata) {
				return $this->processRawFieldData($data, $status, $message, true, $entry_id);
			}

			return null;
		}

	/*-------------------------------------------------------------------------
		Export:
	-------------------------------------------------------------------------*/

		/**
		 * Return a list of supported export modes for use with `prepareExportValue`.
		 *
		 * @return array
		 */
		public function getExportModes() {
			return array(
				'getUnformatted' => ExportableField::UNFORMATTED,
				'getPostdata' =>	ExportableField::POSTDATA
			);
		}

		/**
		 * Give the field some data and ask it to return a value using one of many
		 * possible modes.
		 *
		 * @param mixed $data
		 * @param integer $mode
		 * @param integer $entry_id
		 * @return string|null
		 */
		public function prepareExportValue($data, $mode, $entry_id = null) {
			$modes = (object)$this->getExportModes();

			// Export unformatted:
			if ($mode === $modes->getUnformatted || $mode === $modes->getPostdata) {
				return isset($data['value'])
					? $data['value']
					: null;
			}

			return null;
		}

	/*-------------------------------------------------------------------------
		Filtering:
	-------------------------------------------------------------------------*/

		/**
		 * Returns the keywords that this field supports for filtering. Note
		 * that no filter will do a simple 'straight' match on the value.
		 *
		 * @since Symphony 2.6.0
		 * @return array
		 */
		public function fetchFilterableOperators()
		{
			return array(
				array(
					'title' 			=> 'is',
					'filter' 			=> ' ',
					'help' 				=> __('Find values that are an exact match for the given number.')
				),
				array(
					'title'				=> 'less than',
					'filter'			=> 'less than ',
					'help'				=> __('Less than %s', array('<code>$x</code>'))
				),
				array(
					'title'				=> 'equal to or less than',
					'filter'			=> 'equal to or less than ',
					'help'				=> __('Equal to or less than %s', array('<code>$x</code>'))
				),
				array(
					'title'				=> 'greater than',
					'filter'			=> 'greater than ',
					'help'				=> __('Greater than %s', array('<code>$x</code>'))
				),
				array(
					'title'				=> 'equal to or greater than',
					'filter'			=> 'equal to or greater than ',
					'help'				=> __('Equal to or greater than %s', array('<code>$x</code>'))
				),
				array(
					'title'				=> 'between',
					'filter'			=> 'x to y',
					'help'				=> __('Find values between two values with %s to %s', array(
						'<code>$x</code>',
						'<code>$y</code>'
					))
				),
			);
		}

		public function buildDSRetrievalSQL($data, &$joins, &$where, $andOperation = false) {
			$field_id = $this->get('id');
			$expression = " `t$field_id`.`value` ";

			// X to Y support
			if(preg_match('/^(-?(?:\d+(?:\.\d+)?|\.\d+)) to (-?(?:\d+(?:\.\d+)?|\.\d+))$/i', $data[0], $match)) {

				$joins .= " LEFT JOIN `tbl_entries_data_$field_id` AS `t$field_id` ON (`e`.`id` = `t$field_id`.entry_id) ";
				$where .= " AND `t$field_id`.`value` BETWEEN {$match[1]} AND {$match[2]} ";

			}

			// Equal to or less/greater than X
			else if(preg_match('/^(equal to or )?(less|greater) than\s*(-?(?:\d+(?:\.\d+)?|\.\d+))$/i', $data[0], $match)) {

				switch($match[2]) {
					case 'less':
						$expression .= '<';
						break;

					case 'greater':
						$expression .= '>';
						break;
				}

				if($match[1]){
					$expression .= '=';
				}

				$expression .= " {$match[3]} ";

				$joins .= " LEFT JOIN `tbl_entries_data_$field_id` AS `t$field_id` ON (`e`.`id` = `t$field_id`.entry_id) ";
				$where .= " AND $expression ";

			}

			// Look for <=/< or >=/> symbols
			else if(preg_match('/^(=?[<>]=?)\s*(-?(?:\d+(?:\.\d+)?|\.\d+))$/i', $data[0], $match)) {

				$joins .= " LEFT JOIN `tbl_entries_data_$field_id` AS `t$field_id` ON (`e`.`id` = `t$field_id`.entry_id) ";
				$where .= sprintf(
					" AND %s %s %f",
					$expression,
					$match[1],
					$match[2]
				);

			}

			else parent::buildDSRetrievalSQL($data, $joins, $where, $andOperation);

			return true;
		}

	/*-------------------------------------------------------------------------
		Grouping:
	-------------------------------------------------------------------------*/

		public function groupRecords($records) {
			if(!is_array($records) || empty($records)) return;

			$groups = array($this->get('element_name') => array());

			foreach($records as $r) {
				$data = $r->getData($this->get('id'));

				$value = $data['value'];

				if(!isset($groups[$this->get('element_name')][$value])) {
					$groups[$this->get('element_name')][$value] = array(
						'attr' => array('value' => $value),
						'records' => array(),
						'groups' => array()
					);
				}

				$groups[$this->get('element_name')][$value]['records'][] = $r;

			}

			return $groups;
		}

	}
