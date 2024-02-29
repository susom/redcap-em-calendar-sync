<?php

namespace Stanford\CalSync;

require_once "emLoggerTrait.php";

class CalSync extends \ExternalModules\AbstractExternalModule
{

    const CALSYNC = '@CALSYNC';

    const ALLOWED_DATE_FIELDS = array('date', 'date_ymd', 'date_mdy', 'date_dmy', 'datetime',
        'datetime_ymd', 'datetime_mdy', 'datetime_dmy', 'datetime_seconds_ymd', 'datetime_seconds_dmy', 'datetime_seconds_mdy');
    use emLoggerTrait;

    private $record = [];

    private $calendars = [];

    function redcap_save_record($project_id, $record = NULL, $instrument, $event_id, $group_id = NULL, $survey_hash = NULL, $response_id = NULL, $repeat_instance = 1)
    {

        global $Proj;

        $fieldNames = \REDCap::getFieldNames($instrument);
        foreach ($fieldNames as $fieldName) {
            // if field has CALSYNC action tag. And the field is date/datetime
            if (str_contains($Proj->metadata[$fieldName]['misc'], self::CALSYNC) and in_array($Proj->metadata[$fieldName]['element_validation_type'], self::ALLOWED_DATE_FIELDS)) {

                // now check the field value.
                $data = $this->getRecord($record);
                if ($data[$record][$event_id][$fieldName] != '') {
                    $value = $data[$record][$event_id][$fieldName];
                    try {
                        $dt = new \DateTime($value);
                    } catch (\Exception $e) {
                        $this->emError("Invalid date/time value: $value", "ERROR", "Unable to parse date/time for $term");
                        continue;
                    }
                    $date = $dt->format("Y-m-d");
                    $time = $dt->format("H:i");

                    // For inserting, we need either a time or NULL if no time component
                    $i_time = strlen($value) > 10 ? "'$time'" : "NULL";

                    $sql = sprintf("
                          insert into redcap_events_calendar
                          (record,project_id,event_id,event_date,event_time,event_status,notes)
                          values
                          ('%s'  , %d       , %d     , '%s'     , $i_time  , %d         , '%s');",
                        db_real_escape_string($record),
                        intval($project_id),
                        intval($event_id),
                        db_real_escape_string($date),
                        1,
                        "[" . date('Y-m-d H:i:s') . "] Created by " . (defined("USERID") ? USERID : '') . " from [$field] on the $instrument form"
                    );
                    //Plugin::log($sql, "DEBUG", "Insert SQL");
                    db_query($sql);
                }
            }
        }
    }

    public function redcap_data_entry_form_top(int $project_id, string $record = NULL, string $instrument, int $event_id, int $group_id = NULL, int $repeat_instance = 1)
    {
        global $Proj;

        $fieldNames = \REDCap::getFieldNames($instrument);
        foreach ($fieldNames as $fieldName) {
            // if field has CALSYNC action tag. And the field is date/datetime
            if (str_contains($Proj->metadata[$fieldName]['misc'], self::CALSYNC) and in_array($Proj->metadata[$fieldName]['element_validation_type'], self::ALLOWED_DATE_FIELDS)) {

                // now check the field value.
                $data = $this->getRecord($record);
                if ($data[$record][$event_id][$fieldName] != '') {
                    $value = $data[$record][$event_id][$fieldName];
                    try {
                        $dt = new \DateTime($value);
                    } catch (\Exception $e) {
                        $this->emError("Invalid date/time value: $value", "ERROR", "Unable to parse date/time for $term");
                        continue;
                    }
                    $date = $dt->format("Y-m-d");
                    $time = $dt->format("H:i");
                    //Plugin::log("$field = $value, date = $date and time = $time", "DEBUG");

                    $sql = sprintf("
                      select * 
                      from redcap_events_calendar
                      where
                        record = '%s'
                        and project_id = %d
                        and event_id = %d
                        and event_date = '%s'",
                        db_escape($record),
                        intval($project_id),
                        intval($event_id),
                        db_escape($date)
                    );

                    // Add time to query if date/time value includes time component
                    if (strlen($value) > 10) {
                        $sql .= sprintf("
            and event_time = '%s'",
                            db_escape($time)
                        );
                    }

                    $q = db_query($sql);
                    if (db_num_rows($q) > 0) {
                        // A result exists in the calendar
                        $row = db_fetch_assoc($q);
                        $this->calendars[$fieldName]['cal'] = $row;
                    } else {
                        // No calendar entry exists for this record/event/date/time
                        $this->calendars[$fieldName]['cal'] = null;
                    }
                } else {
                    // value is empty
                    $this->calendars[$fieldName]['cal'] = null;
                }
            }
        }

        // if calendar is not empty then inject UI
        if(!empty($this->calendars))
        {
            $this->includeFile('pages/form.php');
        }

    }

    public function includeFile($path)
    {
        include_once $path;
    }
    public
    function getRecord($record_id)
    {
        if (!$this->record) {
            $param = [
                'project_id' => $this->getProjectId(),
                'format' => 'array',
                'records' => [$record_id]
            ];
            $this->record = \REDCap::getData($param);
        }
        return $this->record;
    }

}
