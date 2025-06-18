<?php

namespace HalloWelt\MigrateDokuwiki\Utility;

class AccentedChars {

	/**
	 * Replace accented chars in keys
	 *
	 * See https://github.com/dokuwiki/dokuwiki/blob/master/inc/Utf8/tables/loweraccents.php
	 *
	 * @param string $text
	 * @return string
	 */
	public static function normalizeAccentedText( string $text ): string {
		foreach ( self::getMap() as $accented => $normalized ) {
			$text = str_replace( $accented, $normalized, $text );
		}
		return $text;
	}

	/**
	 * @return array
	 */
	private static function getMap(): array {
		return [
			'á' => 'a',
			'à' => 'a',
			'ă' => 'a',
			'â' => 'a',
			'å' => 'a',
			'ä' => 'ae',
			'ã' => 'a',
			'ą' => 'a',
			'ā' => 'a',
			'æ' => 'ae',
			'ḃ' => 'b',
			'ć' => 'c',
			'ĉ' => 'c',
			'č' => 'c',
			'ċ' => 'c',
			'ç' => 'c',
			'ď' => 'd',
			'ḋ' => 'd',
			'đ' => 'd',
			'ð' => 'dh',
			'é' => 'e',
			'è' => 'e',
			'ĕ' => 'e',
			'ê' => 'e',
			'ě' => 'e',
			'ë' => 'e',
			'ė' => 'e',
			'ę' => 'e',
			'ē' => 'e',
			'ḟ' => 'f',
			'ƒ' => 'f',
			'ğ' => 'g',
			'ĝ' => 'g',
			'ġ' => 'g',
			'ģ' => 'g',
			'ĥ' => 'h',
			'ħ' => 'h',
			'í' => 'i',
			'ì' => 'i',
			'î' => 'i',
			'ï' => 'i',
			'ĩ' => 'i',
			'į' => 'i',
			'ī' => 'i',
			'ı' => 'i',
			'ĵ' => 'j',
			'ķ' => 'k',
			'ĺ' => 'l',
			'ľ' => 'l',
			'ļ' => 'l',
			'ł' => 'l',
			'ṁ' => 'm',
			'ń' => 'n',
			'ň' => 'n',
			'ñ' => 'n',
			'ņ' => 'n',
			'ó' => 'o',
			'ò' => 'o',
			'ô' => 'o',
			'ö' => 'oe',
			'ő' => 'o',
			'õ' => 'o',
			'ø' => 'o',
			'ō' => 'o',
			'ơ' => 'o',
			'ṗ' => 'p',
			'ŕ' => 'r',
			'ř' => 'r',
			'ŗ' => 'r',
			'ś' => 's',
			'ŝ' => 's',
			'š' => 's',
			'ṡ' => 's',
			'ş' => 's',
			'ș' => 's',
			'ß' => 'ss',
			'ť' => 't',
			'ṫ' => 't',
			'ţ' => 't',
			'ț' => 't',
			'ŧ' => 't',
			'ú' => 'u',
			'ù' => 'u',
			'ŭ' => 'u',
			'û' => 'u',
			'ů' => 'u',
			'ü' => 'ue',
			'ű' => 'u',
			'ũ' => 'u',
			'ų' => 'u',
			'ū' => 'u',
			'ư' => 'u',
			'ẃ' => 'w',
			'ẁ' => 'w',
			'ŵ' => 'w',
			'ẅ' => 'w',
			'ý' => 'y',
			'ỳ' => 'y',
			'ŷ' => 'y',
			'ÿ' => 'y',
			'ź' => 'z',
			'ž' => 'z',
			'ż' => 'z',
			'þ' => 'th',
			'µ' => 'u',
		];
	}
}
