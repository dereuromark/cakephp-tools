<?php

/**
 * Soros interpreter
 *
 * @see http://numbertext.org
 * @author Pavel Astakhov <pastakhov@yandex.ru>
 * @license LGPL/BSD dual license
 * @copyright (c) 2009-2010, László Németh
 */
class Soros {

	private $patterns = array();
	private $values = array();
	private $begins = array();
	private $ends = array();
	private $m;
	private $m2;
	private $c;
	private $c2;
	private $slash;
	private $pipe;
	private $func;
	private $numbertext = false;

	/**
	 * Constructor.
	 * @param string $source
	 */
	public function __construct($source) {
		$this->m = array(
			"\\",
			"\"",
			";",
			"#");
		$this->m2 = array(
			"$",
			"(",
			")",
			"|");
		$this->c = array(
			json_decode('"\uE000"'),
			json_decode('"\uE001"'),
			json_decode('"\uE002"'),
			json_decode('"\uE003"'),
			);
		$this->c2 = array(
			json_decode('"\uE004"'),
			json_decode('"\uE005"'),
			json_decode('"\uE006"'),
			json_decode('"\uE007"'),
			);
		$this->slash = array(json_decode('"\uE000"'));
		$this->pipe = array(json_decode('"\uE003"'));

		$source = self::translate($source, $this->m, $this->c, "\\");
		$source = preg_replace("/(#[^\n]*)?(\n|$)/", ";", $source);
		if (strpos($source, "__numbertext__") !== false) {
			$this->numbertext = true;
			$source = str_replace("__numbertext__", "0+(0|[1-9]\\d*) $1\n", $source);
		}

		foreach (split(";", $source) as $s) {
			if ($s != "" && preg_match("/^\\s*(\"[^\"]*\"|[^\\s]*)\\s*(.*[^\\s])?\\s*$/", $s, $sp) > 0) {
				$s = self::translate(preg_replace("/\"$/", "", preg_replace("/^\"/", "", $sp[1], 1), 1), $this->c, $this->m, "");
				$s = str_replace($this->slash[0], "\\\\", $s);
				$s2 = "";
				if (isset($sp[2]))
					$s2 = preg_replace("/\"$/", "", preg_replace("/^\"/", "", $sp[2], 1), 1);

				$s2 = self::translate($s2, $this->m2, $this->c2, "\\");
				$s2 = preg_replace("/(\\$\\d|\\))\\|\\$/", "$1||\\$", $s2);
				$s2 = self::translate($s2, $this->c, $this->m, "");
				$s2 = self::translate($s2, $this->m2, $this->c, "");
				$s2 = self::translate($s2, $this->c2, $this->m2, "");

				$s2 = preg_replace("/[$]/", "\\$", $s2); // $ -> \$
				$s2 = preg_replace("/" . $this->c[0] . "(\\d)/", $this->c[0] . $this->c[1] . "\\$$1" . $this->c[2], $s2); // $n -> $(\n)
				$s2 = preg_replace("/\\\\(\\d)/", "\\$$1", $s2); // \[n] -> $[n]
				$s2 = preg_replace("/\\n/", "\n", $s2); // \n -> [new line]

				$this->patterns[] = "^" . preg_replace("/\\$$/", "", preg_replace("/^\\^/", "", $s, 1), 1) . "$";
				$this->begins[] = (mb_substr($s, 0, 1) == "^");
				$this->ends[] = (mb_substr($s, -1) == "$");
				$this->values[] = $s2;
			}
		}

		$this->func = self::translate("(?:\\|?(?:\\$\\()+)?" . "(\\|?\\$\\(([^\\(\\)]*)\\)\\|?)" . "(?:\\)+\\|?)?", $this->m2, $this->c, "\\");
	}

	/**
	 *
	 * @param string $input
	 * @return string
	 */
	public function run($input) {
		if (!$this->numbertext)
			return $this->run3($input, true, true);
		return preg_replace("/  +/", " ", trim($this->run3($input, true, true)));
	}

	/**
	 *
	 * @param string $input
	 * @param string $begin
	 * @param string $end
	 * @return string
	 */
	private function run3($input, $begin, $end) {
		$count = count($this->patterns);
		for ($i = 0; $i < $count; $i++) {
			if ((!$begin && $this->begins[$i]) || (!$end && $this->ends[$i]))
				continue;
			if (!preg_match("/" . $this->patterns[$i] . "/", $input, $m))
				continue;

			$s = preg_replace("/" . $this->patterns[$i] . "/", $this->values[$i], $m[0]);
			preg_match_all("/" . $this->func . "/u", $s, $n, PREG_OFFSET_CAPTURE);
			while (count($n[0]) > 0) {
				//              n.start()            n.group()            n.start(1)           n.group(1)           n.start(2)           n.group(2)
				//MWDebug::log( $n[0][0][1] . "=>" . $n[0][0][0] . ", " . $n[1][0][1] . "=>" . $n[1][0][0] . ", " . $n[2][0][1] . "=>" . $n[2][0][0] );
				$b = false;
				$e = false;

				if (mb_substr($n[1][0][0], 0, 1) == $this->pipe[0] || mb_substr($n[0][0][0], 0, 1) == $this->pipe[0]) {
					$b = true;
				} elseif ($n[0][0][1] == 0) {
					$b = $begin;
				}

				if (mb_substr($n[1][0][0], -1) == $this->pipe[0] || mb_substr($n[0][0][0], -1) == $this->pipe[0]) {
					$e = true;
				} elseif ($n[0][0][1] + strlen($n[0][0][0]) == strlen($s))
					$e = $end;

				$s = substr($s, 0, $n[1][0][1]) . $this->run3($n[2][0][0], $b, $e) . substr($s, $n[1][0][1] + strlen($n[1][0][0]));
				preg_match_all("/" . $this->func . "/u", $s, $n, PREG_OFFSET_CAPTURE);
			}
			return $s;
		}
		return "";
	}

	/**
	 *
	 * @param string $s
	 * @param string $chars
	 * @param string $chars2
	 * @param string $delim
	 * @return string
	 */
	private static function translate($s, $chars, $chars2, $delim) {
		$count = count($chars);
		for ($i = 0; $i < $count; $i++) {
			$s = str_replace($delim . $chars[$i], $chars2[$i], $s);
		}
		return $s;
	}

}
