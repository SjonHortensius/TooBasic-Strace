<?php namespace PhpFpmStrace\Operation;

use PhpFpmStrace\Duration;
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

		if ($c instanceof Syscall\Read && preg_match('~REQUEST_URI(.*?)\\\\f~', $c->getArgument(1), $m))
		{
			$this->_lastRequest = new Operation($this, $c, $m[1]);
			yield self::LEVEL_INFO => 'incoming request ' . $m[1];
		}
		elseif ($c instanceof Syscall\Write)
		{
			$this->_lastRequest->complete($c);
			yield self::LEVEL_INFO => 'sending response, length: ' . $c->getReturn() . ' bytes';
			yield self::LEVEL_CALL => $this->_lastRequest;
		}
	}

	public function summary(): array
	{
		return [
			'queries' => $this->_hits,
			'timeSpent' => (string)$this->_spent,
		];
	}
}