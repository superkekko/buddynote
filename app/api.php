<?php
class api extends authentication {
	function beforeroute($f3) {
		if (!$this->checklogged($f3)) {
			$f3->reroute("/logout");
		}
	}

	function afterroute($f3) {}

	function htmlread($f3) {
		$path = $f3->get('POST.path');

		if (!empty($path)) {
			$path = $f3->get('main_path')."/data/files/".$path;

			$text = file_get_contents($path);

			$return_array = ['data' => array('html' => $text)];
			header('Content-Type: application/json');
			echo json_encode($return_array);
		} else {
			header('Content-Type: application/json');
			http_response_code(400);
		}
	}

	function htmlwrite($f3) {
		$id = $f3->get('POST.id');
		$version = $f3->get('POST.version');
		$path = $f3->get('POST.path');
		$data = $f3->get('POST.html');

		$saved_version = $f3->get('DB')->exec("SELECT version FROM note where id=?", $id);

		if (!empty($path) && !empty($data)) {
			$path = $f3->get('main_path')."/data/files/".$path;

			if (empty($saved_version[0]) || $saved_version[0]['version'] == $version) {

				$write = file_put_contents($path, $data, LOCK_EX);

				if ($write != false) {
					$return_array = ['data' => array('message' => 'OK', 'version' => $version+1, 'date' => date("Y-m-d H:i:s"), 'byte' => $write)];
					header('Content-Type: application/json');
					echo json_encode($return_array);
				} else {
					header('Content-Type: application/json');
					http_response_code(500);
				}
			} else {
				$return_array = ['data' => array('message' => 'KO', 'error' => 'Version mismatch, refresh to update', 'date' => date("Y-m-d H:i:s"))];
				header('Content-Type: application/json');
				echo json_encode($return_array);
			}
		} else {
			header('Content-Type: application/json');
			http_response_code(400);
		}
	}
}