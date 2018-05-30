<?php

	Class extension_numberfield extends Extension {

		public function uninstall() {
			Symphony::Database()
				->drop('tbl_fields_number')
				->ifExists()
				->execute()
				->success();
		}

		public function install() {
			return Symphony::Database()
				->create('tbl_fields_number')
				->ifNotExists()
				->charset('utf8')
				->collate('utf8_unicode_ci')
				->fields([
					'id' => [
						'type' => 'int(11)',
						'auto' => true,
					],
					'field_id' => 'int(11)',
				])
				->keys([
					'id' => 'primary',
					'field_id' => 'unique',
				])
				->execute()
				->success();
		}

	}
