<?php
	set_time_limit(0);

	include("conexion.php");
	include("func.php");
	include("dispo_resp.php");
	
	$query="SELECT * from disponibilidad";

	$result_asr=conexion('bgpr',"logs_8kt",$query);

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

	$query="select fecha_inicio from (SELECT FORMAT_TIME_MINUTE(fecha_inicio) fecha_inicio,count(*) cuenta FROM caidas_masivas group by FORMAT_TIME_MINUTE(fecha_inicio)) a where cuenta>10";

	$result_masiva=conexion('bgpr',"logs_8kt",$query);

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

	foreach ($result_masiva as $key => $value) {
		if($key==0){
			continue;
		}

		$tiempos_fecha=array();
		$query="select tiempo from  caidas_masivas where FORMAT_TIME_MINUTE(fecha_inicio)='$value[fecha_inicio]'";
		$result_masiva_fecha=conexion('bgpr',"logs_8kt",$query);

		if($result_masiva_fecha[0]["evento"]=="correcto")
		{
			if($result_masiva_fecha[0]["numRegs"]==0){
				echo"trono aki2";
				exit;	
			}
		}else{
			echo $result_masiva_fecha[0]["msg"];
			exit;
		}

		foreach ($result_masiva_fecha as $key_fecha => $value_fecha) {
			if($key_fecha==0){
				continue;
			}

			if(in_array($value_fecha['tiempo'], $tiempos_fecha)){
				continue;
			}

			$query="call caida_masiva_p('$value[fecha_inicio]',$value_fecha[tiempo])";

			$result_=conexion('bgpr',"logs_8kt",$query);
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
				if(array_key_exists ($value2['id_ini'] ,$caidasMasivasTipo )) 
				{
					continue;
				}
					$caidasMasivas[]=array('id_ini'=>$value2['id_ini'],'fecha_inicio'=>$value2['fecha_inicio'],'tiempo'=>$value2['tiempo']);

					if($repisa!=$value2['repisa'] && $repisa!=''){
						$tipoCaida='CAIDA MASIVA';
					}
					$caidaCant++;
					$repisa=$value2['repisa'];
			}
			if($caidaCant>9){
				foreach ($caidasMasivas as $key2 => $value2) {

						$caidasMasivasTipo[$value2['id_ini']]=array('tipoCaida'=> $tipoCaida,'caidaCant'=>$caidaCant,'fecha_inicio'=> $value2['fecha_inicio']);

					if(!in_array($value2['tiempo'], $tiempos_fecha)){
						array_push($tiempos_fecha, $value2['tiempo']);
					}
					
					//$caidasMasivasTipo[]=array('num_ini'=> $value2['num_ini'],'tipoCaida'=> $tipoCaida,'caidaCant'=>$caidaCant,'fecha_inicio'=> $value2['fecha_inicio']);
				}
			}
			echo $value['fecha_inicio']."\n";
		}
	}
		
	if(file_exists("Disponibilidad_8kt".".csv"))
	{
		unlink("Disponibilidad_8kt".".csv");
	}
	$fp=fopen("Disponibilidad_8kt".".csv","a");

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
		/*
		if($value['evento']==''){
			if(($ip!=$value['ip_man']) && $value['tipo']=='Up'){
				//$fecha_t=$fecha_i;
				$value['fecha_termino']=$value['fecha_inicio'];
				$value['fecha_inicio']='';
				$value['num_ter']=$value['num_ini'];
				$value['num_ini']='';
				$value['evento']='INICIO DESCONECTADO';

			}else{
				if($value['tipo']=='Up'){
					$value['fecha_termino']=$value['fecha_inicio'];
					$value['fecha_inicio']='';
					$value['num_ter']=$value['num_ini'];
					$value['num_ini']='';
				}
				$value['evento']='EVENTO INCOMPLETO';				
			}
		}
		*/
		/*
		if($value['fecha_inicio']=='' && $value['evento']!='EVENTO INCOMPLETO'){
			$d=new DateTime($value['fecha_termino']);
			$d->modify('first day of this month');
			$value['fecha_inicio']=$d->format('Y-m-d 00:00:00');

		}elseif($value['fecha_termino']=='' && $value['evento']!='EVENTO INCOMPLETO'){
			$d=new DateTime($value['fecha_inicio']);
			$d->modify('last day of this month');
			$value['fecha_termino']=$d->format('Y-m-d 23:59:59');
		}


		if($value['evento']!='EVENTO INCOMPLETO'){
			$rango = segundos(strtotime($value['fecha_termino']) - strtotime($value['fecha_inicio']));
		}
		*/

		if (array_key_exists($value['id_ini'],$caidasMasivasTipo)){
			$caida=$caidasMasivasTipo[$value['id_ini']]['tipoCaida']." ".$caidasMasivasTipo[$value['id_ini']]['caidaCant'];


			//$query_caidas="select ip_man, redondear(fecha_inicio) fecha_inicio_r, redondear(fecha_termino) fecha_termino_r, time_to_sec(timediff(REDONDEAR(fecha_termino),REDONDEAR(fecha_inicio))) as segundos, id_fechini, id_fechter FROM detalle_log where ip_man = '$value[ip_man_respaldo]' and fecha_inicio >='$value[fecha_inicio]'  and fecha_termino <= ADDTIME('$value[fecha_termino]', '0:2:0')";

			$query_caidas="select ip_man, fecha_inicio fecha_inicio_r, fecha_termino fecha_termino_r, tiempo as segundos, num_ini, num_ter FROM disponibilidad where ip_man ='$value[ip_man_respaldo]' and ('$value[fecha_inicio]'<=fecha_termino and '$value[fecha_termino]'>=fecha_inicio) and evento not like('EVENTO INCOMPLETO') and (tiempo between (".(strtotime($value['fecha_termino']) - strtotime($value['fecha_inicio']))."-500) and (".(strtotime($value['fecha_termino']) - strtotime($value['fecha_inicio']))."+500))";
			
			echo "$query_caidas"."\n";
			//$query_caidas="insert into caidas_masivas_t(ip_man, no_log_ini, fecha_inicio, no_log_fin, fecha_termino, tipo_evento, tiempo_desconectado, caida_masiva, facturacion, ip_asr, repisa, total_enlaces_repisa ,ip_man_respaldo) values ('$value[ip_man]','$value[num_ini]','$value[fecha_inicio]','$value[num_ter]','$value[fecha_termino]','$value[evento]','$rango','$caida','Mayo','$value[ip_asr]','$value[repisa]','$value[cuenta_ips]','$ip_man_r')";
			$result_caida=conexion('bgpr',"logs_8kr",$query_caidas);

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
		/*
		$bus=search($caidasMasivasTipo,'num_ini',$value['num_ini']);

		if(count($bus)>0){
			$caida=$bus[0]['tipoCaida']." ".$bus[0]['caidaCant'];
		}else{
			$caida=' ';
		}
		*/
		

		$d=new DateTime($value['fecha_inicio']);
		$value['fecha_inicio']=$d->format('d/m/Y H:i:s');
		$d=new DateTime($value['fecha_termino']);
		$value['fecha_termino']=$d->format('d/m/Y H:i:s');

		$rango=segundos($value['tiempo']);

		if($value['evento']!='EVENTO INCOMPLETO'){
			$lineaInsert="$value[ip_man],$value[num_ini],$value[fecha_inicio],$value[num_ter],$value[fecha_termino],$value[evento],$rango,$caida,Mayo,$value[ip_asr],$value[repisa],$value[cuenta_ips],$value[ip_man_respaldo],$value[caida_r],$value[fecha_inicio_respaldo],$value[fecha_termino_respaldo],$value[evento_r]\n";
			fputs($fp,$lineaInsert);
		}
		$ip=$value['ip_man'];	
	}
	fclose($fp);
?>

