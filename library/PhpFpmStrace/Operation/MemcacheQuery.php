<?php namespace PhpFpmStrace\Operation;

use PhpFpmStrace\Duration;
use PhpFpmStrace\Operation;
use PhpFpmStrace\Syscall;

class MemcacheQuery implements Observer
{
	protected array $_fds = [];
	protected int $_hits = 0;
	protected Duration $_spent;
	private Operation $_lastRequest;
	protected static Duration $_spentTotal;

	public function __construct()
	{
		$this->_spent = new Duration;

		if (!isset(self::$_spentTotal))
			self::$_spentTotal = new Duration;
	}

	public function observe(Syscall $c): \Generator
	{
		if ($c instanceof Syscall\Connect && 11211 === $c->port)
		{
			$host = $c->getArgument(1)["sin_addr"] ?? explode('"', $c->getArgument(1)[0])[1];
			yield self::LEVEL_INFO => 'connecting to '.$host;
			$this->_fds[ intval($c->getArgument(0)) ] = true;
		}
		elseif (!is_numeric($c->getArgument(0)) || !isset($this->_fds[ intval($c->getArgument(0)) ]))
			return;

		if ($c instanceof Syscall\Sendto)
		{
			//FIXME properly implement protocol
			$p = explode('\\0', $c->getArgument(1));
			$key = array_pop($p);

			$this->_lastRequest = new Operation($this, $c, 'requesting '. $key);
			$this->_hits++;

			yield self::LEVEL_INFO => 'requesting '. $key;
		}
		elseif ($c instanceof Syscall\Recvfrom)
		{
			$this->_lastRequest->complete($c);
			yield $this->_lastRequest;

			$spent = $this->_lastRequest->took();
			$this->_spent->add($spent);
			self::$_spentTotal->add($spent);

			//FIXME properly implement protocol
			$p = explode('\\0', $c->getArgument(1));

			if (count($p) > 2)
				yield self::LEVEL_INFO => 'received '. strlen(array_pop($p)) .' bytes';
		}
		elseif ($c instanceof Syscall\Close)
			unset($this->_fds[ $c->getArgument(0) ]);
	}

	public function summary(): array
	{
		return [
			'queries' => $this->_hits,
			'timeSpent' => (string)$this->_spent,
		];
	}
}