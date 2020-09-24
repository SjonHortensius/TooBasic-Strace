<?php namespace PhpFpmStrace;

abstract class Syscall
{
	protected \DateTimeImmutable $_time;
	protected int $_returns;
	protected string $_returnVerbose;

	protected array $_args;

	public function __construct(...$args)
	{
		$this->_args = $args;
	}

	public static function fromCall(string $time, string $call, string $args, string $returns = ""): self
	{
		$class = '\\'. static::class .'\\'. ucfirst($call);

		if (!class_exists($class))
			eval('namespace PhpFpmStrace\Syscall; use '. static::class .' as p; class '. ucfirst($call) .' extends p {}');

	    $syscall = $class::fromRawArguments($args);
		$syscall->_setMeta(\DateTimeImmutable::createFromFormat('H:i:s.u', $time), $returns);

		return $syscall;
	}

	protected static function fromRawArguments(string $raw): self
	{
		$args = self::parseArguments($raw);

		try
		{
			return (new \ReflectionClass(static::class))->newInstanceArgs($args);
		}
		catch (\ArgumentCountError $e)
		{
			throw new \Exception("Not enough args to construct ". static::class . ': '. print_r($args, true), 0, $e);
		}
		catch (\TypeError $e)
		{
			var_dump("Invalid args to construct ". static::class . ': '. print_r($args, true));
			throw new \Exception("Invalid args to construct ". static::class . ': '. print_r($args, true), 0, $e);
		}
	}

	// break up the string of arguments into an array representing only the TOP arguments
	protected static function parseArguments(string $raw): array
	{
		$args = [];
		$state = null;
		$buffer = "";
		$sub = [];
		$depth = 0;

		for ($i = 0; $i<strlen($raw); $i++)
		{
			$c = $raw[$i];

			switch ($state)
			{
				case null:
					// Reset for deeper blocks once they complete
					$sub = [];
					$depth = 0;

						if ('"' === $c) $state = 'string';
					elseif ('{' === $c)	$state = 'struct';
					elseif ('[' === $c)	$state = 'array';
					elseif (',' === $c && ' ' === $raw[$i+1])
					{
						array_push($args, $buffer);
						$buffer = "";
						$i++; // eat space
					}
					else
						$buffer .= $c;
				break;

				// needed to eat 'special' chars such as '[' in strings
				case 'string':
					if ('\\' == $c && '"' === $raw[$i+1])
					{
						$buffer .= $c;
						$i++; // eat encoded "
					}
					elseif ('"' == $c)
					{
						array_push($args, $buffer);
						$buffer = "";
						$state = null;
					}
					else
						$buffer .= $c;
				break;

				case 'array':
					// First handle nesting
					if ('[' === $c)
						$depth++;
					elseif (']' === $c)
					{
						$depth--;

						if ($depth == -1)
						{
							// is merged to args by 'case null'
							$buffer = array_merge($sub, [$buffer]);
							$state = null;
							break;
						}
					}

					// detect structs or other special items in array, and don't split them
					if ('{' === $c || '(' === $c)
						$depth++;
					elseif ('}' === $c || ')' === $c)
						$depth--;

					if ($depth == 0 && ( (',' === $c && ' ' === $raw[$i+1]) || ' ' === $c))
					{
						$sub []= $buffer;
						$buffer = "";

						if (',' === $c)
							$i++; // eat space
					} else
						$buffer .= $c;
				break;

				case 'struct':
					if ('{' === $c || '(' === $c)
						$depth++;
					elseif ('}' === $c || ')' === $c)
					{
						$depth--;

						if ($depth == -1)
						{
							$kv = explode('=', $buffer, 2);

							if (count($kv) == 2)$sub[$kv[0]] = $kv[1];
							else				$sub []= $buffer;

							// is merged to args by 'case null'
							$buffer = $sub;
							$state = null;
							break;
						}
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
					throw new Exception("unknown state `%s`", [$state]);
			}
		}

		array_push($args, $buffer);

		return $args;
	}

	private function _setMeta(\DateTimeImmutable $time, string $returns)
	{
		$this->_time = $time;

		if (false === strpos($returns, ' '))
			$returns = intval($returns);
		else
			[$returns, $this->_returnVerbose] = explode(' ', $returns, 2);

		if (false !== strpos($returns, 'x'))
			$returns = hexdec($returns);

		$this->_returns = $returns;
	}

	public function __toString(): string
	{
		return sprintf('%s', __CLASS__);
	}
}