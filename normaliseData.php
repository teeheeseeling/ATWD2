<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
    # Normalise the data to remove unwanted data in the file
    $loc=array("Brislington","fishponds","parson st","rupert st","wells road","newfoundland way");

    for($i=0;$i<6;$i++){
        # use XMLReader() to parse the XML files because streaming parser can handle big data
        $reader = new XMLReader();
        # array that holds all the rows
        $records=array();
        $filename = str_replace(' ' , '_', $loc[$i]);
        $file_name = strtolower($filename).'.xml';
        # if the reader failed to open the file or could not find the file
        if (!$reader->open($file_name)) {
            die("Failed to open 'data.xml'");
        }
        # reader starts to read the file when it opened the file
        echo 'Reading....</br>';
        # reader reading the file line by line
        while($reader->read()) {
            if ($reader->nodeType == XMLReader::ELEMENT){
                # read the attributes where the cursor is pointing
                switch($reader->name){
                    case "row":
                        # array that stores all the attributes needed for each row
                        $row=array();
                        $row['count'] = $reader->getAttribute('count');
                        break;

                    case "desc":
                        $row['desc'] = $reader->getAttribute('val');
                        break;

                    case "date":
                        $row['date'] = $reader->getAttribute('val');
                        break;

                    case "time":
                        $row['time'] = $reader->getAttribute('val');
                        break;

                    case "no2":
                        $row['no2'] = $reader->getAttribute('val');
                        break;

                    case "lat":
                        # if the $lat field is empty then store the value, to reduce redundancy
                        if(!isset($lat)){
                            $row['lat'] = $reader->getAttribute('val');
                        }
                        break;

                    case "long":
                        if(!isset($long)){
                            $row['long'] = $reader->getAttribute('val');
                            $records[]=$row;
                        }
                        break;
                }
            }
        }
            echo 'Read Complete...</br>';
            $reader->close();
            echo'Start Writing</br>';
            # write the data into a new file
            $writer = new XMLWriter();
            $fileName = strtolower($filename);
            $writer->openURI($fileName.'_no2.xml');
            $writer->startDocument('1.0','UTF-8');
            $writer->setIndent(4);
            $writer->startElement('data');
               $writer->writeAttribute('type', 'nitrogen dioxide');
               $writer->startElement('location');
               $writer->writeAttribute('id', $row['desc']);
               $writer->writeAttribute('lat', $row['lat']);
               $writer->writeAttribute('long', $row['long']);
            # loop all the reading value in records
            foreach($records as $row){
                $writer->startElement('reading');
                 $writer->writeAttribute('date', $row['date']);
                 $writer->writeAttribute('time', $row['time']);
                 $writer->writeAttribute('no2', $row['no2']);
                $writer->endElement();
            }
            $writer->endElement();
            $writer->endElement();
            $writer->endDocument();
            $writer->flush();
            # done normalise a file and loop to the next file
            echo 'Write Complete...</br>';
    }
    # finished normalise the file
    echo 'All Done!!!</br>';
?>
