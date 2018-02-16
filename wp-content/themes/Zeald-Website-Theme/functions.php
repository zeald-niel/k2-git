<?php /*

  This file is part of a child theme called Zeald Website Theme.
  Functions in this file will be loaded before the parent theme's functions.
  For more information, please read https://codex.wordpress.org/Child_Themes.

*/

function elegant_enqueue_css() { wp_enqueue_style( 'parent-style', get_template_directory_uri() . '/style.css' ); }

add_action( 'wp_enqueue_scripts', 'elegant_enqueue_css' );

include('editor/footer-editor.php');

include('editor/login-editor.php');

// this code loads the parent's stylesheet (leave it in place unless you know what you're doing)

/*  Add your own functions below this line.
    ======================================== */
