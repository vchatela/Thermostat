<?php
/*
@name Thermostat
@author Valentin Chatelard <valentindu64@hotmail.fr>
@link http://valentindu64.ddns.net
@licence Moi
@version 1.0.0
@description Thermostat
*/

//Si vous utiliser la base de donnees a ajouter
Plugin::addCss("/yana-server/plugins/Thermostat/css/style.css"); 

include 'connection.php';
//Cette fonction va generer un nouveau element dans le menu
function test_plugin_menu(&$menuItems){
	global $_;
	$menuItems[] = array('sort'=>10,'content'=>'<a href="index.php?module=test"><i class="fa fa-codepen"></i> Thermostat</a>');
}

//Cette fonction ajoute une commande vocale
function test_plugin_vocal_command(&$response,$actionUrl){
	global $conf;
	//Création de la commande vocale "Yana, temperature de la chambre" avec une sensibilité de 0.90 et un appel 
	// vers l'url /action.php?action=thermostat_plugin_vocal_test après compréhension de la commande
	$response['commands'][] = array(
		'command'=>$conf->get('VOCAL_ENTITY_NAME').', temperature de la chambre',
		'url'=>$actionUrl.'?action=test_plugin_vocal_test','confidence'=>('0.90'+$conf->get('VOCAL_SENSITIVITY'))
		);
}

//cette fonction comprends toutes les actions du plugin qui ne nécessitent pas de vue html
function test_plugin_action(){
	global $_,$conf;
	$temperatureGet = temperatureGet();
	//Action de réponse à la commande vocale "Yana, temperature de la chambre"
	switch($_['action']){
		case 'test_plugin_vocal_test':
			$response = array('responses'=>array(
										array('type'=>'talk','sentence'=>'La température est de ' .$temperatureGet. '°C')
											)
								);
			$json = json_encode($response);
			echo ($json=='[]'?'{}':$json);
		break;
	}
}

function get_max(){
	$req= connection_database();
        mysql_data_seek($req, mysql_num_rows($req) - 337);
	$max = -150;
	while($row = mysql_fetch_array($req)){
		if ($row[3] > $max){
			$max = $row[3];
		}
	}

mysql_free_result($req);
$max = substr($max, 0, -1);
return $max;
}
function get_min(){
        $req = connection_database();
        mysql_data_seek($req, mysql_num_rows($req) - 337);
        $min = +150;
        while($row = mysql_fetch_array($req)){
                if ($row[3] < $min){
                        $min = $row[3];
                }
        }
mysql_free_result($req);
$min = substr($min, 0, -1);

return $min;
}

function get_average(){
        $req = connection_database();
	mysql_data_seek($req, mysql_num_rows($req) - 337);
	$average=0;
	$compteur = 0;
        while ($row = mysql_fetch_array($req)){
		$average += $row[3];
		$compteur +=1;
	}
$average = substr($average / $compteur , 0, 5);
mysql_free_result($req);
$average = substr($average, 0, -1);

return $average;
}

function resultat_database(){
        $req = connection_database();
	mysql_data_seek($req, mysql_num_rows($req) - 337);
	$data='';
	$compteur = 0;
	while ($row = mysql_fetch_array($req) and $compteur <337){
		//traiter les données
		// on récupère l'heure et la valeur de la temperature
		//['2016-01-20 10:27',13.312],['2016-01-20 10:58',13.937]
		// [ new Date(year, month, day, hours, minutes) , 'temp']
		$data .= ('[ new Date(' .substr($row[1],0,4). ',' .substr($row[1],5,2). ',' .substr($row[1],8,2). ',' .substr($row[2],0,-6). ',' .substr($row[2],3,2). '),'.$row[3].',' .$row[4]. '],');
		$compteur += 1;
	}
$data = substr($data, 0, -1);
mysql_free_result($req);

return $data;
}


//Cette fonction va generer une page quand on clique sur Thermostat dans menu
function test_plugin_page($_){
	if(isset($_['module']) && $_['module']=='test'){

	?>
	<div class="span9">


	<h1>Utilisation comme Thermostat</h1>
	<p>A l'aide du composant DS18B20 pour récupérer la température, et de prise Chacon radio commandé, il s'agira de gérer la température dans la pièce.</p>


		<h2></h2>

	    <ul class="nav nav-tabs">
	      <li class="active"><a href="#">Etat du chauffage</a></li>
	      <li><a href="#">Programmation</a></li>
	    </ul>
<!--
	    <h2>Barre de progression</h2>
            <div class="progress progress-striped active">
            <div class="bar" style="width: 100%;"></div>
            </div>
-->

	    <h2>Résumé</h2>
	    <table class="table table-striped table-bordered table-hover">
	    <thead>
	    <tr>
	    <th>Température Actuelle</th>
	    <th>Température moyenne (7 jours)</th>
	    <th>Température la plus haute (7jours)</th>
            <th>Température la plus basse (7jours)</th>
	    </tr>
	    </thead>
	    <tr>
	    <td><?php echo temperatureGet();?>°C</td>
	    <td><?php echo get_average();?>°C</td>
	    <td><?php echo get_max();?>°C</td>
	    <td><?php echo get_min();?>°C</td></tr>
	    </table>
	 <h2> Affichage des résultats des 7 derniers jours</h2>
  <script type="text/javascript" src="https://www.google.com/jsapi"></script>
<div id="chart_div" style="width:1000; height:500"></div>
	    <h2>Référence</h2>
	    <div class="pagination">
	    <ul>
	    <li><a href="#">Prev</a></li>
	    <li class="active"><a href="#">1</a></li>
	    <li><a href="#">Next</a></li>
	    </ul>
	    </div>

	</div>

 <script type="text/javascript">
google.load('visualization', '1', {packages: ['corechart', 'line']});
google.setOnLoadCallback(drawChart);

function drawChart() {

      var data = new google.visualization.DataTable();
      data.addColumn('datetime', 'Date - Heure');
      data.addColumn('number', 'Temperature');
	  data.addColumn('number', 'Etat du Chauffage');

      data.addRows([
		<?php echo resultat_database();?>
      ]);

      var options = {
		width:1000,
        height:500,
		series:{
			 // Gives each series an axis name that matches the Y-axis below.
          0: {axis: 'Date1'},
          1: {axis: 'Date2'}
		},
		axes:{
			// Adds labels to each axis; they don't have to match the axis names.
          y: {
            Data1: {label: 'Temperature ( °C)'},
            Data2: {label: 'Etat du chauffage'}
          }
		}
      };

      var chart = new google.visualization.LineChart(document.getElementById('chart_div'));

      chart.draw(data, options);
    }

</script>
<?php mysql_close();
	}
}

function temperatureGet(){
        if ($handle = opendir('/sys/bus/w1/devices')) {
                while (false !== ($entry = readdir($handle))) {
                        if(!strncmp($entry, "28-" , strlen("28-"))) {
                                $filename = "/sys/bus/w1/devices/".$entry."/w1_slave" ;
                                if (file_exists($filename)) {
                                        $lines = file($filename);
                                        $currenttemp = round ( substr($lines[1], strpos($lines[1], "t=")+2) / 1000 , 2) ;
                                        closedir($handle);
                                        return $currenttemp;
                                }
                        }
                }
                closedir($handle);
        }
        return "N/A";
}
 

Plugin::addHook("menubar_pre_home", "test_plugin_menu");  
Plugin::addHook("home", "test_plugin_page");  
Plugin::addHook("action_post_case", "test_plugin_action");    
Plugin::addHook("vocal_command", "test_plugin_vocal_command");
?>
