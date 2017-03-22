<?php

$selected = '';
$selectDate='';
$records = array();
# array that stores all the attributes needed for each row
$row = array();
$no2ByHour= array();
$data = array();
$bigData = array();

# fill up the stations name in the drop down list
function get_Station($select) {
    $stations = array("Brislington","Fishponds","Parson St","Rupert St","Wells Road","Newfoundland Way");
    $options = '';

    foreach ($stations as $value) {
        if ($select == $value) {
            # if user selected a station, the drop down list will show the selected station instead of going back to the first value
            $options.='<option value"' . $value . '" selected>' . $value . '</option>';
        } else {
            $options.='<option value"' . $value . '">' . $value . '</option>';
        }
    }
    return $options;
}

# fill up the drop down list with the dates available in each stations
function get_Date($selects){
    $options ='';
    $station = $_POST['stations'];
    $getDate = array();
    # use XMLReader() to parse the XML files because streaming parser can handle big data
    $reader = new XMLReader();
    $filename = str_replace(' ', '_', $station);
    $file_name = strtolower($filename) . '_no2.xml';
    # if the reader failed to open the file or could not find the file
    if (!$reader->open($file_name)) {
        die("Failed to open 'data.xml'");
    }

    # reader reading the file line by line
    while ($reader->read()) {
        # read the attributes where the cursor is pointing
        if ($reader->nodeType == XMLReader::ELEMENT && $reader->localName == 'reading') {
            # get all the date from the file
            $getDate = $reader->getAttribute('date');
            # format the date
            $replaceSym = str_replace('/', '-', $getDate);
            $row['sortDate'] = date('Y-m-d',strtotime($replaceSym));
            $row['date'] = $reader->getAttribute('date');
            # array that holds all the row
            $records[] = $row;
        }
    }
    # sort the records accordingly
    asort($records);
    # remove duplicate date in the array
    $records = array_map("unserialize", array_unique(array_map("serialize", $records)));
    foreach ($records as $reading) {
        if ($selects == $reading['date']) {
            # if user selected a date, the drop down list will show the selected date instead of going back to the first value
            $options.='<option value"' . $reading['date'] . '" selected>' . $reading['date'] . '</option>';
        } else {
            $options.='<option value"' . $reading['date'] . '">' . $reading['date'] . '</option>';
        }
    }
    return $options;
}

# count the average of the NO2 value at each hour
function countAverage (&$records, $hour){
    # count occurence of the NO2 value at the specific hour
    $count=null;
    $countNo2=null;

    foreach ($records as $reading){
        # display NO2 value that has the same hour
        if($reading['getHour']== $hour){
            # when the variable is empty
            if($countNo2==null){
                # initialize NO2 value
                $countNo2 = $reading['no2'];
            }else{
                # add all the NO2 value
                $countNo2 += $reading['no2'];
            }
            $count++;
        }
    }
    # if there NO2 value
    if($count !=0){
        # average the NO2 value
        $avgNo2 = $countNo2/$count;
    }else{
        # when there is no NO2 value
        $avgNo2 =0;
    }
    # format the average NO2 value into 2 decimal points
    $formatAvg = number_format($avgNo2, 2, '.', '');
    $data['getHour']=$hour;
    $data['avgNo2'] = $formatAvg;
    # array that holds all the data
    $bigData[] = $data;
    # populate data into the graph
    foreach ($bigData as $r){
        echo "['" . $r['getHour'] . "'," . $r['avgNo2']. "],";
    }
    return $formatAvg;
}

# when the stations and date field are set
if (isset($_POST['stations'])||isset($_POST['date'])) {
    $selected = $_POST['stations'];
    $selectDate = $_POST['date'];
    $getHour = array();
    $getDate = array();
    # use XMLReader() to parse the XML files because streaming parser can handle big data
    $reader = new XMLReader();
    $filename = str_replace(' ', '_', $selected);
    $file_name = strtolower($filename) . '_no2.xml';
    # if the reader failed to open the file or could not find the file
    if (!$reader->open($file_name)) {
        die("Failed to open 'data.xml'");
    }
    # reader reading the file line by line
    while ($reader->read()) {
        # read the attributes where the cursor is pointing
        if ($reader->nodeType == XMLReader::ELEMENT && $reader->localName == 'reading') {
            # display the data where the time meets the requirement
            if ($reader->getAttribute('date') == $selectDate) {
                $row['time'] = $reader->getAttribute('time');
                # get the first two strings as hour
                $row['getHour'] = substr(($row['time']), 0,2);
                $row['no2'] = $reader->getAttribute('no2');
                # array that holds all the rows
                $records[] = $row;
            }
        }
    }
}else{
  # if the station field is not set
  echo "<center><b>Choose a Station</b></center>";
}

?>

<html>
    <head>
      <!-- Google Chart API -->
      <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
      <script type="text/javascript">
      google.charts.load('current', {packages: ['corechart', 'line']});
      google.charts.setOnLoadCallback(drawBasic);

    function drawBasic() {

          var data = new google.visualization.DataTable();
          data.addColumn('string', 'X');
          data.addColumn('number', '<?php echo $_POST['stations']." on ". $_POST['date'] ;?>');

          data.addRows([
            <?php
            # populate data into the graph within 24 hours
            for($hour=0;$hour<24;$hour++){
                $no2ByHour[$hour] = countAverage($records, $hour);
            }
            ?>
          ]);

          var options = {
            title: ' <?php echo $_POST['stations']; ?> NO2 Concentration (µg/m³) on <?php echo $_POST['date']; ?> ',
            hAxis: {
              title: 'Hours' ,minValue:0,maxValue:24
            },
            vAxis: {
              title: 'NO2 Concentration (µg/m³)'
            },
                pointSize: 3,
                pointShape: 'circle'
            };

          var chart = new google.visualization.LineChart(document.getElementById('chart_div'));

          chart.draw(data, options);
        }
       </script>
    </head>
    <body>
    <center>
    <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST">
        <p>Station:
            <select name="stations" onchange="this.form.submit();">
                <?php echo get_Station($selected); ?>
            </select>
            <select name="date" onchange="this.form.submit();">
                <?php echo get_Date($selectDate); ?>
            </select>
    </form></p>
    <!-- draw the chart -->
    <div id="chart_div" style="width: 1500px; height: 500px"></div>
  </center>
</body>
</html>
