<?php

function re_name(){
	$is_file = $_GET['is_file'];
	$rnm_dir = urldecode($_GET['rnm_dir']);
	$new_name = $_GET['new_name'];
	
	if($is_file){
		$inf = pathinfo($rnm_dir);
		$ext = $inf['extension'];
		$dir = $inf['dirname'];
		
		$n_inf = pathinfo($new_name);
		$n_ext = $n_inf['extension'];
		
		$n_file = $dir.'/'.$new_name.( strlen($n_ext)>2 ? '' : '.'.$ext);
		@rename($rnm_dir, $n_file);
		$out['name'] = $n_file;
		echo json_encode($out);
	} else {
		$dir = dirname($rnm_dir);
		$n_dir = $dir.'/'.$new_name;
		@rename($rnm_dir, $n_dir);
		$out['name'] = $n_dir;
		echo json_encode($out);
	}
}

if ($_REQUEST['function'] == 'rename'){
	re_name();
}

?>
