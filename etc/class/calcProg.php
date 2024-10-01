<?php
require_once 'environment.php';
require_once 'formatDate.php';

class CalcProg {
	public string $ID;
	public TimeTagData $Instant;
	public string $Title;
	public string $Subject;
	public string $Model;
	public int $TiCalc;
	public string $Description;

	public function __construct(CurrentUser $user, string $id, int $instant, string $title, string $subject, string $model, int $ticalc, string $description) {
		$this->ID = $id;
		if ($instant)
			$this->Instant = new TimeTagData($user, 'smart', $instant, FormatDate::Long);
		$this->Title = $title;
		$this->Subject = $subject;
		$this->Model = $model;
		$this->TiCalc = $ticalc;
		$this->Description = $description;
	}

	public static function List(mysqli $db, CurrentUser $user): array {
		$progs = [];
		try {
			$select = $db->prepare('select c.id, unix_timestamp(p.instant), p.title, c.subject, c.model, c.ticalc, c.description from calcprog as c left join post as p on p.id=c.post order by instant desc');
			$select->execute();
			$select->bind_result($id, $instant, $title, $subject, $model, $ticalc, $description);
			while ($select->fetch())
				$progs[] = new self($user, $id, $instant, $title, $subject, $model, $ticalc, $description);
			return $progs;
		} catch (mysqli_sql_exception $mse) {
			throw DetailedException::FromMysqliException('error looking up calculator programs', $mse);
		}
	}
}
