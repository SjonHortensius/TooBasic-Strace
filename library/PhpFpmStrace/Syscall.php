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
		/** @var Syscall $class $class */
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
			throw new \Exception("Invalid args to construct ". static::class . ': '. json_encode($args), 0, $e);
		}
	}

	public function getTimestamp(): \DateTimeImmutable
	{
		return $this->_time;
	}

	public function getArgument(int $index)
	{
		$raw = $this->_args[$index];
		if (is_string($raw))
			return Syscall::decode($raw);
		elseif (is_array($raw))
			return array_map([self::class, 'decode'], $raw);
		else
			return $raw;
	}

	public function getReturn(): int
	{
		return $this->_returns;
	}

	public static function decode(string $argument): string
	{
		$decoded = '';

		if (preg_match_all('~\\\\[tnvfr]|\\\\[x0-9a-f]+|.~', $argument, $matches))
			foreach ($matches[0] as $char)
			{
				if (1 === strlen($char))
					$decoded .= $char;
				// re-encode 'decoded' data
				elseif (in_array($char, ['\t', '\n', '\v', '\f', '\r']))
					$decoded .= str_replace(['\t', '\n', '\v', '\f', '\r'], ["\t", "\n", "\v", "\f", "\r"], $char);
				// support strace -x option
				elseif (substr($char, 0, 2) == '\x')
					$decoded .= chr(hexdec(substr($char, 2)));
				else
					$decoded .= chr(hexdec(substr($char, 1)));
			}

		return $decoded;
	}

	// break up the string of arguments into an array representing only the TOP arguments.
	// This roughly equals `explode(', ', $raw)` or `preg_match_all("/({.*?}|\[.*?\]|[^\[\]{}]+)(, |$)/", $raw);`
	// but it properly leaves nested elements intact
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
						$args []= $buffer; $buffer = "";
						$i++; // eat space
					}
					else
						$buffer .= $c;
				break;

				// needed to eat 'special' chars such as '[' in strings
				case 'string':
					if ('\\' == $c && '"' === $raw[$i+1])
					{
						$i++; // eat \\
						$buffer .= '"';
					}
					elseif ('"' == $c)
						$state = null;
					else
						$buffer .= $c;
				break;

				case 'array':
					// detect nested items in array, and prevent splitting on those
					if (in_array($c, ['{', '(', '[']))
						$depth++;
					elseif (in_array($c, ['}', ')', ']']))
						$depth--;

					if ($depth == -1)
					{
						// is merged to args by 'case null'
						$buffer = array_merge($sub, [$buffer]);
						$state = null;
					}
					elseif ($depth == 0 && ',' === $c && ' ' === $raw[$i+1])
					{
						$sub []= $buffer;
						$buffer = "";
						$i++; // eat space
					}
					elseif ($depth == 0 && ' ' === $c)
					{
						$sub []= $buffer;
						$buffer = "";
					} else
						$buffer .= $c;
				break;

				case 'struct':
					if ('{' === $c || '(' === $c)
						$depth++;
					elseif ('}' === $c || ')' === $c)
						$depth--;

					if ($depth === -1 || ($depth === 0 && ',' === $c && ' ' === $raw[$i+1]))
					{
						$kv = explode('=', $buffer, 2);

						if (count($kv) == 2)
							$sub[$kv[0]] = $kv[1];
						else
							$sub []= $buffer;

						if ($depth === -1)
						{
							// is merged to args by 'case null'
							$buffer = $sub;
							$state = null;
						}
						else
						{
							$buffer = "";
							$i++; // eat space
						}
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
		return sprintf('%s:%s', __CLASS__, json_encode($this->_args));
	}
}