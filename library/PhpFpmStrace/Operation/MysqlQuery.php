<?php namespace PhpFpmStrace\Operation;

use PhpFpmStrace\Duration;
use PhpFpmStrace\Operation;
use PhpFpmStrace\Syscall;

class MysqlQuery implements Observer
{
	protected array $_fds = [];
	protected int $_hits = 0;
	protected Duration $_spent;
	private Operation $_lastRequest;
	public const TEXT_PROTOCOL_TYPES = [
		0x01 => 'COM_QUIT',
		0x02 => 'COM_INIT_DB',
		0x03 => 'COM_QUERY',
		0x09 => 'COM_STATISTICS',
		0x0a => 'COM_SHUTDOWN',
		0x0b => 'COM_SET_OPTION',
		0x0c => 'COM_PROCESS_KILL',
		0x0d => 'COM_DEBUG',
		0x0e => 'COM_PING',
		0x0f => 'COM_RESET_CONNECTION',
		0x11 => 'COM_CHANGE_USER',
	];

	public function __construct()
	{
		$this->_spent = new Duration;
	}

	public function observe(Syscall $c): \Generator
	{
		if ($c instanceof Syscall\Connect && 3306 === $c->port)
		{
			$this->_lastRequest = new Operation($this, $c, "connecting");
			$this->_fds[ intval($c->getArgument(0)) ] = true;

			$host = $c->getArgument(1)["sin_addr"] ?? explode('"', $c->getArgument(1)[0])[1];
			yield self::LEVEL_INFO => 'connecting to '. $host;
		}
		elseif (!is_numeric($c->getArgument(0)) || !isset($this->_fds[ intval($c->getArgument(0)) ]))
			return;

		if ($c instanceof Syscall\Sendto)
		{
			$parsed = self::parse($c->getArgument(1));

			if ($parsed['type'] == 0x15)
				$what = 'authenticating';
			elseif ($parsed['type'] == 0x03)
				$what = 'executing query: '. $parsed['query'];
			else
				$what = 'executing '. (self::TEXT_PROTOCOL_TYPES[$parsed['type']] ?? 'unknown type '.$parsed['type']);

			yield self::LEVEL_INFO => $what;

			$this->_hits++;
			$this->_lastRequest = new Operation($this, $c, $what);
		}
		elseif ($c instanceof Syscall\Recvfrom)
		{
			if (!isset($this->_lastRequest) && preg_match('~(5.5.*?)\\\\0~', $c->getArgument(1), $m))
				return yield Observer::LEVEL_INFO => 'hello from ' . $m[1];

			$parsed = self::parse($c->getArgument(1));

			// @see https://mariadb.com/kb/en/com_query/
			if ($parsed['type'] == 0xff)
				$what = 'received an error';
			elseif ($parsed['type'] == 0x00)
				$what = 'received OK';
			elseif ($parsed['type'] == 0xfb)
				$what = 'received LOCAL_INFILE packet';
			else
				$what = 'received resultset with '. $parsed['type'] .' columns';

			$this->_lastRequest->complete($c, $what);

			$spent = $this->_lastRequest->took();
			$this->_spent->add($spent);

			yield self::LEVEL_INFO => $what;
			yield self::LEVEL_CALL => $this->_lastRequest;
		}
		elseif ($c instanceof Syscall\Close)
			unset($this->_fds[ $c->getArgument(0) ]);
	}

	public static function parse(string $raw): array
	{
		$decoded = Syscall::decode($raw);

		// @see https://mariadb.com/kb/en/0-packet/
		$hdr = substr($decoded, 0, 5);
		$length = ord($hdr[0]) | ord($hdr[1]) <<8 | ord($hdr[2]) <<16;
		$seq = ord($hdr[3]);
		$typ = ord($hdr[4]);

		$q = substr($decoded, 5, $length-1);

		return [
			'type' => $typ,
			'sequence' => $seq,
			'query' => trim(preg_replace('~(\n|\t|\r| )+~', ' ', $q)),
		];
	}

	public function summary(): array
	{
		return [
			'queries' => $this->_hits,
			'timeSpent' => (string)$this->_spent,
		];
	}
}