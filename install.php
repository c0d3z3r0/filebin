<?php

if (version_compare(PHP_VERSION, '5.3.0') < 0) {
	echo "Just a heads up: Filebin has not been tested with php older than 5.3. You might run into problems.";
}

$errors = "";

define('SELF', pathinfo(__FILE__, PATHINFO_BASENAME));
define('FCPATH', str_replace(SELF, "", __FILE__));
if (getenv("HOME") == "") {
	putenv('HOME='.FCPATH);
}

if (file_exists(FCPATH."is_installed")) {
	exit("already installed\n");
}

$old_path = getenv("PATH");
putenv("PATH=$old_path:/usr/local/bin:/usr/bin:/bin:/usr/local/sbin:/usr/sbin:/sbin");

// test exec()
exec("echo -n works") == "works" || $errors .= "exec() failed\n";

// test passthru()
ob_start();
passthru("echo -n works");
$buf = ob_get_contents();
ob_end_clean();
$buf == "works" || $errors .= "passthru() failed\n";

// test perl deps
$perldeps = array(
	"HTML::FromANSI",
	"Text::Markdown"
);
foreach ($perldeps as $dep) {
	ob_start();
	passthru("perl 2>&1 -M'$dep' -e1");
	$buf = ob_get_contents();
	ob_end_clean();
	if ($buf != "") {
		$errors .= " - failed to find perl module: $dep.\n";
		$errors .= $buf;
	}
}

// test memcache
if (!class_exists("Memcache")) {
	$errors .= " - Missing \"Memcache\" php class. Please install your distribution's package of http://pecl.php.net/package/memcache\n";
}

// test pygmentize
ob_start();
passthru("pygmentize -V 2>&1", $buf);
ob_end_clean();
if ($buf != "0") {
	$errors .= " - Error when testing pygmentize: Return code was \"$buf\".\n";
}

// test qrencode
ob_start();
passthru("qrencode -V 2>&1", $buf);
ob_end_clean();
if ($buf != "0") {
	$errors .= " - Error when testing qrencode: Return code was \"$buf\".\n";
}


if ($errors != "") {
	echo nl2br("Errors occured:\n");
	echo nl2br($errors);
} else {
// TODO: Make this an actual installer
	file_put_contents(FCPATH."is_installed", "true");
	echo nl2br("Tests completed.\n"
		."The following steps remain:\n"
		." - copy the files from ./application/config/example/ to ./application/config/ and edit them to suit your setup\n"
		." - the database will be set up automatically\n"
	);
}
