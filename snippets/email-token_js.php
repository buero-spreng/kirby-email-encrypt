<?php
// Loads the published plugin asset via a proper public URL
// Works in subfolder installs and avoids hardcoded /media paths

?>

<?= js('/media/plugins/kesabr/email-token/email-token.js', ['defer' => true]) ?>