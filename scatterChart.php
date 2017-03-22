<?php

$selected = '';
$year = '2016'; # define the year to populate in the graph
$time ="06:00:00"; # define the time to populate in the graph

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

# if the station field is set
if (isset($_POST['stations'])) {

    $selected = $_POST['stations'];
    $station = $_POST['stations'];
    $records = array();
    # array that stores all the attributes needed for each row
    $row = array();
    $getDate = array();
    $sortedTheDate = array();
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
            # display the data where the time meets the requirement
            if ($reader->getAttribute('time') == $time) {
                # get all the dates meet the requirement
                $getDate = $reader->getAttribute('date');
                # format the date
                $replaceSym = str_replace('/', '-', $getDate);
                $row['sortDate'] = date('Y-m-d',strtotime($replaceSym));
                # get the year first 4 string from the date
                $compareYear = substr(($row['sortDate']), 0,4);
                # if the $compareYear meets the requirement
                if($compareYear == $year){
                    $row['date'] = $reader->getAttribute('date');
                    $row['no2'] = $reader->getAttribute('no2');
                    $row['time'] = $reader->getAttribute('time');
                    # array that holds all the rows
                    $records[] = $row;
                }
            }
        }
    }
    # sort the array accordingly
    asort($records);
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
            google.charts.load('current', {'packages':['corechart']});
            google.charts.setOnLoadCallback(drawChart);

            function drawChart() {
              var data = google.visualization.arrayToDataTable([
                ['Dates', 'NO2 Concentration',{'type':'string','role':'style'}],
                <?php
                $color = '';
                # assign encoding colours for each NO2 value
                foreach ($records as $reading) {
                    if($reading['no2']>=0 && $reading['no2'] <68){
                        $color = 'rgb(156,255,156)';
                    }else if($reading['no2']>=68 && $reading['no2'] <135){
                        $color = 'rgb(49,255,0)';
                    }else if($reading['no2']>=135 && $reading['no2'] <201){
                        $color = 'rgb(49, 207, 0)';
                    }else if($reading['no2']>=201 && $reading['no2'] <268){
                        $color = 'rgb(255, 255, 0)';
                    }else if($reading['no2']>=268 && $reading['no2'] <335){
                        $color = 'rgb(255, 207, 0)';
                    }else if($reading['no2']>=335 && $reading['no2'] <401){
                        $color = 'rgb(255, 154, 0)';
                    }else if($reading['no2']>=401 && $reading['no2'] <468){
                        $color = 'rgb(255, 100, 100)';
                    }else if($reading['no2']>=468 && $reading['no2'] <535){
                        $color = 'rgb(255, 0, 0)';
                    }else if($reading['no2']>=535 && $reading['no2'] <601){
                        $color = 'rgb(153, 0, 0)';
                    }else if($reading['no2']>=601){
                        $color = 'rgb(206, 48, 255)';
                    }
                    # populate the data and assign the colour for the indicator
                    echo "['" . $reading['date'] . "'," . $reading['no2'] . ",'point{fill-color:". $color .";}'],";
                }
                ?>
              ]);

              var options = {
                title: ' <?php echo $_POST['stations']; ?> NO2 Concentration (µg/m³) in <?php echo $year." at ".$time; ?> ',
                hAxis: {title: 'Dates', viewWindow:{minValue: 0, maxValue: 365,ticks: <?php
                    echo "['";
                    foreach ($records as $readRow) {
                        echo $readRow['date'];
                        echo "','";
                    }
                    echo "']";
                    ?>}},
                vAxis: {title: 'NO2 Concentration', minValue: 0, maxValue: 15},
                legend: ''
              };

              var chart = new google.visualization.ScatterChart(document.getElementById('chart_div'));
              chart.draw(data, options);
            }
        </script>
    </head>
    <body>
    <center>
    <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST">
        <p><b>Station:</b>
            <select name="stations" onchange="this.form.submit();">
                <?php echo get_Station($selected); ?>
            </select>
    </form></p>
    <!-- draw chart -->
    <div id="chart_div" style="width: 1500px; height: 500px;"></div>
    <!-- print the colour encoding -->
    <img src="http://www.cems.uwe.ac.uk/~s5-low/atwd2/assignment/legend.PNG"/>

    </center>
    </body>
</html>
