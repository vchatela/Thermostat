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
//include('thermostat.class.php');

//Cette fonction va generer un nouveau element dans le menu
function test_plugin_menu(&$menuItems){
	global $_;
	$menuItems[] = array('sort'=>10,'content'=>'<a href="index.php?module=test"><i class="fa fa-codepen"></i> Thermostat</a>');
}

//Cette fonction ajoute une commande vocale
function test_plugin_vocal_command(&$response,$actionUrl){
	global $conf;
	//Création de la commande vocale "Yana, commande de test" avec une sensibilité de 0.90 et un appel 
	// vers l'url /action.php?action=thermostat_plugin_vocal_test après compréhension de la commande
	$response['commands'][] = array(
		'command'=>$conf->get('VOCAL_ENTITY_NAME').' commande vocale de test',
		'url'=>$actionUrl.'?action=test_plugin_vocal_test','confidence'=>('0.90'+$conf->get('VOCAL_SENSITIVITY'))
		);
}

//cette fonction comprends toutes les actions du plugin qui ne nécessitent pas de vue html
function test_plugin_action(){
	global $_,$conf;
	$temperatureGet = (integer) temperatureGet();
	//Action de réponse à la commande vocale "Yana, commande de test"
	switch($_['action']){
		case 'test_plugin_vocal_test':
			$response = array('responses'=>array(
										array('type'=>'talk','sentence'=>'La température est de .$temperatureGet. °C')
											)
								);
			$json = json_encode($response);
			echo ($json=='[]'?'{}':$json);
		break;
	}
}

function resultat_database(){
        // connexion a la base
        $base = mysql_connect('localhost', 'userdist', '123456');
        mysql_select_db('temperature',$base);
        
// lancement de la requete
        $sql = 'SELECT * FROM Temperature WHERE 1';
        $req = mysql_query($sql) or die ('Erreur SQL ! <br />' .$sql.'<br />'.mysql_error());
	while ($row = mysql_fetch_array($req)){
		//traiter les données
	}


//  on libère la place mémoire allouée pour l'interrogation de la base
        mysql_free_result($req);
        mysql_close();
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

	    <h2>Barre de progression</h2>
            <div class="progress progress-striped active">
            <div class="bar" style="width: 100%;"></div>
            </div>


	    <h2>Résumé</h2>
	    <table class="table table-striped table-bordered table-hover">
	    <thead>
	    <tr>
	    <th>Température Actuelle</th>
	    </tr>
	    </thead>
	    <tr><td><?php echo temperatureGet();?>°C</td></tr>
	    </table>
	 <h2> Affichage des résultats</h2>
	<div id="container" style="height: 400px; min-width: 310px"></div>
	<script src="http://ajax.googleapis.com/ajax/libs/jquery/1.8.2/jquery.min.js"></script>
	<script src="http://code.highcharts.com/highcharts.js"></script>
	<script src="http://code.highcharts.com/stock/highstock.js"></script>
	<script src="http://code.highcharts.com/stock/modules/exporting.js"></script>
	<script src="js/main.js"></script>
	<script src="js/gray.js"></script>
	<?php resultat_database();?>


	    <h2>Référence</h2>
	    <div class="pagination">
	    <ul>
	    <li><a href="#">Prev</a></li>
	    <li class="active"><a href="#">1</a></li>
	    <li><a href="#">Next</a></li>
	    </ul>
	    </div>

	</div>
<?php
	}
}

function temperatureGet(){
        if ($handle = opendir('/sys/bus/w1/devices')) {
                while (false !== ($entry = readdir($handle))) {
                        if(!strncmp($entry, "28-" , strlen("28-"))) {
                                $filename = "/sys/bus/w1/devices/".$entry."/w1_slave" ;
                                if (file_exists($filename)) {
                                        $lines = file($filename);
                                        $currenttemp = round ( substr($lines[1], strpos($lines[1], "t=")+2) / 1000 , 1) ;
                                        closedir($handle);
                                        return $currenttemp;
                                }
                        }
                }
                closedir($handle);
        }
        return "N/A";
}



Plugin::addCss("/css/style.css"); 
Plugin::addJs("/js/jquery.js"); 
Plugin::addJs("/js/highstock.js"); 
Plugin::addJs("/js/highcharts.js"); 
//Plugin::addJs("/js/highmaps.js"); 
//Plugin::addJs("/js/exporting.js"); 
//Plugin::addJs("/js/main.js"); 

Plugin::addHook("menubar_pre_home", "test_plugin_menu");  
Plugin::addHook("home", "test_plugin_page");  
Plugin::addHook("action_post_case", "test_plugin_action");    
Plugin::addHook("vocal_command", "test_plugin_vocal_command");
?>
