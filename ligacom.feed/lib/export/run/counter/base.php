<?php

namespace Ligacom\Feed\Export\Run\Counter;

use Ligacom\Feed;

abstract class Base
{
	/**
	 * ����������� ������� � ��������
	 */
	abstract public function start();

	/**
	 * ���������� ���������� ��������� �� �������
	 *
	 * @param $filter array
	 * @param $context array
	 *
	 * @return int
	 */
	abstract public function count($filter, $context);

	/**
	 * ���������� ������� �� ��������� ��������
	 */
	abstract public function finish();
}