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
		$path = $f3->get('POST.path');
		$data = $f3->get('POST.html');

		if (!empty($path) && !empty($data)) {
			$path = $f3->get('main_path')."/data/files/".$path;
			
			$write = file_put_contents($path, $data, LOCK_EX);

			if ($write != false) {
				$return_array = ['data' => array('date' => date("Y-m-d H:i:s"), 'byte' => $write)];
				header('Content-Type: application/json');
				echo json_encode($return_array);
			} else {
				header('Content-Type: application/json');
				http_response_code(500);
			}
		} else {
			header('Content-Type: application/json');
			http_response_code(400);
		}
	}
}