<?php namespace PhpFpmStrace\Syscall;

class Socketpair extends \PhpFpmStrace\Syscall implements Opener
{
	protected Closer $_closer;
	protected array $_sockets;

	public function __construct(string $domain, string $type, string $protocol, array $v)
	{
		parent::__construct(...func_get_args());
		$this->_sockets = array_map('intval', $v);
	}

	public function spawns(): array
	{
		return $this->_sockets;
	}

	public function closedBy(Closer $call): void
	{
		$this->_closer = $call;
	}

	public function __toString(): string
	{
		return sprintf('%s:socket[%s]', __CLASS__, implode(',', $this->_sockets));
	}
}
