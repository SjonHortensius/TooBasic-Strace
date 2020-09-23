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

	public function closedBy(Closer $call): bool
	{
		if (in_array($call->closes(), $this->_sockets))
		{
			$this->_closer = $call;
			return true;
		}

		return false;
	}

	public function __toString(): string
	{
		return sprintf('%s:socket[%s]', __CLASS__, $this->_returns);
	}
}
