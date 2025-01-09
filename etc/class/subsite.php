<?php
require_once 'environment.php';

class Subsite {
	public string $ID;
	public string $Name;
	public string $CallToAction;

	private function __construct(string $id, string $name, string $calltoaction) {
		$this->ID = $id;
		$this->Name = $name;
		$this->CallToAction = $calltoaction;
	}

	public static function List(mysqli $db): array {
		try {
			$select = $db->prepare('select id, name, calltoaction from subsite where feature>0 order by feature');
			$select->execute();
			$select->bind_result($id, $name, $calltoaction);
			$list = [];
			while ($select->fetch())
				$list[] = new self($id, $name, $calltoaction);
			return $list;
		} catch (mysqli_sql_exception $mse) {
			throw new DetailedException('error looking up subsites.', $mse);
		}
	}
}
