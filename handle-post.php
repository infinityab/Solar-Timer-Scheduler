
<?php

// run now or stop now

foreach($devices as $deviceName => $devicePin) {
    $actionPar = $deviceName . 'Action';
    $durationPar = $deviceName . 'Duration';
    $actionPar = str_replace(' ', '_', $actionPar);
    $durationPar = str_replace(' ', '_', $durationPar);
    if (isset($_POST[$actionPar])) {
        $turnOn = $_POST[$actionPar] == 'Turn on';
        runGpio("write", $devicePin[0], $turnOn ? "1" : "0");
        if (isset($_POST[$durationPar]) && $_POST[$durationPar]) { // something other than 0
            issueAt($deviceName, $_POST[$durationPar], $turnOn ? "0" : "1");
        }
    }

    $ConfigPar = $devicePin[0] . '-Config';
    $rewrite_config = False;
    if (isset($_POST[$ConfigPar]) && $_POST[$ConfigPar]) {
        $rewrite_config = True;
        $configSubmit = True;
    }

    $SchedulePar = $devicePin[0] . '-ScheduleConf'; // load the Post array pointer
    if (isset($_POST[$SchedulePar])) { //  schedule to be  set
        $SchedPost = $_POST[$SchedulePar];
        $rewrite_config = True;
    }
}

$powerReserve = $_POST['powerreserve'];
if ($powerReserve == "") $powerReserve = 0; // check for null
$autoOnhr = ltrim($_POST['autoOnhr'], '0');
if ($autoOnhr == "") $autoOnhr = 0; // check for null
$autoOnmin = ltrim($_POST['autoOnmin'], '0');
if ($autoOnmin == "") $autoOnmin = 0;
$autoOffhr = ltrim($_POST['autoOffhr'], '0');
if ($autoOffhr == "") $autoOffhr = 0;
$autoOffmin = ltrim($_POST['autoOffmin'], '0');
if ($autoOffmin == "") $autoOffmin = 0;
$hwsautoOffhr = ltrim($_POST['hwsautoOffhr'], '0');
if ($hwsautoOffhr == "") $hwsautoOffhr = 0;
$hwsautoOffmin = ltrim($_POST['hwsautoOffmin'], '0');
if ($hwsautoOffmin == "") $hwsautoOffmin = 0;

if ($rewrite_config) {
    $source = "config.php";
    $target = "configbkup.php";
    $handle = fopen($source, 'r');
    $handle_out = fopen($target, 'w');
    if ($handle) {
        while (($line = fgets($handle)) !== false) {
            if (substr($line, 0, 8) == '$devices') {
                fwrite($handle_out, $line);
                while (($line = fgets($handle)) !== false) {
                    if (substr($line, 0, 2) == ");") {
                        break;
                    }
                }

                foreach($devices as $deviceName => $devicePin) {
                    $power = $_POST['powerTarget'];
                    $AutoPar = $devicePin[0] . '-Auto'; // Auto Power Mgmnt
                    $SchedulePar = $devicePin[0] . '-ScheduleConf'; // last schedule to be configured
                    $SuspendPar = $devicePin[0] . '-Suspend';
                    $CloudPar = $devicePin[0] . '-Cloud';
                    $DowMon = $devicePin[0] . '-DowMon';
                    $DowTue = $devicePin[0] . '-DowTue';
                    $DowWed = $devicePin[0] . '-DowWed';
                    $DowThu = $devicePin[0] . '-DowThu';
                    $DowFri = $devicePin[0] . '-DowFri';
                    $DowSat = $devicePin[0] . '-DowSat';
                    $DowSun = $devicePin[0] . '-DowSun';
                    fwrite($handle_out, "    \"" . $deviceName . "\" => array(" . $devicePin[0] . ","); //  Gpio pin number
                    if ($_POST[$devicePin[0] . "-Auto"] == "") {
                        fwrite($handle_out, (0) . ",");
                    }
                    else {
                        fwrite($handle_out, ($_POST[$devicePin[0] . "-Auto"]) . ","); // device pin 1
                    }

                    fwrite($handle_out, $SchedPost . ","); // just write out current schedule for show-devices page

                    // Schedule 1

                    if ($SchedPost == 0 && !$SchedPost && $configSubmit) { // update config according to settings
                        if ($_POST[$devicePin[0] . "-Trigger"] == "") {
                            fwrite($handle_out, (0) . ",");
                        }
                        else {
                            fwrite($handle_out, ($_POST[$devicePin[0] . "-Trigger"]) . ","); // device pin 3  Trigger target
                        }

                        fwrite($handle_out, ($_POST[$SuspendPar] == "on" ? "1" : "0") . ","); // Suspend device pin 4
                        if ($_POST[$devicePin[0] . "-Power"] == "") { // Auto Power
                            fwrite($handle_out, (0) . ",");
                        }
                        else {
                            fwrite($handle_out, ($_POST[$devicePin[0] . "-Power"]) . ","); // device pin $
                        }

                        fwrite($handle_out, ($_POST[$DowMon] == "on" ? "1" : "0") . ","); // device pin 6 S1 Mon
                        fwrite($handle_out, ($_POST[$DowTue] == "on" ? "1" : "0") . ","); // device pin 7 S1 Tue
                        fwrite($handle_out, ($_POST[$DowWed] == "on" ? "1" : "0") . ","); // device pin 8 S1 Wed
                        fwrite($handle_out, ($_POST[$DowThu] == "on" ? "1" : "0") . ","); // device pin 9 S1 Thu
                        fwrite($handle_out, ($_POST[$DowFri] == "on" ? "1" : "0") . ","); // device pin 10 S1 Fri
                        fwrite($handle_out, ($_POST[$DowSat] == "on" ? "1" : "0") . ","); // device pin 11 S1 Sat
                        fwrite($handle_out, ($_POST[$DowSun] == "on" ? "1" : "0") . ","); // device pin 12 S1 Sun
                    }
                    else {
                        if ($configSubmit) { // write Power from ANY schedule into first location
                            if ($_POST[$devicePin[0] . "-Trigger"] == "") {
                                fwrite($handle_out, (0) . ",");
                            }
                            else {
                                fwrite($handle_out, ($_POST[$devicePin[0] . "-Trigger"]) . ","); // device pin 3  Trigger target
                            }
                        }
                        else {
                            fwrite($handle_out, ($devicePin[3]) . ","); // else just copy existing trigger setting
                        }

                        for ($dev = 4; $dev <= 12; $dev++) {
                            fwrite($handle_out, ($devicePin[$dev]) . ","); // else just copy existing setting
                        }
                    }

                    // Schedule 2

                    if (($SchedPost == 1) && ($configSubmit)) {
                        if ($_POST[$devicePin[0] . "-Trigger"] == "") {
                            fwrite($handle_out, (0) . ",");
                        }
                        else {
                            fwrite($handle_out, ($_POST[$devicePin[0] . "-Trigger"]) . ",");
                        }

                        fwrite($handle_out, ($_POST[$SuspendPar] == "on" ? "1" : "0") . ",");
                        if ($_POST[$devicePin[0] . "-Power"] == "") { // Auto Power
                            fwrite($handle_out, (0) . ",");
                        }
                        else {
                            fwrite($handle_out, ($_POST[$devicePin[0] . "-Power"]) . ",");
                        }

                        fwrite($handle_out, ($_POST[$DowMon] == "on" ? "1" : "0") . ","); // S2 device pin 16 Mon 
                        fwrite($handle_out, ($_POST[$DowTue] == "on" ? "1" : "0") . ","); // S2 device pin 17 Tue
                        fwrite($handle_out, ($_POST[$DowWed] == "on" ? "1" : "0") . ","); // S2 device pin 18 Wed
                        fwrite($handle_out, ($_POST[$DowThu] == "on" ? "1" : "0") . ","); // S2 device pin 19 Thu
                        fwrite($handle_out, ($_POST[$DowFri] == "on" ? "1" : "0") . ","); // S2 device pin 20 Fri
                        fwrite($handle_out, ($_POST[$DowSat] == "on" ? "1" : "0") . ","); // S2 device pin 21 Sat
                        fwrite($handle_out, ($_POST[$DowSun] == "on" ? "1" : "0") . ","); // S2 device pin 22 Sun
                    }
                    else {
                        for ($dev = 13; $dev <= 22; $dev++) {
                            fwrite($handle_out, ($devicePin[$dev]) . ",");
                        }
                    }

                    // Schedule 3

                    if (($SchedPost == 2) && ($configSubmit)) {
                        if ($_POST[$devicePin[0] . "-Trigger"] == "") {
                            fwrite($handle_out, (0) . ",");
                        }
                        else {
                            fwrite($handle_out, ($_POST[$devicePin[0] . "-Trigger"]) . ",");
                        }

                        fwrite($handle_out, ($_POST[$SuspendPar] == "on" ? "1" : "0") . ",");
                        if ($_POST[$devicePin[0] . "-Power"] == "") { // Auto Power
                            fwrite($handle_out, (0) . ",");
                        }
                        else {
                            fwrite($handle_out, ($_POST[$devicePin[0] . "-Power"]) . ",");
                        }

                        fwrite($handle_out, ($_POST[$DowMon] == "on" ? "1" : "0") . ","); // device pin 26 S2 Mon
                        fwrite($handle_out, ($_POST[$DowTue] == "on" ? "1" : "0") . ","); // device pin 27 S2 Tue
                        fwrite($handle_out, ($_POST[$DowWed] == "on" ? "1" : "0") . ","); // device pin 28 S2 Wed
                        fwrite($handle_out, ($_POST[$DowThu] == "on" ? "1" : "0") . ","); // device pin 29 S2 Thu
                        fwrite($handle_out, ($_POST[$DowFri] == "on" ? "1" : "0") . ","); // device pin 30 S2 Fri
                        fwrite($handle_out, ($_POST[$DowSat] == "on" ? "1" : "0") . ","); // device pin 31 S2 Sat
                        fwrite($handle_out, ($_POST[$DowSun] == "on" ? "1" : "0") . ","); // device pin 32 S2 Sun
                    }
                    else {
                        for ($dev = 23; $dev <= 32; $dev++) {
                            fwrite($handle_out, ($devicePin[$dev]) . ",");
                        }
                    }

                    // ***************
                    // Schedule 4

                    if (($SchedPost == 3) && ($configSubmit)) {
                        if ($_POST[$devicePin[0] . "-Trigger"] == "") {
                            fwrite($handle_out, (0) . ",");
                        }
                        else {
                            fwrite($handle_out, ($_POST[$devicePin[0] . "-Trigger"]) . ",");
                        }

                        fwrite($handle_out, ($_POST[$SuspendPar] == "on" ? "1" : "0") . ","); // Suspend
                        if ($_POST[$devicePin[0] . "-Power"] == "") { // Auto Power
                            fwrite($handle_out, (0) . ",");
                        }
                        else {
                            fwrite($handle_out, ($_POST[$devicePin[0] . "-Power"]) . ",");
                        }

                        fwrite($handle_out, ($_POST[$DowMon] == "on" ? "1" : "0") . ","); // device pin 26 S2 Mon
                        fwrite($handle_out, ($_POST[$DowTue] == "on" ? "1" : "0") . ","); // device pin 27 S2 Tue
                        fwrite($handle_out, ($_POST[$DowWed] == "on" ? "1" : "0") . ","); // device pin 28 S2 Wed
                        fwrite($handle_out, ($_POST[$DowThu] == "on" ? "1" : "0") . ","); // device pin 29 S2 Thu
                        fwrite($handle_out, ($_POST[$DowFri] == "on" ? "1" : "0") . ","); // device pin 30 S2 Fri
                        fwrite($handle_out, ($_POST[$DowSat] == "on" ? "1" : "0") . ","); // device pin 31 S2 Sat
                        fwrite($handle_out, ($_POST[$DowSun] == "on" ? "1" : "0") . ","); // device pin 32 S2 Sun
                    }
                    else {
                        for ($dev = 33; $dev <= 42; $dev++) {
                            fwrite($handle_out, ($devicePin[$dev]) . ",");
                        }
                    }

                    // ***************
                    // Schedule 5

                    if (($SchedPost == 4) && ($configSubmit)) {
                        if ($_POST[$devicePin[0] . "-Trigger"] == "") {
                            fwrite($handle_out, (0) . ",");
                        }
                        else {
                            fwrite($handle_out, ($_POST[$devicePin[0] . "-Trigger"]) . ",");
                        }

                        fwrite($handle_out, ($_POST[$SuspendPar] == "on" ? "1" : "0") . ","); // Suspend
                        if ($_POST[$devicePin[0] . "-Power"] == "") { // Auto Power
                            fwrite($handle_out, (0) . ",");
                        }
                        else {
                            fwrite($handle_out, ($_POST[$devicePin[0] . "-Power"]) . ",");
                        }

                        fwrite($handle_out, ($_POST[$DowMon] == "on" ? "1" : "0") . ","); // S5 Mon
                        fwrite($handle_out, ($_POST[$DowTue] == "on" ? "1" : "0") . ","); // S5 Tue
                        fwrite($handle_out, ($_POST[$DowWed] == "on" ? "1" : "0") . ","); // S5 Wed
                        fwrite($handle_out, ($_POST[$DowThu] == "on" ? "1" : "0") . ","); // S5 Thu
                        fwrite($handle_out, ($_POST[$DowFri] == "on" ? "1" : "0") . ","); // S5 Fri
                        fwrite($handle_out, ($_POST[$DowSat] == "on" ? "1" : "0") . ","); // S5 Sat
                        fwrite($handle_out, ($_POST[$DowSun] == "on" ? "1" : "0") . "),"); // S5 Sun
                    }
                    else {
                        for ($dev = 43; $dev <= 51; $dev++) {
                            fwrite($handle_out, ($devicePin[$dev]) . ",");
                        }

                        fwrite($handle_out, ($devicePin[52]) . "),");
                    }

                    fwrite($handle_out, "\n");
                }

                fwrite($handle_out, $line);
            }
            elseif (substr($line, 0, 13) == '$powerReserve') {
                fwrite($handle_out, "\$powerReserve = " . $powerReserve . ";\n");
            } // check and update Power Lag in config file
            elseif (substr($line, 0, 9) == '$autoOnhr') {
                fwrite($handle_out, "\$autoOnhr = " . $autoOnhr . ";\n");
            }
            elseif (substr($line, 0, 10) == '$autoOnmin') {
                fwrite($handle_out, "\$autoOnmin = " . $autoOnmin . ";\n");
            } // check and update Auto On time variable in config file
            elseif (substr($line, 0, 10) == '$autoOffhr') {
                fwrite($handle_out, "\$autoOffhr = " . $autoOffhr . ";\n");
            }
            elseif (substr($line, 0, 11) == '$autoOffmin') {
                fwrite($handle_out, "\$autoOffmin = " . $autoOffmin . ";\n");
            } // check and update Auto On time variable in config file
            elseif (substr($line, 0, 13) == '$hwsautoOffhr') {
                fwrite($handle_out, "\$hwsautoOffhr = " . $hwsautoOffhr . ";\n");
            }
            elseif (substr($line, 0, 14) == '$hwsautoOffmin') {
                fwrite($handle_out, "\$hwsautoOffmin = " . $hwsautoOffmin . ";\n");
            }
            else { // check and update Auto Off time variable in config file
                fwrite($handle_out, $line);
            }
        }
    }
    else {
        // error opening the file.
    }

    $configSubmit = False;
    fclose($handle);
    unlink($source);
    fclose($handle_out);
    $file = '/var/www/rasptimer/configbkup.php'; // Added backup file
    $newfile = '/var/www/rasptimer/config.php';
    if (!copy($file, $newfile)) {
        echo "failed to copy $file...\n";
    }
    header("Location: $baseUrl/configure.php");
    exit(0);
}

// schedule

$scheduleSave = False;

if (isset($_POST['change-schedule']) && $_POST['change-schedule'] == 'Save') {
    $schedule = readCrontab(); // get the current schedule
    $schedDevName = $deviceName = $_POST['deviceName']; // find out which one is to change
    $scheduleSave = True;
    if (isset($devices[$deviceName])) {
        if ($_POST['scheduled'] == 'yes') { // run regularly or not
            function rangeCheck($val, $min, $max)
            {
                $val = intval($val);
                if ($val < $min) {
                    $val = $min;
                }
                else
                if ($val > $max) {
                    $val = $max;
                }

                return $val;
            }   // add and check new schedule
            $schedHr = $schedule[$deviceName]['timeOn']['hour'] = rangeCheck($_POST['timeOnHour'], 0, 23);
            $schedMin = $schedule[$deviceName]['timeOn']['min'] = rangeCheck($_POST['timeOnMin'], 0, 59);
            $schedDurHr = $schedule[$deviceName]['duration']['hour'] = rangeCheck($_POST['durationHour'], 0, 23);
            $schedDurMin = $schedule[$deviceName]['duration']['min'] = rangeCheck($_POST['durationMin'], 0, 59);
            $runOn = 1;
        }
        else {
            $runOn = 0;
            $schedule[$deviceName] = NULL; // a schedule which is now NON-run is removed
        }
    }
}

if ($scheduleSave) {
    $schedulenumPar = $schedulenums . '-Schedule';
    $scheduleName = "Schedule-" . strval($_POST[$schedulenumPar]);
    $source = "config2.php";
    $target = "/tmp/config2.php";
    $handle = fopen($source, 'r');
    $handle_out = fopen($target, 'w');
    if ($handle) {
        while (($line = fgets($handle)) !== false) {
            if (substr($line, 0, 10) == '$schedules') {
                fwrite($handle_out, $line);
                while (($line = fgets($handle)) !== false) {
                    if (substr($line, 0, 2) == ");") {
                        break;
                    }
                } // while 2
                foreach($schedules as $scheduleNums => $scheduleKey) {
                    fwrite($handle_out, "  \"" . $scheduleNums . "\" => array(");
                    fwrite($handle_out, "\n");
                    foreach($scheduleKey as $deviceNames => $devicePins) {
                        fwrite($handle_out, "    \"" . $deviceNames . "\" => array(" . $devicePins[0] . ",");
                        fwrite($handle_out, ($devicePins[1]) . ",");
                        if (($schedDevName == $deviceNames) && ($scheduleNums == $scheduleName)) {
                            $devicePins[2] = $runOn;
                            if ($runOn) {
                                $devicePins[3] = $schedHr; // add and check new schedule
                                $devicePins[4] = $schedMin;
                                $devicePins[5] = $schedDurHr;
                                $devicePins[6] = $schedDurMin;
                            }
                        }

                        fwrite($handle_out, ($devicePins[2]) . ",");
                        fwrite($handle_out, ($devicePins[3]) . ",");
                        fwrite($handle_out, ($devicePins[4]) . ",");
                        fwrite($handle_out, ($devicePins[5]) . ",");
                        fwrite($handle_out, ($devicePins[6]) . "),");
                        fwrite($handle_out, "\n");
                    } // for each 2 Device Names array -> schedules
                    fwrite($handle_out, "    ),");
                    fwrite($handle_out, "\n");
                } // for each 1 Schedules array
                fwrite($handle_out, $line);
            }
            else { // $schedules search
                fwrite($handle_out, $line);
            }
        } // while 1
    }
    else { // handle
        // error opening the file.
    }
    fclose($handle);
    unlink($source);
    fclose($handle_out);
    $file = '/tmp/config2.php'; // Added file save to update changes - DRC
    $newfile = '/var/www/rasptimer/config2.php';
    if (!copy($file, $newfile)) {
        echo "failed to copy $file...\n";
    }
    $Schedule = checkSchedules($schedule); // bump all the schedules as required
    writeCrontab($Schedule); // send all current and new schedule to crontab
}
header("Location: $baseUrl/");
exit(0);
