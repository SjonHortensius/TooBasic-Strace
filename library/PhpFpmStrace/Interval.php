<?php namespace PhpFpmStrace;

class Interval
{
	private float $_diff;

	public function __construct(float $diff = 0)
	{
		$this->_diff = $diff;
	}

	public static function fromDiff(\DateTimeImmutable $a, \DateTimeImmutable $b): self
	{
		$uselessPhpClass = $a->diff($b);

		$diff  = $uselessPhpClass->s + $uselessPhpClass->f;
		$diff += $uselessPhpClass->m * 60;
		$diff += $uselessPhpClass->h * 60 * 60;
		$diff += $uselessPhpClass->d * 24 * 60 * 60;

		return new self($diff);
	}

	public function add(self $i)
	{
		$this->_diff += $i->_diff;
	}

	public function __toString(): string
	{
		if ($this->_diff > 60)
			return sprintf('%dm', $this->_diff);
		elseif ($this->_diff > 1)
			return sprintf('%.2fs', $this->_diff);
		else
			return sprintf('%.4fs', $this->_diff);
	}
}