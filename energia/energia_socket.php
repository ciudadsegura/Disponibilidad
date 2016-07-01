<?php
set_time_limit(0);
include("conexion.php");
include("func.php");
require "thread.php";

function functionWarnings($errno, $errstr, $errfile, $errline){
	//echo getmypid().">>>>Hay un WARNING.<br />\n";
	//echo "El warning es: ". $errstr ."<br />\n";
	//echo "El fichero donde se ha producido el warning es: ". $errfile ."<br />\n";
	//echo "La línea donde se ha producido el warning es: ". $errline ."<br />\n"; 
	//goto prueba;
	//die();
	sleep(1);
	throw new ErrorException($errstr,0,$errno,$errfile, $errline);
}

function energia7k($ip_man,$ip_asr,$c2){

	while(true){

		set_error_handler("functionWarnings", E_WARNING);

		try{

			if(!($connection = ssh2_connect($ip_asr, 22))){
				echo "No se pudo establecer conexion\n";
			}
			else{
				if(!ssh2_auth_password($connection, 'casmto', 'c4smt0')){
				echo "No se pudo autenticar\n";
				}
			}
		}
		catch(Exception $error){
			continue;
		}
		restore_error_handler();


		$datos=ssh2_shell($connection, 'VT220');

		$cmd= "tel ".$ip_man."\nreduno\nb1c3nt3n4r10\nsh clock\nterm len 0\nsh ver\nsh arp\nsh int summ\nexit" . PHP_EOL . "exit" . PHP_EOL ;
		
		fwrite( $datos, $cmd);
		stream_set_blocking($datos, true);

		$theNewData="";
		$done=false;

		if(file_exists("consulta_energia.txt"))
		{
			$consulta_energia_FL=fopen("consulta_energia.txt","w");
			fputs($consulta_energia_FL,"");
			fclose($consulta_energia_FL);
		}
		if(file_exists("dispositivos.txt"))
		{
			$dispositivos_FL=fopen("dispositivos.txt","w");
			fputs($dispositivos_FL,"");
			fclose($dispositivos_FL);
		}
		if(file_exists("flujo.txt"))
		{
			$flujo_FL=fopen("flujo.txt","w");
			fputs($flujo_FL,"");
			fclose($flujo_FL);
		}
		if(file_exists("plantilla.txt"))
		{
			$plantilla_FL=fopen("plantilla.txt","w");
			fputs($plantilla_FL,"");
			fclose($plantilla_FL);
		}
		if(file_exists("serie.txt"))
		{
			$serie_FL=fopen("serie.txt","w");
			fputs($serie_FL,"");
			fclose($serie_FL);
		}
		if(file_exists("version.txt"))
		{
			$version_FL=fopen("version.txt","w");
			fputs($version_FL,"");
			fclose($version_FL);
		}

		$c=0;

		$arrMonth= array("Jan"=>"01","Feb"=>"02","Mar"=>"03","Apr"=>"04","May"=>"05","Jun"=>"06"
								,"Jul"=>"07","Aug"=>"08","Sep"=>"09","Oct"=>"10","Nov"=>"11","Dec"=>"12");
		
		$hostname="";
		$fechaconsulta="";
		$fechareinicio=NULL;
		$dispositivos = NULL;
		$flujo=NULL;
		$tfc = 0;
		$ini=0;
		$fin=0;
		$inid=0;
		$find=0;
		$cnsn=0;
		$ipl = 0;
		$device="S/N";
		$pid="S/N";
		$serie="S/N";

		while (!feof($datos) && !$done){
			set_error_handler("functionWarnings", E_WARNING);
			try{
		    	$stream = stream_get_line($datos, 1024, "\n");
		    	$stream=trim($stream);

		    	if(preg_match('/exit/',$stream)){

					fclose($datos); 
					break;
				}
		    }
		    catch(Exception $error2){
		    	continue 2;
		    }
		    restore_error_handler();

		    if(preg_match('/timed out/',$stream) || preg_match('/Authentication failed/',$stream)){

					echo "time out >>>> ".$ip_man."	".$ip_asr."	".$c2."	fecha consulta ".date("Y-m-d H:i:s")."\n";
					fclose($datos); 
					continue 2;
			}
			if(preg_match('/timeout/',$stream)){

					echo "reintentando >>>> ".$ip_man."	".$ip_asr."	".$c2."	fecha consulta ".date("Y-m-d H:i:s")."\n";
					fclose($datos); 
					continue 2;
			}

		    if(preg_match('/Async/',$stream)){
		    	$ini=$c;
		    	//echo $ini."\n";
		    }

		    if(preg_match('/NOTE:/',$stream)){
		    	$fin=$c;
		    	//echo $fin."\n";
		    }
			if (($ini>0)&&($c>=$ini) && ($fin==0)){
				$linea=preg_replace('/[\s]+/'," ",(string)$stream);
				$flujoll=str_replace("* ","*",$linea);
				$flujol=explode(' ',$flujoll);
			    $flujo[]=array('ip_man'=>$ip_man,'fecha_consulta'=>$fechaconsulta,'interfaz'=>$flujol[0],'ihq'=>$flujol[1],'iqd'=>$flujol[2],'ohq'=>$flujol[3],'oqd'=>$flujol[4],'rxbs'=>$flujol[5],'rxps'=>$flujol[6],'txbs'=>$flujol[7],'txps'=>$flujol[8],'trtl'=>$flujol[9]);
			    //fputs($fp_h,trim($flujoll)."\n");
			
			}else{
			    $linea=preg_replace('/[\s]+/'," ",(string)$stream);
			    //fputs($fp_h,trim($linea)."\n");


			   if (preg_match('/#sh clock/',$linea)){
			    	$hostname=explode('#',$linea);
			    	$hostname=$hostname[0];
			    	//echo $hostname."\n";
				}

		 		/*if ((preg_match('/CENTRAL/',$linea)) && ($tfc==0)){
					$tiempo=explode(' ',$linea);
					$tp=explode(':',$tiempo[0]);
					$tpc = str_replace(".", "",$tp[0]).":".$tp[1].":".$tp[2];
					$fechaconsulta=date("Y")."-".$arrMonth[$tiempo[3]]."-".$tiempo[4]." ".$tpc;
					$fechaconsulta = str_replace("*","",$fechaconsulta);
					//echo $fechaconsulta."\n";
					$tfc++;
				}*/

				if (((preg_match('/CENTRAL/',$linea)) || (preg_match('/UTC/',$linea))) && ($tfc==0)){
					$tiempo=explode(' ',$linea);
					$tp=explode(':',$tiempo[0]);
					$tpc = str_replace(".", "",$tp[0]).":".$tp[1].":".$tp[2];
					$fechaconsulta=date("Y")."-".$arrMonth[$tiempo[3]]."-".$tiempo[4];
					$date1 =  date_create($fechaconsulta);
					$fechaconsulta = $date1->format('Y-m-d')." ".$tpc;
					$fechaconsulta = str_replace("*","",$fechaconsulta);
					//echo $fechaconsulta."\n";

					if((preg_match('/UTC/',$linea))){
						$date1=date_create($fechaconsulta);
						$date1->modify('-5 hours');
						$fechaconsulta = $date1->format('Y-m-d H:i:s.u');
						//echo $fechaconsulta."\n";
					}
					$tfc++;
				}

				if(!isset($inforouter)){
					if (preg_match('/RELEASE SOFTWARE/',$linea)){
						if (!preg_match('/Cisco IOS/',$linea)){
							$linea='Cisco IOS Software, '.$linea;	
						}
						$inforouter=$linea;
						$versionl=explode(', ',$linea);
						$tipo_router=$versionl[0];
						$software=$versionl[1];
						$version=$versionl[2];
						$release=$versionl[3];
						//echo $tipo_router." ".$software." ".$version." ".$release."\n";
					}
				}

				if (preg_match('/ROM:/',$linea)){
					$version_l=$linea;
					//echo $version_l."\n";
				}
				if (preg_match('/uptime/',$linea)){
					$hostname_uptime=$linea;
					$uptime=preg_replace('/[a-zA-Z0-9\-]+\suptime\sis\s/',"",$linea);
					$up_time = preg_replace('/, /'," ",$uptime);
					//echo $up_time."\n";
				}
				if (preg_match('/System returned/',$linea)){
					$mensaje=$linea;
					//echo $mensaje."\n";
				}
				if (preg_match('/System restarted/',$linea)){
					$info_reinicio=$linea;
					$tiempo=explode(' ',$linea);
					$fechareinicio=$tiempo[8]."-".$arrMonth[$tiempo[6]]."-".$tiempo[7]." ".$tiempo[3];
					$fechareinicio = str_replace("*","",$fechareinicio);


					if($tiempo[4]=="UTC"){
						$date1=date_create($fechareinicio);
						$date1->modify('-5 hours');
						$fechareinicio = $date1->format('Y-m-d H:i:s.u');
					}

					//echo $fechareinicio."\n";
				}
				if (preg_match('/System image/',$linea)){
					$imagen_sistema=preg_replace('/"/',"",$linea);
					//echo $imagen_sistema."\n";
				}
				if (preg_match('/Device#/',$linea)){
					$cnsn=$c+2;
				}
				if (($c==$cnsn) && ($cnsn>0)){
					$series=explode(' ',$linea);
					$device = $series[0];
					$pid = $series[1];
					$serie = $series[2];
					//echo $device." ".$pid." ".$serie."\n";
				}
				if(preg_match('/Protocol Address/',$linea)){
					$inid=$c;
				}
				if(preg_match('/#sh int/',$linea)){
					$find=$c;
				}

				if (($inid>0)&&($c>$inid) && (!(preg_match('/#sh int/',$linea)))&&$find==0){
					$dispositivo=explode(' ',$linea);

					if(($dispositivo[2]=="-") && ($ipl==0)){
						$ip_lan = $dispositivo[1];
						$ipl++;
					}

					if(!($dispositivo[3]=="Incomplete")){

						$dispositivos[]=array('protocolo'=>$dispositivo[0],'ip_adress'=>$dispositivo[1],'tiempo'=>$dispositivo[2],'hardware'=>$dispositivo[3],'tipo'=>$dispositivo[4],'interfaz'=>$dispositivo[5],'ip_man'=>$ip_man,'fecha_consulta'=>$fechaconsulta);
					}
				}
			}
			$done = ($stream === '0');
			$c++;
		}

		if($fechareinicio == NULL){
			$upt=preg_replace('/[a-zA-Z0-9\-]+\suptime\sis\s/',"",$hostname_uptime);

			$ups=explode(", ",$upt);

			$fechareinicio = $fechaconsulta;

			foreach($ups as $up){
				//echo $up."<br>";
				$date2=date_create($fechareinicio);
				//echo $up."<br>";
				
				$date2->modify('-'.$up);
				
				$fechareinicio = $date2->format('Y-m-d H:i:s');
			}

		}

		$rango = (strtotime($fechaconsulta) - strtotime($fechareinicio));

		$consulta_energia_FL=fopen("consulta_energia.txt","a");
		$dispositivos_FL=fopen("dispositivos.txt","a");
		$flujo_FL=fopen("flujo.txt","a");
		$plantilla_FL=fopen("plantilla.txt","a");
		$serie_FL=fopen("serie.txt","a");
		$version_FL=fopen("version.txt","a");

		fputs($consulta_energia_FL,trim($ip_man.'	'.$c2.'	'.$hostname.'	'.$fechaconsulta.'	'.$fechareinicio.'	'.$inforouter.'	'.$up_time.'	'.$rango.'	'.$hostname_uptime.'	'.$mensaje.'	'.$info_reinicio.'	'.$imagen_sistema));

		for($i=0;$i<count($dispositivos);$i++){

				if($i>0)fputs($dispositivos_FL,"\n");
				fputs($dispositivos_FL,trim($dispositivos[$i]['protocolo']."	".$dispositivos[$i]['ip_adress']."	".$dispositivos[$i]['tiempo']."	".$dispositivos[$i]['hardware']."	".$dispositivos[$i]['tipo']."	".$dispositivos[$i]['interfaz']."	".$dispositivos[$i]['ip_man']."	".$ip_lan."	".$dispositivos[$i]['fecha_consulta']));
		}
		
		for($i=0;$i<count($flujo);$i++){

			if($i>0)fputs($flujo_FL,"\n");
			fputs($flujo_FL,trim($flujo[$i]['ip_man']."	".$flujo[$i]['fecha_consulta']."	".$flujo[$i]['interfaz']."	".$flujo[$i]['ihq']."	".$flujo[$i]['iqd']."	".$flujo[$i]['ohq']."	".$flujo[$i]['oqd']."	".$flujo[$i]['rxbs']."	".$flujo[$i]['rxps']."	".$flujo[$i]['txbs']."	".$flujo[$i]['txps']));
		}
		fputs($plantilla_FL,trim($fechaconsulta.'	'.$ip_man.'	'.$version_l));
		fputs($serie_FL,trim($fechaconsulta.'	'.$ip_man.'	'.$device.'	'.$pid.'	'.$serie));
		fputs($version_FL,trim($fechaconsulta.'	'.$ip_man.'	'.$tipo_router.'	'.$software.'	'.$version.'	'.$release));

		fclose($consulta_energia_FL);
		fclose($dispositivos_FL);
		fclose($flujo_FL);
		fclose($plantilla_FL);
		fclose($serie_FL);
		fclose($version_FL);

		//print_r($flujo);
		echo "correcto >>>> 	".$ip_man."	".$ip_asr."	".$c2."	fecha consulta ".$fechaconsulta."\n";
		break;
	}
}

function energia8k_socket($ip_man,$ip_asr,$c2){

	while(true){

		$service_port = getservbyname('telnet', 'tcp');

		$address = gethostbyname($ip_man);

		$socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);

		set_error_handler("functionWarnings", E_WARNING);

		try {
			$result = socket_connect($socket, $address, $service_port);

			$in = "casmto\r\nc4smt0\r\nsh clock\r\nsh ver\r\n  sh arp\r\nsh int summ\r\n exit\r\n";
			
			socket_write($socket, $in, strlen($in));
			
		} 
		catch(Exception $error){
			continue;
		}

		restore_error_handler();


		if(file_exists("consulta_energia.txt"))
		{
			$consulta_energia_FL=fopen("consulta_energia.txt","w");
			fputs($consulta_energia_FL,"");
			fclose($consulta_energia_FL);
		}
		if(file_exists("dispositivos.txt"))
		{
			$dispositivos_FL=fopen("dispositivos.txt","w");
			fputs($dispositivos_FL,"");
			fclose($dispositivos_FL);
		}
		if(file_exists("flujo.txt"))
		{
			$flujo_FL=fopen("flujo.txt","w");
			fputs($flujo_FL,"");
			fclose($flujo_FL);
		}
		if(file_exists("plantilla.txt"))
		{
			$plantilla_FL=fopen("plantilla.txt","w");
			fputs($plantilla_FL,"");
			fclose($plantilla_FL);
		}
		if(file_exists("serie.txt"))
		{
			$serie_FL=fopen("serie.txt","w");
			fputs($serie_FL,"");
			fclose($serie_FL);
		}
		if(file_exists("version.txt"))
		{
			$version_FL=fopen("version.txt","w");
			fputs($version_FL,"");
			fclose($version_FL);
		}

		$c=0;

		$arrMonth= array("Jan"=>"01","Feb"=>"02","Mar"=>"03","Apr"=>"04","May"=>"05","Jun"=>"06"
				,"Jul"=>"07","Aug"=>"08","Sep"=>"09","Oct"=>"10","Nov"=>"11","Dec"=>"12");
		
		$hostname="";
		$fechaconsulta="";
		$fechareinicio= NULL;
		$dispositivos = NULL;
		$info_reinicio="";
		$flujo=NULL;
		$tfc = 0;
		$ini=0;
		$fin=0;
		$inid=0;
		$find=0;
		$cnsn=0;
		$ipl = 0;
		$cnfl=0;
		$device="S/N";
		$pid="S/N";
		$serie="S/N";

		$datos="";

		while ($out= socket_read($socket, 2048)){
			$datos .= $out;

		}

		socket_close($socket);

		$datos=preg_replace('/ --More--         /',"",$datos);


		/*$ar=fopen("energy_file8k.txt","w");
		fputs($ar,$datos);
		fclose($ar);*/
		
		//print_r($datos);

		if(preg_match('/exit/',$datos))
		{

			$linea=explode("\n",$datos);

			for($i=0;$i<count($linea);$i++){

				if (preg_match('/#sh clock/',$linea[$i])){
			    	$hostname=explode('#',$linea[$i]);
			    	$hostname=$hostname[0];
			    	//echo $hostname."\n";
				}
				if (((preg_match('/CENTRAL/',$linea[$i])) || (preg_match('/UTC/',$linea[$i]))) && ($tfc==0)){
					$tiempo=explode(' ',$linea[$i]);
					$tp=explode(':',$tiempo[0]);
					$tpc = str_replace(".", "",$tp[0]).":".$tp[1].":".$tp[2];

					$fechaconsulta=date("Y")."-".$arrMonth[$tiempo[3]]."-".$tiempo[4];
					$date1 =  date_create($fechaconsulta);
					$fechaconsulta = $date1->format('Y-m-d')." ".$tpc;
					$fechaconsulta = str_replace("*","",$fechaconsulta);
					//echo $fechaconsulta."\n";

					if((preg_match('/UTC/',$linea[$i]))){
						$date1=date_create($fechaconsulta);
						$date1->modify('-5 hours');
						$fechaconsulta = $date1->format('Y-m-d H:i:s.u');
						//echo $fechaconsulta."\n";
					}
					$tfc++;
				}

				if(!isset($inforouter)){
					if (preg_match('/RELEASE SOFTWARE/',$linea[$i])){
						if (!preg_match('/Cisco IOS/',$linea[$i])){
							$linea='Cisco IOS Software, '.$linea[$i];	
						}
						$inforouter=$linea[$i];
						$versionl=explode(', ',$linea[$i]);
						$tipo_router=$versionl[0];
						$software=$versionl[1];
						$version=$versionl[2];
						$release=$versionl[3];
						//echo $tipo_router." ".$software." ".$version." ".$release."\n";
					}
				}

				if (preg_match('/ROM:/',$linea[$i])){
					$version_l=$linea[$i];
					//echo $version_l."\n";
				}

				if (preg_match('/uptime/',$linea[$i])){
					$hostname_uptime=$linea[$i];
					$uptime=preg_replace('/[a-zA-Z0-9\-]+\suptime\sis\s/',"",$linea[$i]);
					$up_time = preg_replace('/, /'," ",$uptime);
					//echo $up_time."\n";
				}

				if (preg_match('/System returned/',$linea[$i])){
					$mensaje=$linea[$i];
					//echo $mensaje."\n";
				}

				if (preg_match('/System restarted/',$linea[$i])){
					$info_reinicio=$linea[$i];
					$tiempo=explode(' ',$linea[$i]);
					$fechareinicio=trim($tiempo[8])."-".$arrMonth[$tiempo[6]]."-".$tiempo[7]." ".$tiempo[3];
					$fechareinicio = str_replace("*","",$fechareinicio);

					if($tiempo[4]=="UTC"){
						$date1=date_create($fechareinicio);
						$date1->modify('-5 hours');
						$fechareinicio = $date1->format('Y-m-d H:i:s.u');
					}

					//echo $fechareinicio."\n";
				}

				if (preg_match('/System image/',$linea[$i])){
					$imagen_sistema=preg_replace('/"/',"",$linea[$i]);
					//echo $imagen_sistema."\n";
				}

				if (preg_match('/Device#/',$linea[$i])){
					$cnsn=$c+2;
				}

				if (($c==$cnsn) && ($cnsn>0)){
					$line=preg_replace('/[\s]+/'," ",(string)$linea[$i]);
					$series=explode(' ',$line);
					$device = $series[0];
					$pid = $series[1];
					$serie = $series[2];
					//echo $device." ".$pid." ".$serie."\n";
				}

				if(preg_match('/Protocol/',$linea[$i])){
					$inid=$c;
				}
				if(preg_match('/#sh int/',$linea[$i])){
					$find=$c;
				}

				if (($inid>0)&&($c>$inid) && (!(preg_match('/#sh int/',$linea[$i])))&&$find==0){
					$line=preg_replace('/[\s]+/'," ",(string)$linea[$i]);
					$dispositivo=explode(' ',$line);
					if(($dispositivo[2]=="-") && ($ipl==0)){
						$ip_lan = $dispositivo[1];
						$ipl++;
					}

					if(!($dispositivo[3]=="Incomplete")){

						$dispositivos[]=array('protocolo'=>$dispositivo[0],'ip_adress'=>$dispositivo[1],'tiempo'=>$dispositivo[2],'hardware'=>$dispositivo[3],'tipo'=>$dispositivo[4],'interfaz'=>$dispositivo[5],'ip_man'=>$ip_man,'fecha_consulta'=>$fechaconsulta);
					}

				}

				if(preg_match('/Async/',$linea[$i])){
			    	$ini=$c;
			    	//echo $ini."\n";
			    }

				if ( ($ini>0) && ($c>=$ini) && (!(preg_match('/#exit/',$linea[$i]))) && (!(trim($linea[$i])=="")) && (!(preg_match('/Interface/',$linea[$i]))) && (!(preg_match('/-/',$linea[$i]))) ){
					$line=preg_replace('/[\s]+/'," ",(string)$linea[$i]);
					$flujoll=str_replace("* ","*",$line);
					$flujoll=str_replace(" A","A",$flujoll);
					$flujoll=str_replace(" F","F",$flujoll);
					$flujol=explode(' ',$flujoll);
			   		$flujo[]=array('ip_man'=>$ip_man,'fecha_consulta'=>$fechaconsulta,'interfaz'=>$flujol[0],'ihq'=>$flujol[1],'iqd'=>$flujol[2],'ohq'=>$flujol[3],'oqd'=>$flujol[4],'rxbs'=>$flujol[5],'rxps'=>$flujol[6],'txbs'=>$flujol[7],'txps'=>$flujol[8],'trtl'=>$flujol[9]);
				}

				$c++;
			}

			if($fechareinicio == NULL){
				$upt=preg_replace('/[a-zA-Z0-9\-]+\suptime\sis\s/',"",$hostname_uptime);

				$ups=explode(", ",$upt);

				$fechareinicio = $fechaconsulta;

				foreach($ups as $up){
					//echo $up."<br>";
					$date2=date_create($fechareinicio);
					//echo $up."<br>";
					
					$date2->modify('-'.$up);
					
					$fechareinicio = $date2->format('Y-m-d H:i:s');
				}
			}

			$rango = (strtotime($fechaconsulta) - strtotime($fechareinicio));

			$consulta_energia_FL=fopen("consulta_energia.txt","a");
			$dispositivos_FL=fopen("dispositivos.txt","a");
			$flujo_FL=fopen("flujo.txt","a");
			$plantilla_FL=fopen("plantilla.txt","a");
			$serie_FL=fopen("serie.txt","a");
			$version_FL=fopen("version.txt","a");

			fputs($consulta_energia_FL,trim($ip_man.'	'.$c2.'	'.$hostname.'	'.$fechaconsulta.'	'.$fechareinicio.'	'.$inforouter.'	'.$up_time.'	'.$rango.'	'.$hostname_uptime.'	'.$mensaje.'	'.$info_reinicio.'	'.$imagen_sistema));

			for($i=0;$i<count($dispositivos);$i++){

					if($i>0)fputs($dispositivos_FL,"\n");
					fputs($dispositivos_FL,trim($dispositivos[$i]['protocolo']."	".$dispositivos[$i]['ip_adress']."	".$dispositivos[$i]['tiempo']."	".$dispositivos[$i]['hardware']."	".$dispositivos[$i]['tipo']."	".$dispositivos[$i]['interfaz']."	".$dispositivos[$i]['ip_man']."	".$ip_lan."	".$dispositivos[$i]['fecha_consulta']));
			}
			
			for($i=0;$i<count($flujo);$i++){

				if($i>0)fputs($flujo_FL,"\n");
				fputs($flujo_FL,trim($flujo[$i]['ip_man']."	".$flujo[$i]['fecha_consulta']."	".trim($flujo[$i]['interfaz'])."	".$flujo[$i]['ihq']."	".$flujo[$i]['iqd']."	".$flujo[$i]['ohq']."	".$flujo[$i]['oqd']."	".$flujo[$i]['rxbs']."	".$flujo[$i]['rxps']."	".$flujo[$i]['txbs']."	".$flujo[$i]['txps']));
			}
			fputs($plantilla_FL,trim($fechaconsulta.'	'.$ip_man.'	'.$version_l));
			fputs($serie_FL,trim($fechaconsulta.'	'.$ip_man.'	'.$device.'	'.$pid.'	'.$serie));
			fputs($version_FL,trim($fechaconsulta.'	'.$ip_man.'	'.$tipo_router.'	'.$software.'	'.$version.'	'.$release));

			fclose($consulta_energia_FL);
			fclose($dispositivos_FL);
			fclose($flujo_FL);
			fclose($plantilla_FL);
			fclose($serie_FL);
			fclose($version_FL);
			//print_r($flujo);
			echo "correcto >>>> 	".$ip_man."	".$ip_asr."	".$c2."	fecha consulta ".$fechaconsulta."\n";
			break;

		}
		else{
			$h_inicio=date_create(date("Y-m-d H:i:s"));
			echo "reintentando    >>>> 	".$ip_man."	".$ip_asr."	".$c2."	fecha consulta ".$h_inicio->format("Y-m-d H:i:s")."\n";
			continue;
		}
	}
}

function energia8k($ip_man,$ip_asr,$c2){
	while(true){
		set_error_handler("functionWarnings", E_WARNING);
		try{

			if(!($connection = ssh2_connect($ip_asr, 22))){
				echo "No se pudo establecer conexion\n";
			}
			else{
				if(!ssh2_auth_password($connection, 'casmto', 'c4smt0')){
					echo "No se pudo autenticar\n";
				}
			}
		}
		catch(Exception $error){
			continue;
		}
		restore_error_handler();

		$datos=ssh2_shell($connection, 'VT220');

		$cmd= "tel ".$ip_man."\ncasmto\nc4smt0\nsh clock\nterm len 0\nsh ver\nsh arp\nsh int summ\nexit" . PHP_EOL . "exit" . PHP_EOL ;

		fwrite( $datos, $cmd);
		stream_set_blocking($datos, true);

		$theNewData="";
		$done=false;

		if(file_exists("consulta_energia.txt"))
		{
			$consulta_energia_FL=fopen("consulta_energia.txt","w");
			fputs($consulta_energia_FL,"");
			fclose($consulta_energia_FL);
		}
		if(file_exists("dispositivos.txt"))
		{
			$dispositivos_FL=fopen("dispositivos.txt","w");
			fputs($dispositivos_FL,"");
			fclose($dispositivos_FL);
		}
		if(file_exists("flujo.txt"))
		{
			$flujo_FL=fopen("flujo.txt","w");
			fputs($flujo_FL,"");
			fclose($flujo_FL);
		}
		if(file_exists("plantilla.txt"))
		{
			$plantilla_FL=fopen("plantilla.txt","w");
			fputs($plantilla_FL,"");
			fclose($plantilla_FL);
		}
		if(file_exists("serie.txt"))
		{
			$serie_FL=fopen("serie.txt","w");
			fputs($serie_FL,"");
			fclose($serie_FL);
		}
		if(file_exists("version.txt"))
		{
			$version_FL=fopen("version.txt","w");
			fputs($version_FL,"");
			fclose($version_FL);
		}

		$c=0;

		$arrMonth= array("Jan"=>"01","Feb"=>"02","Mar"=>"03","Apr"=>"04","May"=>"05","Jun"=>"06"
				,"Jul"=>"07","Aug"=>"08","Sep"=>"09","Oct"=>"10","Nov"=>"11","Dec"=>"12");
		
		$hostname="";
		$fechaconsulta="";
		$fechareinicio= NULL;
		$dispositivos = NULL;
		$info_reinicio="";
		$flujo=NULL;
		$tfc = 0;
		$ini=0;
		$fin=0;
		$inid=0;
		$find=0;
		$cnsn=0;
		$ipl = 0;
		$device="S/N";
		$pid="S/N";
		$serie="S/N";

		while (!feof($datos) && !$done){
			set_error_handler("functionWarnings", E_WARNING);
			try{
				$stream = stream_get_line($datos, 1024, "\n");
				$stream=trim($stream);

				if(preg_match('/exit/',$stream)){

					fclose($datos); 
					break;
				}
			}
			catch(Exception $error2){
				continue 2;
			}
			restore_error_handler();

			if(preg_match('/timed out/',$stream) || preg_match('/Authentication failed/',$stream)){
					//$linea=preg_replace('/[\s]+/'," ",(string)$stream);
					echo "time out >>>> ".$ip_man."	".$ip_asr."	".$c2."	fecha consulta ".date("Y-m-d H:i:s")."\n";
					fclose($datos); 

					continue 2;
			}
			if(preg_match('/timeout/',$stream)){
					//$linea=preg_replace('/[\s]+/'," ",(string)$stream);
					echo "reintentando >>>> ".$ip_man."	".$ip_asr."	".$c2."	fecha consulta ".date("Y-m-d H:i:s")."\n";
					fclose($datos); 

					continue 2;
			}

			if(preg_match('/Async/',$stream)){
		    	$ini=$c;
		    	//echo $ini."\n";
		    }

			if (($ini>0)&&($c>=$ini) && (!(preg_match('/#exit/',$linea)))){
				$linea=preg_replace('/[\s]+/',"	",(string)$stream);
				$flujoll=str_replace("*	","*",$linea);
				$flujol=explode('	',$flujoll);
				$flujo[]=array('ip_man'=>$ip_man,'fecha_consulta'=>$fechaconsulta,'interfaz'=>$flujol[0],'ihq'=>$flujol[1],'iqd'=>$flujol[2],'ohq'=>$flujol[3],'oqd'=>$flujol[4],'rxbs'=>$flujol[5],'rxps'=>$flujol[6],'txbs'=>$flujol[7],'txps'=>$flujol[8],'trtl'=>$flujol[9]);
			}
			else{
				$linea=preg_replace('/[\s]+/'," ",(string)$stream);

				if (preg_match('/#sh clock/',$linea)){
			    	$hostname=explode('#',$linea);
			    	$hostname=$hostname[0];
			    	//echo $hostname."\n";
				}

				if (((preg_match('/CENTRAL/',$linea)) || (preg_match('/UTC/',$linea))) && ($tfc==0)){
					$tiempo=explode(' ',$linea);
					$tp=explode(':',$tiempo[0]);
					$tpc = str_replace(".", "",$tp[0]).":".$tp[1].":".$tp[2];
					$fechaconsulta=date("Y")."-".$arrMonth[$tiempo[3]]."-".$tiempo[4];
					$date1 =  date_create($fechaconsulta);
					$fechaconsulta = $date1->format('Y-m-d')." ".$tpc;
					$fechaconsulta = str_replace("*","",$fechaconsulta);
					//echo $fechaconsulta."\n";

					if((preg_match('/UTC/',$linea))){
						$date1=date_create($fechaconsulta);
						$date1->modify('-5 hours');
						$fechaconsulta = $date1->format('Y-m-d H:i:s.u');
						//echo $fechaconsulta."\n";
					}
					$tfc++;
				}

				if(!isset($inforouter)){
					if (preg_match('/RELEASE SOFTWARE/',$linea)){
						if (!preg_match('/Cisco IOS/',$linea)){
							$linea='Cisco IOS Software, '.$linea;	
						}
						$inforouter=$linea;
						$versionl=explode(', ',$linea);
						$tipo_router=$versionl[0];
						$software=$versionl[1];
						$version=$versionl[2];
						$release=$versionl[3];
						//echo $tipo_router." ".$software." ".$version." ".$release."\n";
					}
				}

				if (preg_match('/ROM:/',$linea)){
					$version_l=$linea;
					//echo $version_l."\n";
				}

				if (preg_match('/uptime/',$linea)){
					$hostname_uptime=$linea;
					$uptime=preg_replace('/[a-zA-Z0-9\-]+\suptime\sis\s/',"",$linea);
					$up_time = preg_replace('/, /'," ",$uptime);
					//echo $up_time."\n";
				}

				if (preg_match('/System returned/',$linea)){
					$mensaje=$linea;
					//echo $mensaje."\n";
				}

				if (preg_match('/System restarted/',$linea)){
					$info_reinicio=$linea;
					$tiempo=explode(' ',$linea);
					$fechareinicio=$tiempo[8]."-".$arrMonth[$tiempo[6]]."-".$tiempo[7]." ".$tiempo[3];
					$fechareinicio = str_replace("*","",$fechareinicio);

					if($tiempo[4]=="UTC"){
						$date1=date_create($fechareinicio);
						$date1->modify('-5 hours');
						$fechareinicio = $date1->format('Y-m-d H:i:s.u');
					}

					//echo $fechareinicio."\n";
				}

				if (preg_match('/System image/',$linea)){
					$imagen_sistema=preg_replace('/"/',"",$linea);
					//echo $imagen_sistema."\n";
				}

				if (preg_match('/Device#/',$linea)){
					$cnsn=$c+2;
				}

				if (($c==$cnsn) && ($cnsn>0)){
					$series=explode(' ',$linea);
					$device = $series[0];
					$pid = $series[1];
					$serie = $series[2];
					//echo $device." ".$pid." ".$serie."\n";
				}

				if(preg_match('/Protocol Address/',$linea)){
					$inid=$c;
				}
				if(preg_match('/#sh int/',$linea)){
					$find=$c;
				}
				if (($inid>0)&&($c>$inid) && (!(preg_match('/#sh int/',$linea)))&&$find==0){
					$dispositivo=explode(' ',$linea);
					
					if(($dispositivo[2]=="-") && ($ipl==0)){
						$ip_lan = $dispositivo[1];
						$ipl++;
					}

					if(!($dispositivo[3]=="Incomplete")){

						$dispositivos[]=array('protocolo'=>$dispositivo[0],'ip_adress'=>$dispositivo[1],'tiempo'=>$dispositivo[2],'hardware'=>$dispositivo[3],'tipo'=>$dispositivo[4],'interfaz'=>$dispositivo[5],'ip_man'=>$ip_man,'fecha_consulta'=>$fechaconsulta);
					}

				}
			}
			$done = ($stream === '0');
			$c++;
		}

		if($fechareinicio == NULL){
			$upt=preg_replace('/[a-zA-Z0-9\-]+\suptime\sis\s/',"",$hostname_uptime);

			$ups=explode(", ",$upt);

			$fechareinicio = $fechaconsulta;

			foreach($ups as $up){
				//echo $up."<br>";
				$date2=date_create($fechareinicio);
				//echo $up."<br>";
				
				$date2->modify('-'.$up);
				
				$fechareinicio = $date2->format('Y-m-d H:i:s');
			}

		}

		$rango = (strtotime($fechaconsulta) - strtotime($fechareinicio));

		$consulta_energia_FL=fopen("consulta_energia.txt","a");
		$dispositivos_FL=fopen("dispositivos.txt","a");
		$flujo_FL=fopen("flujo.txt","a");
		$plantilla_FL=fopen("plantilla.txt","a");
		$serie_FL=fopen("serie.txt","a");
		$version_FL=fopen("version.txt","a");

		fputs($consulta_energia_FL,trim($ip_man.'	'.$c2.'	'.$hostname.'	'.$fechaconsulta.'	'.$fechareinicio.'	'.$inforouter.'	'.$up_time.'	'.$rango.'	'.$hostname_uptime.'	'.$mensaje.'	'.$info_reinicio.'	'.$imagen_sistema));

		for($i=0;$i<count($dispositivos);$i++){

				if($i>0)fputs($dispositivos_FL,"\n");
				fputs($dispositivos_FL,trim($dispositivos[$i]['protocolo']."	".$dispositivos[$i]['ip_adress']."	".$dispositivos[$i]['tiempo']."	".$dispositivos[$i]['hardware']."	".$dispositivos[$i]['tipo']."	".$dispositivos[$i]['interfaz']."	".$dispositivos[$i]['ip_man']."	".$ip_lan."	".$dispositivos[$i]['fecha_consulta']));
		}
		
		for($i=0;$i<count($flujo);$i++){

			if($i>0)fputs($flujo_FL,"\n");
			fputs($flujo_FL,trim($flujo[$i]['ip_man']."	".$flujo[$i]['fecha_consulta']."	".$flujo[$i]['interfaz']."	".$flujo[$i]['ihq']."	".$flujo[$i]['iqd']."	".$flujo[$i]['ohq']."	".$flujo[$i]['oqd']."	".$flujo[$i]['rxbs']."	".$flujo[$i]['rxps']."	".$flujo[$i]['txbs']."	".$flujo[$i]['txps']));
		}
		fputs($plantilla_FL,trim($fechaconsulta.'	'.$ip_man.'	'.$version_l));
		fputs($serie_FL,trim($fechaconsulta.'	'.$ip_man.'	'.$device.'	'.$pid.'	'.$serie));
		fputs($version_FL,trim($fechaconsulta.'	'.$ip_man.'	'.$tipo_router.'	'.$software.'	'.$version.'	'.$release));

		fclose($consulta_energia_FL);
		fclose($dispositivos_FL);
		fclose($flujo_FL);
		fclose($plantilla_FL);
		fclose($serie_FL);
		fclose($version_FL);
		//print_r($flujo);
		echo "correcto >>>> 	".$ip_man."	".$ip_asr."	".$c2."	fecha consulta ".$fechaconsulta."\n";
		break;
	}
}


function insertar(){

	$consulta_energia=null;
	$dispositivos=null;
	$flujo=null;
	$plantilla=null;
	$serie=null;
	$version=null;

	$fp=fopen("consulta_energia.txt","r");
	$datos=fread($fp,filesize("consulta_energia.txt"));
	$lineas=explode('\n',$datos);
	foreach ($lineas as $value) {
		$consulta_energia=explode("	", $value);
		//print_r($datos);

	}
	fclose($fp);

	$query="insert into consulta_energia(ip_man,c2,hostname, fecha_consulta, fecha_reinicio, info_router, up_time, uptime, hostname_uptime, mensaje, info_reinicio, imagen_sistema) values('$consulta_energia[0]','$consulta_energia[1]','$consulta_energia[2]','$consulta_energia[3]','$consulta_energia[4]','$consulta_energia[5]', '$consulta_energia[6]', '$consulta_energia[7]', '$consulta_energia[8]', '$consulta_energia[9]', '$consulta_energia[10]','$consulta_energia[11]')";
		$result = conexion('bgpt','energia',$query);
		$id_energia = $result[0]['id'];

		$ip_active ="";


		$fp=fopen("dispositivos.txt","r");

		for($i=0;!feof($fp);$i++) {
			$value=fgets($fp);
			$dispositivos=explode("	", $value);

			if($i>0){
				$ip_active .= ",";
			}
			$ip_active .= "'".$dispositivos[1]."'";
			


			$query = "select id_dispositivo, ip_adress, hardware, ip_man from dispositivos where ip_adress ='".$dispositivos[1]."' and ip_man = '".$consulta_energia[0]."' and activo = '1'";
			//echo($query);

			$result = conexion('bgpt','energia',$query);


			if($result[0]['numRegs'] == 0){

				$query="insert into dispositivos(id_consulta_energia,protocolo, ip_adress, tiempo, hardware, tipo, interfaz, ip_man, ip_lan, fecha_consulta) values('".$id_energia."','".$dispositivos[0]."','".$dispositivos[1]."','".$dispositivos[2]."','".$dispositivos[3]."','".$dispositivos[4]."','".$dispositivos[5]."','".$dispositivos[6]."','".$dispositivos[7]."','".$dispositivos[8]."')";
				

				$resultd = conexion('bgpt','energia',$query);

			}

			if($result[0]['numRegs'] == 1)
			{
				$query = "select id_dispositivo, ip_adress, hardware, ip_man from dispositivos where ip_adress ='".$dispositivos[1]."'  and hardware not like '".$dispositivos[3]."' and ip_man = '".$consulta_energia[0]."' and activo = '1'" ;

				$resulthd = conexion('bgpt','energia',$query);

				if($resulthd[0]['numRegs'] > 0){

					$query="insert into dispositivos(id_consulta_energia,protocolo, ip_adress, tiempo, hardware, tipo, interfaz, ip_man, ip_lan, fecha_consulta) values('".$id_energia."','".$dispositivos[0]."','".$dispositivos[1]."','".$dispositivos[2]."','".$dispositivos[3]."','".$dispositivos[4]."','".$dispositivos[5]."','".$dispositivos[6]."','".$dispositivos[7]."','".$dispositivos[8]."')";

					$resultdn = conexion('bgpt','energia',$query);
					$query="update dispositivos set activo = 0 where id_dispositivo = ".$resulthd[1]['id_dispositivo'];
					$resultdu = conexion('bgpt','energia',$query);					


				}
			}

		}
		fclose($fp);

		$query = "update dispositivos set activo = 0 where ip_man = '$consulta_energia[0]' and ip_adress not in ($ip_active)";
		$resultupi = conexion('bgpt','energia',$query);

		//$fp=fopen("flujo.txt","r");
		//$datos=fread($fp,filesize("flujo.txt"));
		//$lineas=explode('\n',$datos);

		//foreach ($lineas as $value) {
			//$flujo=explode("	", $value);

			//$query="insert into flujo(id_consulta_energia, ip_man, fecha_consulta, interfaz, ihq, iqd, ohq, oqd, rxbs, rxps, txbs, txps)values('".$id_energia."','".$flujo[0]."','".$flujo[1]."','".$flujo[2]."','".$flujo[3]."','".$flujo[4]."','".$flujo[5]."','".$flujo[6]."','".$flujo[7]."','".$flujo[8]."','".$flujo[9]."','".$flujo[10]."')";
			//$result = conexion('bgpt','energia',$query);

			//print_r($datos);

		//}
		//fclose($fp);
		$fp=fopen("plantilla.txt","r");
		$datos=fread($fp,filesize("plantilla.txt"));
		$lineas=explode('\n',$datos);
		foreach ($lineas as $value) {
			$plantilla=explode("	", $value);
			//print_r($datos);

		}
		fclose($fp);

		$query = "select id_plantilla, ip_man from plantilla where ip_man = '$consulta_energia[0]' and activo = '1'";
		$resultp = conexion('bgpt','energia',$query);
		if($resultp[0]['numRegs'] == 0){

			$query="insert into plantilla(id_consulta_energia, fecha_consulta, ip_man, version) values('$id_energia','$plantilla[0]','$plantilla[1]','$plantilla[2]')";
			$result = conexion('bgpt','energia',$query);
		}

		if($resultp[0]['numRegs'] == 1){
			$query = "select id_plantilla, ip_man from plantilla where ip_man = '$consulta_energia[0]' and version not like '$plantilla[2]' and activo = '1'";
			$resultpn = conexion('bgpt','energia',$query);

			if($resultpn[0]['numRegs'] > 0 )
			{
				$query="insert into plantilla(id_consulta_energia, fecha_consulta, ip_man, version) values('$id_energia','$plantilla[0]','$plantilla[1]','$plantilla[2]')";
				$resultpnn = conexion('bgpt','energia',$query);

				$query="update plantilla set activo = 0 where id_plantilla = ".$resultpn[1]['id_plantilla'];
				$resultspu = conexion('bgpt','energia',$query);
			}

		}

		$fp=fopen("serie.txt","r");
		$datos=fread($fp,filesize("serie.txt"));
		$lineas=explode('\n',$datos);
		foreach ($lineas as $value) {
			$serie=explode("	", $value);
			//print_r($datos);

		}
		fclose($fp);

		$query = "select id_consulta_energia, fecha_consulta, ip_man, device, pid, sn, activo from serie where ip_man = '$consulta_energia[0]' and activo = '1'";
		$results = conexion('bgpt','energia',$query);

		if($results[0]['numRegs']==0){

			$query = "insert into serie(id_consulta_energia, fecha_consulta, ip_man, device, pid, sn) values('$id_energia','$serie[0]','$serie[1]','$serie[2]','$serie[3]','$serie[4]')";
			$result = conexion('bgpt','energia',$query);

		}
		if($results[0]['numRegs'] == 1){
			$query = "select id_serie  from serie where ip_man = '$consulta_energia[0]' and sn not like '$serie[4]' and activo = '1'";
			$resultsn = conexion('bgpt','energia',$query);

			if($resultsn[0]['numRegs'] > 0){

				$query = "insert into serie(id_consulta_energia, fecha_consulta, ip_man, device, pid, sn) values('$id_energia','$serie[0]','$serie[1]','$serie[2]','$serie[3]','$serie[4]')";
				$resultsnn = conexion('bgpt','energia',$query);
				$query="update serie set activo = 0 where id_serie = ".$resultsn[1]['id_serie'];
				$resultsnu = conexion('bgpt','energia',$query);

			}
		}

		$fp=fopen("version.txt","r");
		$datos=fread($fp,filesize("version.txt"));
		$lineas=explode('\n',$datos);
		foreach ($lineas as $value) {
			$version=explode("	", $value);
			//print_r($datos);

		}
		fclose($fp);

		$query = "select id_consulta_energia, version from version where ip_man = '$consulta_energia[0]' and activo = '1'";
		$resultv = conexion('bgpt','energia',$query);

		if ($resultv[0]['numRegs'] == 0) {
			$query="insert into version(id_consulta_energia, fecha_consulta, ip_man, tipo_router, software, version, release_) values('$id_energia','$version[0]','$version[1]','$version[2]','$version[3]','$version[4]','$version[5]')";
			$result = conexion('bgpt','energia',$query);	
		}
		if ($resultv[0]['numRegs'] == 1) {
			$query = "select id_version, version from version where ip_man = '$consulta_energia[0]' and version not like '$version[4]' and activo = '1'";
			$resultvn = conexion('bgpt','energia',$query);
			
			if($resultvn[0]['numRegs'] > 0){
				$query="insert into version(id_consulta_energia, fecha_consulta, ip_man, tipo_router, software, version, release_) values('$id_energia','$version[0]','$version[1]','$version[2]','$version[3]','$version[4]','$version[5]')";

				$resultvnn = conexion('bgpt','energia',$query);
				$query="update version set activo = 0 where id_version = ".$resultvn[1]['id_version'];
				$resultsnu = conexion('bgpt','energia',$query);

			}
		}

}

function elemina_ip($ip){
$query ="delete from ips_consulta where ip = '$ip'";
$result=conexion('bgpt','energia',$query);
}

while(true){

		$query = "SELECT * FROM ips_consulta limit 1";


		$resultip = conexion('bgpt','energia',$query);
		
		for($i=1;$i<count($resultip);$i++){

			$ip_man = $resultip[$i]['ip'];

			//$ip_man = "10.210.4.222";
			//$ip_man = "10.205.14.33";
			//$ip_man = "10.202.1.38";

			$query = "SELECT * FROM ips_asr WHERE ip_man = '$ip_man' ";
			$result = conexion('bgpt','energia',$query);

				$ip_asr = NULL;
				$c2 = NULL;
				$proyecto =NULL;

				if($result[0]['numRegs']>0){
					$ip_asr=$result[1]['ip_asr'];
					$c2=$result[1]['asr'];
					$proy=explode(' ',$c2);
					$proyecto =$proy[2];
				}

			//crea hilo
			$thread = null;

			if(($proyecto =="8k")){
				if( ($ip_man=="10.205.14.33") || ($ip_man=="10.202.12.2") || ($ip_man=="10.202.12.1") || ($ip_man=="10.202.12.5") || ($ip_man=="10.205.22.162") || ($ip_man=="10.205.22.161") || ($ip_man=="10.204.6.201") || ($ip_man=="10.202.12.4") || ($ip_man=="10.202.12.3") ){

					$thread = new Thread('energia8k');
				}
				else{
					$thread = new Thread('energia8k_socket');
				}
			}
			elseif($proyecto == "7k"){
		 		$thread = new Thread('energia7k');
			}

			$thread->start($ip_man,$ip_asr,$c2);

			$h_inicio=date_create(date("Y-m-d H:i:s"));

			echo "iniciado >>>> 	".$ip_man."	".$ip_asr."	".$c2."	fecha consulta ".$h_inicio->format("Y-m-d H:i:s")."\n";

			while (date_diff($h_inicio,date_create(date("Y-m-d H:i:s")))->format("%s")<12){
				if(!($thread->isAlive())){
					//inserta datos
					insertar();
					elemina_ip($resultip[$i]['ip']);
					break;
				}
			}

			if($thread->isAlive()){
				echo "fallo    >>>> 	".$ip_man."	".$ip_asr."	".$c2."	fecha consulta ".$h_inicio->format("Y-m-d H:i:s")."\n";
				$thread->kill();

				//posix_kill( $this->pid, $_signal );
				elemina_ip($resultip[$i]['ip']);

				continue;
			}

			//sleep(1);
		}
	sleep(1);
}
?>