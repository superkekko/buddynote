<?php
class controller {

	//instantiate class
	function __construct() {
		$f3 = Base::instance();

		$main_path = dirname(__DIR__);
		$f3->set('main_path', $main_path);

		$f3->set('LOCALES', $main_path.'/dict/');
		$f3->set('FALLBACK', 'en');

		$site = $f3->get('site');
		if (!empty($site['port'])) {
			$f3->set('PORT', $site['port']);
		}
		
		$site_url = $_SERVER['REQUEST_SCHEME'].'://'.$_SERVER['HTTP_HOST'];
		$f3->set('site_url', $site_url);

		//check if DB is empty and create structure
		//check previus DB presence
		if (!file_exists($main_path.'/data/database.db')) {
			$install = true;
		} else {
			$install = false;
		}

		$f3->set('DB', new DB\SQL('sqlite:'.$main_path.'/data/database.db'));
		$db = $f3->get('DB');
		
		//create DB structure if file not exist
		if ($install) {
			$db->exec("CREATE TABLE note (
			    id      	INTEGER PRIMARY KEY AUTOINCREMENT,
			    name    	TEXT,
			    path		TEXT,
			    version		INTEGER NOT NULL,
			    tags    	TEXT,
			    list    	TEXT,
			    group_id	TEXT,
			    share		INTEGER,
			    user_ins	TEXT,
			    time_ins	TEXT,
			    user_upd	TEXT,
			    time_upd	TEXT
			)");

			$db->exec("CREATE TABLE user (
			    id      	INTEGER PRIMARY KEY AUTOINCREMENT,
			    user_id 	TEXT    UNIQUE,
			    group_id	TEXT,
			    superadmin	INTEGER NOT NULL,
			    bearer		TEXT    NOT NULL,
			    password	TEXT	NOT NULL,
			    private_key TEXT
			)");

			$db->exec("CREATE TABLE user_session (
			    id           INTEGER PRIMARY KEY AUTOINCREMENT,
			    user_id      TEXT    NOT NULL,
			    token        TEXT    NOT NULL,
			    token_expire TEXT    NOT NULL
			)");

			if (!file_exists($main_path.'/data/secret.json')) {
				$secret = ['key' => $this->generateRandomString(250),
					'iv' => $this->generateRandomString(250)];
				file_put_contents($main_path.'/data/secret.json', json_encode($secret, JSON_INVALID_UTF8_IGNORE));
			}

			$db->exec("INSERT INTO user (user_id, group_id, superadmin, bearer, password) VALUES(?,?,?,?,?)", array('superadmin', 'superadmin', 1, $this->generateRandomString(50), $this->encriptDecript($f3, 'superadmin')));

			file_put_contents($main_path.'/data/db_version', '1.0.0', LOCK_EX);
		}

		foreach (glob($main_path.'/setup/*') as $file) {
			$file_name = basename($file, '.sql');
			$actual_version = file_get_contents($main_path.'/data/db_version');
		
			if (version_compare($actual_version, $file_name, "<") && $file_name != 'install') {
				$query = file_get_contents($file);
				$query = explode(";", $query);
				$db->exec($query);
				file_put_contents($main_path.'/data/db_version', $file_name, LOCK_EX);
			}
		}

		$f3->set('formatDate', function ($date, $empty = '', $time = false, $second = false, $short=false) {
			return $this->formatDate($date, $empty, $time, $second, $short);
		});

		$f3->set('formatNumber', function ($value, $empty = '', $decimal = false, $money = false, $simple = false) {
			return $this->formatNumber($value, $empty, $decimal, $money, $simple);
		});

		$f3->set('generateRandom', function ($length, $type) {
			return $this->generateRandomString($length, $type);
		});

		$f3->set('formatTime', function ($time) {
			return $this->formatTime($time);
		});
	}

	//custom error page
	function error($f3) {
		$log = new Log('error.log');
		$log->write($f3->get('ERROR.code').' - '.$f3->get('ERROR.text'));
		echo Template::instance()->render('service.html');
	}

	function encriptDecript($f3, $string, $action = 'e') {
		$main_path = $f3->get('main_path');
		$secret = json_decode(file_get_contents($main_path.'/data/secret.json'), true);
		$secret_key = $secret['key'];
		$secret_iv = $secret['iv'];
		$output = false;

		$encrypt_method = "AES-256-CBC";
		$key = hash('sha256', $secret_key);

		$iv = substr(hash('sha256', $secret_iv), 0, 16);
		if ($action == 'e') {
			$output = base64_encode(openssl_encrypt($string, $encrypt_method, $key, 0, $iv));
		} else if ($action == 'd') {
			$output = openssl_decrypt(base64_decode($string), $encrypt_method, $key, 0, $iv);
		}

		return $output;
	}

	function generateRandomString($length, $type = 'mix') {
		$lowercase = 'abcdefghijklmnopqrstuvwxyz';
		$uppercase = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
		$number = '0123456789';
		$special = '!?;:@,.+-=/*()';
		if ($type == 'mix') {
			$characters = $lowercase.$uppercase.$number.$special;
		} elseif ($type == 'letter') {
			$characters = $lowercase.$uppercase;
		} elseif ($type == 'number') {
			$characters = $number;
		} elseif ($type = 'special') {
			$characters = $special;
		} else {
			return null;
		}
		$charactersLength = strlen($characters);
		$randomString = '';
		for ($i = 0; $i < $length; $i++) {
			$randomString .= $characters[rand(0, $charactersLength - 1)];
		}
		return $randomString;
	}

	function formatNumber($value, $empty = '', $decimal = false, $money = false, $simple = false) {
		if (!isset($value)) {
			return $empty;
		} else {
			if ($decimal) {
				if ($simple) {
					$number = number_format($value, 2, ',', '');
				} else {
					$number = number_format($value, 2, ',', '.');
				}
			} else {
				$number = number_format($value, 0, ',', '.');
			}
		}

		if ($money) {
			$number = $number.' €';
		}

		return $number;
	}

	function formatDate($date, $empty = '', $time = false, $second = false, $short = false) {
		if ($time && !$second) {
			$format = 'd/m/Y H:i';
		} elseif ($time && $second) {
			$format = 'd/m/Y H:i:s';
		} elseif($short){
			$format = 'YmdHis';
		} else {
			$format = 'd/m/Y';
		}

		if (empty($date)) {
			return $empty;
		} else {
			return date($format, strtotime($date));
		}
	}

	function formatTime($seconds) {
		if (!empty($seconds)) {
			$dtF = new \DateTime('@0');
			$dtT = new \DateTime("@$seconds");
			return $dtF->diff($dtT)->format('%a d, %h h, %i m');
		} else {
			return 'N/A';
		}

	}
}