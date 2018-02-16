<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
/**
 * @var Bookly\Backend\Modules\Appearance\Lib\Helper $editable
 */
?>
<div class="bookly-form">
    <?php include '_progress_tracker.php' ?>
    <div class="bookly-box">
        <?php $editable::renderText( 'bookly_l10n_info_complete_step', $this->render( '_codes', array( 'step' => 8 ), false ) ) ?>
    </div>
</div>