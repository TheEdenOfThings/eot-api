<?php
include 'init.php';

$h = fopen('/tmp/sensors.log', 'a');
fwrite($h, file_get_contents('php://input')."\n");
//fclose($h);

$stationId  = (int)$_GET['id'];
$sensorType = trim($_GET['type']);

// Params from sensors
$params = array();
parse_str(file_get_contents('php://input'), $params);
fwrite($h, var_export($params, true));
fclose($h);

//$value    = trim(@$_POST['value']);
$sequence = (int)@$params['sequence'];
$live     = ((int)@$params['live']) == 0 ? false : true;

// Sensor team haven't used 'value' for values...
$value = '?';
$sensorValuesFields = array('Temp_0', 'systemtemp', 'Humidity', 'light');
foreach ($sensorValuesFields as $f)
{
	if (isset($params[$f]))
		$value = trim($params[$f]);
}

$sql = <<<EOF
INSERT INTO sensor_data
(sensor_id, value, sequence, date_added)
VALUES
((
    SELECT se.id
    FROM stations s

    INNER JOIN zones z
    ON s.zone_id = s.id

    INNER JOIN locations l
    ON z.id = l.zone_id

    INNER JOIN sensors se
    ON l.id = se.location_id

    INNER JOIN sensor_types st
    ON se.sensor_type_id = st.id

    WHERE s.id=:stationId AND st.type=:sensorType

    LIMIT 1
), 
:value, :sequence, NOW())
EOF;

try
{
    $sth = $dbh->prepare($sql);
    $sth->execute(array(':stationId'  => $stationId,
                        ':sensorType' => $sensorType,
                        ':value'      => $value,
                        ':sequence'   => $sequence
    ));
}
catch (Exception $e)
{
    // TODO: Check exception and echo better error code
    echo '1';
    exit();
}

echo '0';
