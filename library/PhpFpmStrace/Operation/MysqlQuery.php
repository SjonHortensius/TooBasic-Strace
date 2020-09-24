<?php namespace PhpFpmStrace\Operation;

use PhpFpmStrace\Interval;
use PhpFpmStrace\Syscall;

class MysqlQuery implements Observer
{
	protected array $_fds = [];
	protected int $_hits = 0;
	protected Interval $_spent;
	private Syscall\Sendto $_lastRequest;

	public function __construct()
	{
		$this->_spent = new Interval;
	}

	public function executes(Syscall $c): \Generator
	{
		$msg = sprintf('[%s] %s: %%s', $c->getTimestamp()->format("H:m:s.u"), __CLASS__);

		if ($c instanceof Syscall\Connect && 3306 === $c->port)
		{
			$host = $c->getArgument(1)["sin_addr"] ?? explode('"', $c->getArgument(1)[0])[1];
			yield sprintf($msg, 'connecting to '.$host);
			$this->_fds[ intval($c->getArgument(0)) ] = true;
		}
		elseif (!is_numeric($c->getArgument(0)) || !isset($this->_fds[ intval($c->getArgument(0)) ]))
			return;
//var_dump($c);
		if ($c instanceof Syscall\Sendto)
		{
			$this->_lastRequest = $c;

			//FIXME properly implement protocol
			if (false !== strpos($c->getArgument(1), 'mysql_native_password'))
				return yield sprintf($msg, 'authenticating to XXXDB as XXXUSER');

			$this->_hits++;

			$p = explode('\\0', $c->getArgument(1));
			$key = array_pop($p);
			yield sprintf($msg, 'sending query: '.$key);
		}
		elseif ($c instanceof Syscall\Recvfrom)
		{
			// HELO
			if (!isset($this->_lastRequest))
				return yield sprintf($msg, 'hello from mariadb-XXX');

			$this->_spent->add(Interval::fromDiff($c->getTimestamp(), $this->_lastRequest->getTimestamp()));

			yield sprintf($msg, 'received '. strlen($c->getArgument(1)) .' bytes');
		}
		elseif ($c instanceof Syscall\Close)
			unset($this->_fds[ $c->getArgument(0) ]);
	}

	public function reset(): void
	{
		$this->_fds = [];
	}
}