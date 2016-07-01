<?php
	set_time_limit(0);

	include("conexion.php");
	include("func.php");
	include("dispo_resp.php");

	$query="SELECT * from disponibilidad WHERE evento not like ('EVENTO INCOMPLETO') order by ip_man asc, fecha_inicio asc";

	$result_asr=conexion('bgpr',"logs_7kt",$query);

	if($result_asr[0]["evento"]=="correcto")
	{
		if($result_asr[0]["numRegs"]==0){
			echo"trono aki";
			exit;	
		}
	}else{
		echo $result_asr[0]["msg"];
		exit;
	}

	//$query="SELECT * from caidas_masivas order by fecha_inicio asc";

	$query="select fecha_inicio from (SELECT fecha_inicio,count(*) cuenta FROM caidas_masivas group by fecha_inicio) a where cuenta>10";

	$result_masiva=conexion('bgpr',"logs_7kt",$query);

	if($result_masiva[0]["evento"]=="correcto")
	{
		if($result_masiva[0]["numRegs"]==0){
			echo"trono aki2";
			exit;	
		}
	}else{
		echo $result_masiva[0]["msg"];
		exit;
	}


	
	$caidasMasivasTipo=array();
	$fechas=array();

	foreach ($result_masiva as $key => $value) {
		if($key==0){
			continue;
		}
		if(in_array($value['fecha_inicio'], $fechas)){
			continue;
		}

		$query="call caida_masiva_p('$value[fecha_inicio]')";

		$result_=conexion('bgpr',"logs_7kt",$query);
		if($result_[0]["evento"]=="error"){
			echo $result_[0]["msg"];
			exit;
		}
		$tipoCaida='CAIDA DE REPISA';
		$caidaCant=0;
		$repisa='';
		$caidasMasivas=array();

		foreach ($result_ as $key2 => $value2) {
			if($key2==0){
				continue;
			}


			$caidasMasivas[]=array('id_ini'=>$value2['id_ini'],'fecha_inicio'=>$value2['fecha_inicio']);

			if($repisa!=$value2['repisa'] && $repisa!=''){
				$tipoCaida='CAIDA MASIVA';
			}
			$caidaCant++;
			$repisa=$value2['repisa'];
		}
		if($caidaCant>9){
			foreach ($caidasMasivas as $key2 => $value2) {
				$caidasMasivasTipo[$value2['id_ini']]=array('tipoCaida'=> $tipoCaida,'caidaCant'=>$caidaCant,'fecha_inicio'=> $value2['fecha_inicio']);
				if(!in_array($value2['fecha_inicio'], $fechas)){
					array_push($fechas, $value2['fecha_inicio']);
				}
				
				//$caidasMasivasTipo[]=array('num_ini'=> $value2['num_ini'],'tipoCaida'=> $tipoCaida,'caidaCant'=>$caidaCant,'fecha_inicio'=> $value2['fecha_inicio']);
			}
		}


		echo $value['fecha_inicio']."\n";

	}

	//caidas
	//print_r($caidasMasivasTipo);

		
	if(file_exists("Disponibilidad_7kt".".csv"))
	{
		unlink("Disponibilidad_7kt".".csv");
	}
	$fp=fopen("Disponibilidad_7kt".".csv","a");

	//+ DE 11 ENLACES + DE 2 REPISAS COMPLETAS CAIDA MASIVA NUM
	//+ DE 11 ENLACES 1 REPISAS CAIDA POR REPISA CAIDA DE REPISA NUM

	$lineaInsert="IP MAN,NO LOG,FECHA INICIO,NO LOG,FECHA TERMINO,TIPO EVENTO,TIEMPO DESCONECTADO,CAIDA MASIVA,FACTURACION,IP ASR, REPISA, TOTAL DE ENLACE POR REPISA, IP MAN RESPALDO, CAIDA C/S RESPALDO, FECHA INICIO RESPALDO, FECHA TERMINO RESPALDO, TIEMPO DESCONECTADO RESPALDO \n";
	fputs($fp,$lineaInsert);
	$ip='';

	foreach ($result_asr as $key => $value) {
		$rango=0;
		if($key==0){
			continue;
		}
		
		if (array_key_exists($value['id_ini'],$caidasMasivasTipo)){
			$caida=$caidasMasivasTipo[$value['id_ini']]['tipoCaida']." ".$caidasMasivasTipo[$value['id_ini']]['caidaCant'];


			//$query_caidas="select ip_man, redondear(fecha_inicio) fecha_inicio_r, redondear(fecha_termino) fecha_termino_r, time_to_sec(timediff(REDONDEAR(fecha_termino),REDONDEAR(fecha_inicio))) as segundos, id_fechini, id_fechter FROM detalle_log where ip_man = '$value[ip_man_respaldo]' and fecha_inicio >='$value[fecha_inicio]'  and fecha_termino <= ADDTIME('$value[fecha_termino]', '0:2:0')";

			//$query_caidas="insert into caidas_masivas_t(ip_man, no_log_ini, fecha_inicio, no_log_fin, fecha_termino, tipo_evento, tiempo_desconectado, caida_masiva, facturacion, ip_asr, repisa, total_enlaces_repisa ,ip_man_respaldo) values ('$value[ip_man]','$value[num_ini]','$value[fecha_inicio]','$value[num_ter]','$value[fecha_termino]','$value[evento]','$rango','$caida','Mayo','$value[ip_asr]','$value[repisa]','$value[cuenta_ips]','$ip_man_r')";

			//$query_caidas="select ip_man, redondear(fecha_inicio) fecha_inicio_r, redondear(fecha_termino) fecha_termino_r, time_to_sec(timediff(REDONDEAR(fecha_termino),REDONDEAR(fecha_inicio))) as segundos, id_fechini, id_fechter FROM detalle_log where ip_man ='$value[ip_man_respaldo]' and (date_add('$value[fecha_inicio]',interval ((".(strtotime($value['fecha_termino']) - strtotime($value['fecha_inicio'])).")/4) second) between fecha_inicio and fecha_termino)";

			$query_caidas="select ip_man, fecha_inicio as fecha_inicio_r, fecha_termino as fecha_termino_r, tiempo as segundos, num_ini, num_ter FROM disponibilidad where ip_man ='$value[ip_man_respaldo]' and ('$value[fecha_inicio]'<=fecha_termino and '$value[fecha_termino]'>=fecha_inicio) and evento not like ('EVENTO INCOMPLETO') and (tiempo between (".(strtotime($value['fecha_termino']) - strtotime($value['fecha_inicio']))."-500) and (".(strtotime($value['fecha_termino']) - strtotime($value['fecha_inicio']))."+500))";

			$result_caida=conexion('bgpr',"logs_7kr",$query_caidas);

			echo $query_caidas."\n";

			if(count($result_caida)>1)
			{
				$d=new DateTime($result_caida[1]['fecha_inicio_r']);
				$value['fecha_inicio_respaldo']=$d->format('d/m/Y H:i:s');
				$d=new DateTime($result_caida[1]['fecha_termino_r']);
				$value['fecha_termino_respaldo']=$d->format('d/m/Y H:i:s');

				$value['evento_r']=segundos($result_caida[1]['segundos']);
				$value['caida_r']="CAIDA MASIVA SIN RESPALDO";
			}
			else{
				$value['fecha_inicio_respaldo']=' ';
				$value['fecha_termino_respaldo']=' ';
				$value['evento_r']=' ';
				$value['caida_r']="CAIDA MASIVA CON RESPALDO";
			}


		}else{
			$caida=' ';
				$value['fecha_inicio_respaldo']=' ';
				$value['fecha_termino_respaldo']=' ';
				$value['evento_r']=' ';
				$value['caida_r']=' ';
		}
		$d=new DateTime($value['fecha_inicio']);
		$value['fecha_inicio']=$d->format('d/m/Y H:i:s');
		$d=new DateTime($value['fecha_termino']);
		$value['fecha_termino']=$d->format('d/m/Y H:i:s');

		$rango=segundos($value['tiempo']);

		//if($value['evento']!='EVENTO INCOMPLETO'){
			$lineaInsert="$value[ip_man],$value[num_ini],$value[fecha_inicio],$value[num_ter],$value[fecha_termino],$value[evento],$rango,$caida,Mayo,$value[ip_asr],$value[repisa],$value[cuenta_ips],$value[ip_man_respaldo],$value[caida_r],$value[fecha_inicio_respaldo],$value[fecha_termino_respaldo],$value[evento_r]\n";
			fputs($fp,$lineaInsert);
		//}
		$ip=$value['ip_man'];	
	}
	fclose($fp);
?>

