<?php

	Class extension_numberfield extends Extension {

		public function uninstall() {
			Symphony::Database()->query("DROP TABLE `tbl_fields_number`");
		}

		public function install() {
			return Symphony::Database()->query("
				CREATE TABLE `tbl_fields_number` (
					`id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
					`field_id` INT(11) UNSIGNED NOT NULL,
					PRIMARY KEY  (`id`),
					UNIQUE KEY `field_id` (`field_id`)
				)  ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci
			");
		}

	}
