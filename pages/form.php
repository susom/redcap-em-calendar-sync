<?php

namespace Stanford\CalSync;

/** @var \Stanford\CalSync\CalSync $this */
try {
    ?>
    <script>
        function popupCal(cal_id, width) {
            window.open(app_path_webroot + 'Calendar/calendar_popup.php?pid=' + pid + '&width=' + width + '&cal_id=' + cal_id, 'myWin', 'width=' + width + ', height=250, toolbar=0, menubar=0, location=0, status=0, scrollbars=1, resizable=1');
        }

        function htmlEscape(str) {
            return str
                .replace(/&/g, '&amp;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#39;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;');
        }

        $(document).ready(function () {
            var fields = <?php print json_encode($this->escape($this->calendars)) ?>;
            //console.log("Fields:",fields);

            // Loop through each field_name
            $.each(fields, function (field_name, params) {
                //console.log("Here with " + field_name, params);

                if (params.cal) {
                    //console.log(params.cal);
                    notes = htmlEscape(params.cal.notes);
                    btn = $('<span style="margin-left: 12px;" data-toggle="tooltip" title="' + notes + '" class="btn btn-xs btn-primary"><span class="glyphicon glyphicon-calendar"></span> Open</span>').click(function () {
                        popupCal(params.cal.cal_id, 800)
                    });
                } else {
                    // Calendar entry does not exist
                    btn = $('<a style="margin-left: 12px;" href="#" data-toggle="tooltip" title="This record has no calendar entries for this date/time"><span class="badge badge-danger"><span class="far fa-calendar-alt"></span></span></a>');
                }
                $('input[name="' + field_name + '"]').parent().append(btn);
            });

            $('[data-toggle="tooltip"]').tooltip();
        });
    </script>
    <?php
} catch (\Exception $e) {
    echo $e->getMessage();
}