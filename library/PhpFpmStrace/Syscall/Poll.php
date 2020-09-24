<?php namespace PhpFpmStrace\Syscall;

class Poll extends \PhpFpmStrace\Syscall
{
	protected Closer $_closer;

	public function __construct(array $descriptors, int $descriptorLength, int $timeout)
	{
		parent::__construct(...func_get_args());
	}

/*	protected static function fromRawArguments(string $raw): self
	{
		return parent::fromRawArguments($raw);
	}
*/
}