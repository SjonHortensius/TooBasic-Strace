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
var_dump($args);print_r(self::parseArguments($args));
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

	// break up the string of arguments into an array representing only the TOP arguments
	protected static function parseArguments(string $raw): array
	{
		$args = [];
		$state = null;
		$buffer = "";
		$depth = 0;

		for ($i = 0; $i<strlen($raw); $i++)
		{
			$c = $raw[$i];

			switch ($state)
			{
				case null:
					if ('"' === $c)		$state = 'string';
					elseif ('{' === $c)	$state = 'struct';
					elseif ('[' === $c)	$state = 'array';
					elseif (',' === $c && ' ' === $raw[$i+1])
					{
						$args [] = $buffer;
						$buffer = "";
						$i++;
					}
					else
						$buffer .= $c;
				break;

				case 'string':
					if ('"' == $c)
					{
						$args []= $buffer;
						$state = null;
						$buffer = "";
					}
					else
						$buffer .= $c;
				break;

				case 'array':
					if (!isset($sub))
						$sub = [];

					if ('[' === $c)		$depth++;
					elseif (']' === $c)	$depth--;

					if ($depth > 0)
					{
						$buffer .= $c;
						break;
					}
					elseif ($depth == -1)
					{
						$sub []= $buffer;
						$args []= $sub;

						unset($sub);
						$depth = 0;
						$state = null;
						$buffer = "";
						break;
					}

					if (',' === $c && ' ' === $raw[$i+1])
					{
						$sub []= $buffer;
						$buffer = "";
					} else
						$buffer .= $c;
				break;

				case 'struct':
					if (!isset($sub))
						$sub = [];

					if ('{' === $c || '(' === $c)		$depth++;
					elseif ('}' === $c || ')' === $c)	$depth--;

					if ($depth > 0)
					{
						$buffer .= $c;
						break;
					}
					elseif ($depth == -1)
					{
						$kv = explode('=', $buffer, 2);

						if (count($kv) == 2)$sub[$kv[0]] = $kv[1];
						else				$sub []= $buffer;

						$args []= $sub;

						unset($sub);
						$depth = 0;
						$state = null;
						$buffer = "";
						break;
					}

					if ($depth === 0 && ',' === $c && ' ' === $raw[$i+1])
					{
						$kv = explode('=', $buffer, 2);

						if (count($kv) == 2)$sub[$kv[0]] = $kv[1];
						else				$sub []= $buffer;

						$buffer = "";
						$i++;
					} else
						$buffer .= $c;
				break;

				default:
					throw new Exception("unknown state `%s`", $state);
			}
		}

		return $args;
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