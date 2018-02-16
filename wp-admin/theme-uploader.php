<?php
require_once('admin.php');
require_once('uploader/upload.php');
$title = __('Upload theme');
$parent_file = 'themes.php';
require_once('admin-header.php');
?>
<div class="wrap">
<h2><?php _e('Theme Uploader'); ?></h2>
<p><?php _e('Choose files to upload'); ?></p>
    <form id="theme_uploader" action="" enctype="multipart/form-data" method="post">
	  <?php wp_nonce_field('upload-theme'); ?>
      <input type="file" name="package" id="id_package" /><br/>
	  <input type="submit" value="<?php _e('Upload')?>" name="upload_theme" class="button"/>
    </form>
</div>
<?php
include("admin-footer.php") ?>
