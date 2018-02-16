<?php
function pmui_delete_user($uid){		

	$post = new PMXI_Post_Record();
	$post->get_by_post_id($uid)->isEmpty() or $post->delete();	
	
}
?>