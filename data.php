<?php
	$_GET_lc = array_change_key_case($_GET);

	// Creates the KML/XML Document.
	$dom = new DOMDocument('1.0', 'UTF-8');

	// Creates the root KML element and appends it to the root document.
	$node = $dom->createElementNS('http://www.opengis.net/kml/2.2', 'kml');
	$parNode = $dom->appendChild($node);

	// Creates a KML Document element and append it to the KML element.
	$dnode = $dom->createElement('Document');
	$docNode = $parNode->appendChild($dnode);

        #$baseUrl = 'http://openfiremap.org/c/';
        $baseUrl = 'http://localhost/~cwhlocal/';

	foreach (array( array('hy_u', $baseUrl.'icons/hydrant_u_17.png'),
			array('hy_p', $baseUrl.'icons/hydrant_p_17.png'),
			array('wt', $baseUrl.'icons/water_tank_17.png'),
			array('wu', $baseUrl.'icons/water_tank_unlimited_17.png') ) as $style) { 
	  $node = $dom->createElement('Style');
	  $styleNode = $docNode->appendChild($node);
	  $styleNode->setAttribute('id', $style[0]);

	  $node = $dom->createElement('IconStyle');
	  $iconStyleNode = $styleNode->appendChild($node);

	  $node = $dom->createElement('Icon');
	  $iconNode = $iconStyleNode->appendChild($node);

	  $node = $dom->createElement('href', $style[1]);
	  $iconNode->appendChild($node);

	  $node = $dom->createElement('LabelStyle');
	  $labelStyleNode = $styleNode->appendChild($node);

	  $node = $dom->createElement('color', 'ff0000cc');
	  $colorNode = $labelStyleNode->appendChild($node);
	}

	// database stuff
	$pgsql['user'] = 'cwh'; // username
	$pgsql['db'] = 'gis';      // database

	// currently we only support amenity in the url
	$what = 'fire_hydrant';

	$connect_string = ' user=' . $pgsql['user'] . ' dbname=' . $pgsql['db'];
	$pgcon = pg_connect($connect_string); 
	if ($pgcon) { // connected!	

		$bbox = $_GET_lc['bbox']; // get the bbox param from google earth
		list($bbox_south, $bbox_west, $bbox_east, $bbox_north) = split(",", $bbox); // west, south, east, north
		// Get the data from the Database Table (planet_osm_point)
		//$sql = "SELECT osm_id, name, x(way) as lon, y(way) as lat FROM planet_osm_point WHERE (amenity='" . $what . "') AND (box(point(" . $bbox_south . "," . $bbox_west . "),point(" . $bbox_east . "," . $bbox_north . ")) ~ (way)) LIMIT 1000";
		//or with transform:
		$sql = "SELECT osm_id, amenity, emergency, \"fire_hydrant:type\", \"fire_hydrant:diameter\", \"water_tank:volume\", x(transform(way,4326)) as lon, y(transform(way, 4326)) as lat FROM planet_osm_point WHERE (amenity='" . $what . "' OR emergency='" . $what . "' OR emergency='water_tank' OR emergency='fire_water_pond' OR emergency='suction_point') AND (box(point(" . $bbox_south . "," . $bbox_west . "),point(" . $bbox_east . "," . $bbox_north . ")) ~ transform(way,4326)) LIMIT 30000";
		error_log("query: $sql", 0);
		// perform query
		$query = pg_query($pgcon, $sql);
		if ($query) {
			if (pg_num_rows($query) > 0) { // found something

				// Iterates through the results, creating one Placemark for each row.
				while ($row = pg_fetch_array($query))
				{
					// Creates a Placemark and append it to the Document.
					  $node = $dom->createElement('Placemark');
					  $placeNode = $docNode->appendChild($node);

					  // Creates an id attribute and assign it the value of id column.
					  $placeNode->setAttribute('id', 'placemark' . $row['osm_id']);

					  if ( $row['emergency'] == 'water_tank' ) {
                                            if ( $row['water_tank:volume'] == 'unlimited' ) {
                                              $pt_name = $row['name'];
  					      $pt_style = '#wu';
                                            }
                                            else {
                                              $pt_name = $row['water_tank:volume' + "m³" ];
					      $pt_style = '#wt';
                                            }
					  }
                                          elseif ( $row['emergency'] == 'fire_water_pond' || $row['emergency'] == 'suction_point' ) {
                                            $pt_name = $row['name'];
					    $pt_style = '#wp';
					  }
					  else {
					    $pt_name = $row['fire_hydrant:diameter'];
					    $pt_style = $row['fire_hydrant:type'] == 'pillar' ? '#hy_p' : '#hy_u';
					  }
					  
					  // Create name, and description elements and assigns them the values of the name and address columns from the results.
					  $nameNode = $dom->createElement('name',htmlentities($pt_name));
					  $placeNode->appendChild($nameNode);

					  $labelNode = $dom->createElement('label',htmlentities($pt_name));
					  $placeNode->appendChild($labelNode);

					  $styleNode = $dom->createElement('styleUrl', $pt_style );
					  $placeNode->appendChild($styleNode);
					  
					  // Creates a Point element.
					  $pointNode = $dom->createElement('Point');
					  $placeNode->appendChild($pointNode);

					  // Creates a coordinates element and gives it the value of the lng and lat columns from the results.
					  $coorStr = $row['lon'] . ','  . $row['lat'];
					  $coorNode = $dom->createElement('coordinates', $coorStr);
					  $pointNode->appendChild($coorNode);
				}

			} else { // nothing found
			}
		}
		pg_close($pgcon);
	} else {
		// no valid database connection
	}

	$kmlOutput = $dom->saveXML();
	header('Content-type: application/vnd.google-earth.kml+xml');
	header('Content-Disposition: attachment; filename="fire_hydrants.kml"');
	echo $kmlOutput;
?>
