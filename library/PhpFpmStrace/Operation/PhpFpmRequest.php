<?php namespace PhpFpmStrace\Operation;

use PhpFpmStrace\Duration;
use PhpFpmStrace\Exception;
use PhpFpmStrace\Operation;
use PhpFpmStrace\Syscall;

class PhpFpmRequest implements Observer
{
	protected array $_fds = [];
	private Operation $_lastRequest;

	public function observe(Syscall $c): \Generator
	{
		if ($c instanceof Syscall\Accept)
		{
			$this->_fds[ $c->getReturn() ] = true;

			$host = $c->getArgument(1)["sin_addr"] ?? explode('"', $c->getArgument(1)[0])[1];
			yield self::LEVEL_INFO => 'connection from '. $host;
		}
		elseif (!is_numeric($c->getArgument(0)) || !isset($this->_fds[ intval($c->getArgument(0)) ]))
			return;

		if ($c instanceof Syscall\Read && false !== strpos($c->getArgument(1), 'REQUEST_URI'))
		{
			$req = self::decodeKeyValuePairs($c->getArgument(1));

			$this->_lastRequest = new Operation($this, $c, $req['REQUEST_URI']);
			yield self::LEVEL_INFO => 'incoming request '. $req['REQUEST_URI'];
		}
		elseif ($c instanceof Syscall\Write)
		{
			// Only yield if we observed the request as well
			if (isset($this->_lastRequest))
			{
				$this->_lastRequest->complete($c, 'responded with '. $c->getReturn() . ' bytes');
				yield self::LEVEL_CALL => $this->_lastRequest;
			}

			yield self::LEVEL_INFO => 'sending response, length: ' . $c->getReturn() . ' bytes';
		}
	}

	// fastgi spec prefixes each k/v pair with a byte specifying their lengths
	private static function decodeKeyValuePairs(string $data): array
	{
		$length = strlen($data);
		$i = 0;
		$kv = [];

		// args can be truncated (trailing '...') but REQUEST_URI is usually in there
		while ($i < $length-4)
		{
			$k = ord($data[$i++]);
			if ($k >=128)
			{
				$k = ($k & 0x7F << 24);
				$k |= (ord($data[$i++]) << 16);
				$k |= (ord($data[$i++]) << 8);
				$k |= (ord($data[$i++]));
			}

			$v = ord($data[$i++]);
			if ($v >=128)
			{
				$v = ($v & 0x7F << 24);
				$v |= (ord($data[$i++]) << 16);
				$v |= (ord($data[$i++]) << 8);
				$v |= (ord($data[$i++]));
			}

			$kv[ substr($data, $i, $k) ] = substr($data, $i+$k, $v);
			$i += $k+$v;
		}

		return $kv;
	}

	public function summary(): array
	{
		return [];
	}
}