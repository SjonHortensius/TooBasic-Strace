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

		// fastgi spec prefixes each k/v pair with a byte specifying the lengths
		if ($c instanceof Syscall\Read && preg_match('~(..)REQUEST_URI(.*)~', $c->getArgument(1), $m))
		{
			if (ord($m[1][0]) != 0x0b)
				throw new Exception('FastCgi error; key `REQUEST_URI` unexpectedly passed as having length: %d', [ord($m[1][0])]);

			$requestUri = substr($m[2], 0, ord($m[1][1]));

			$this->_lastRequest = new Operation($this, $c, $requestUri);
			yield self::LEVEL_INFO => 'incoming request '. $requestUri;
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

	public function summary(): array
	{
		return [];
	}
}