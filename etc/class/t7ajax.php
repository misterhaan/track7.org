<?php
/**
 * ajax return class for responding to ajax requests with json.
 * @author misterhaan
 *
 */
class t7ajax {
	/**
	 * returned data object.  starts with ->fail set to false and should have other data added.
	 * @var object
	 */
	public $Data;

	/**
	 * initialize ajax response object.
	 */
	public function t7ajax() {
		$this->Data = new stdClass();
		$this->Data->fail = false;
	}

	/**
	 * Merge an entire object of data into the ajax response data.
	 * @param object $newObject data object to merge.
	 */
	public function MergeData($newObject) {
		$this->Data = (object)array_merge((array)$this->Data, (array)$newObject);
	}

	/**
	 * mark the request failed and add a reason.
	 * @param string $message failure reason
	 */
	public function Fail($message, $debug = false) {
		global $user;
		$this->Data->fail = true;
		if($debug)
			$message .= $user->IsAdmin() ? ':  ' . $debug : '.';
		$this->Data->message = $message;
	}

	/**
	 * Send the ajax response.
	 */
	public function Send() {
		header('Content-Type: application/json');
		echo json_encode($this->Data);
	}
}
