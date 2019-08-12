<?php

namespace Ligacom\Feed\Reference\Agent;

abstract class Regular extends Base
{
	/**
	 * @return array ������ �������� �������, �����:
	 *               method => string, # �������� ������ (�������������)
	 *               arguments => array|null # ��������� ������ ������ (�������������)
	 *               interval => integer, # �������� �������, � �������� (�������������)
	 *               sort => integer, # ����������, ��-��������� � 100 (�������������)
	 *               next_exec => string, # ���� � ������� Y-m-d H:i:s (�������������)
	 * */
	public static function getAgents()
	{
		return array(
			static::getDefaultParams()
		);
	}
}
