<?php

$disks = array_values(array_filter(explode(" ", shell_exec("df -H | grep '/dev'"))));

print_r($disks);


