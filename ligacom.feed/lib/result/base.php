<?php

namespace Ligacom\Feed\Result;

use Ligacom\Feed;

class Base
{
	protected $isErrorStrict = true;
	protected $isSuccess = true;
	/** @var Ligacom\Feed\Error\Base[] */
	protected $errors;
	/** @var Ligacom\Feed\Error\Base[] */
	protected $warnings;

	public function __construct()
	{
		$this->errors = [];
		$this->warnings = [];
	}

	public function isSuccess()
	{
		return $this->isSuccess;
	}

	public function setErrorStrict($isStrict)
	{
		$this->isErrorStrict = (bool)$isStrict;
	}

	public function isErrorStrict()
	{
		return $this->isErrorStrict;
	}

	public function invalidate()
	{
		if ($this->isErrorStrict)
		{
			$this->isSuccess = false;
		}
	}

	public function addError(Feed\Error\Base $error)
	{
		if ($this->isErrorStrict)
		{
			$this->isSuccess = false;
			$this->errors[] = $error;
		}
		else
		{
			$this->addWarning($error);
		}
	}

	public function addErrors(array $errors)
	{
		if ($this->isErrorStrict)
		{
			$this->isSuccess = false;

			foreach ($errors as $error)
			{
				$this->errors[] = $error;
			}
		}
		else
		{
			$this->addWarnings($errors);
		}
	}

	public function getErrors()
	{
		return $this->errors;
	}

	public function getErrorMessages()
	{
		$result = [];

		foreach ($this->errors as $error)
		{
			$result[] = $error->getMessage();
		}

		return $result;
	}

	/**
	 * @return bool
	 */
	public function hasErrors()
	{
		return !empty($this->errors);
	}

	public function addWarning(Feed\Error\Base $warning)
	{
		$this->warnings[] = $warning;
	}

	public function addWarnings(array $warnings)
	{
		foreach ($warnings as $warning)
		{
			$this->warnings[] = $warning;
		}
	}

	/**
	 * @return Ligacom\Feed\Error\Base[]
	 */
	public function getWarnings()
	{
		return $this->warnings;
	}

	/**
	 * @return bool
	 */
	public function hasWarnings()
	{
		return !empty($this->warnings);
	}
}