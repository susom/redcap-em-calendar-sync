<?php

namespace Stanford\CalSync;

/** @var \Stanford\CalSync\CalSync $this */
try {
    ?>
    <script src="<?php echo $this->getUrl('assets/js/form.js') ?>"></script>
    <script>
        CalSync.form.fields = <?php print json_encode($this->escape($this->calendars)) ?>;
        CalSync.form.pid = <?php echo $this->getProjectId() ?>;
        CalSync.form.pid = <?php echo $this->getProjectId() ?>;
        CalSync.form.app_path_webroot = <?php echo APP_PATH_WEBROOT ?>;

        window.addEventListener("load",
            function () {
                setTimeout(function () {
                    CalSync.form.init();
                }, 100)
            }
            , true);
    </script>
    <?php
} catch (\Exception $e) {
    echo $e->getMessage();
}