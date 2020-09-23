<?php namespace PhpFpmStrace;

abstract class Syscall
{
	protected \DateTimeImmutable $_time;
	private string $_call;
	protected array $_args;
	protected int $_retn;
	private static $_classes = [];

	public function __construct(...$args)
	{
		$this->_args = $args;
	}

	public static function fromCall(string $time, string $call, string $args, string $retn = ""): self
	{
		$class = '\\'. static::class .'\\'. ucfirst($call);

		if (!class_exists($class))
			eval('namespace PhpFpmStrace\Syscall; use '. static::class .' as p; class '. ucfirst($call) .' extends p {}');

	    // decode structures, pointers and arrays
		var_dump($args, self::parseArguments($args));
		$args = self::parseArguments($args);

		try
		{
		    /** @var Syscall $syscall */
		    $syscall = (new \ReflectionClass($class))->newInstanceArgs($args);
		} catch (\ArgumentCountError $e) {
			throw new \Exception("Not enough args to construct ".$class . ' '. print_r($args, 1), 0, $e);
		}

		$syscall->_setMeta(\DateTimeImmutable::createFromFormat('H:i:s.u', $time), intval($retn));
		$syscall->_call = $call;

		return $syscall;
	}

	protected static function parseArguments(string $raw): array
	{
		if (
			substr_count($raw, '{') != substr_count($raw, '}') ||
			substr_count($raw, '[') != substr_count($raw, ']')
		)
			throw new Exception("Unbalanced arguments `%s`, cannot parse", [$raw]);

		$parts = [];
		$raw = explode(', ', $raw);
		for ($i = 0; $i<count($raw); $i++)
		{
			$part = $raw[$i];

			if (in_array($part[0], ['[', '{']))
			{
				var_dump("found start: ". $part);
				$part = substr($part, 1);
				$sub = [];

				while (!in_array($part[strlen($part)-1], [']', '}']))
				{
					var_dump("not the end: ". $part);
					$sub []= $part;
					$part = $raw[++$i];
				}

				$part = substr($part, 0, strlen($part)-1);
				$sub []= $part;

//				array_push($parts, self::parseArguments(implode(', ', $sub)));
				array_push($parts, $sub);
				var_dump("done: ". $part, print_r($parts, 1));
			}
			else//if false === substr($part, '(')
				array_push($parts, $part);
		}

		return $parts;
//		preg_match_all("/({.*?}|\[.*?\]|[^\[\]{}]+)(, |$)/", $args, $m);
	}

	private function _setMeta(\DateTimeImmutable $time, int $retn)
	{
		$this->_time = $time;
		$this->_retn = $retn;
	}

	public function __toString(): string
	{
		return sprintf('%s:%s', __CLASS__, $this->_call);
	}
}