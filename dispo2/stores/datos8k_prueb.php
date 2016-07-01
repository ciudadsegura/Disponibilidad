<?php

	include("conexion.php");
	include("func.php");

	$fecha=$_POST["fecha"];
	$proyecto=$_POST["proyecto"];
	$fecha="2016-04-28";
	$proyecto="7k";
	$fecha_ini=$fecha." 00:00:00";
	$fecha_fin=$fecha." 23:59:59";
	$query="";
	$rango=0;
	$rango = (strtotime($fecha_fin) - strtotime($fecha_ini));
	$result_eventolog = array();
	$rows=array();
	$correcto=true;

	function search($array,$key,$value){
		$results=array();
		search_r($array,$key,$value,$results);
		if(!is_array($results)){
			$results=array();
		}
		return $results;
	}
	function search_r($array,$key,$value,&$results){
		if(!is_array($array)){
			return;
		}
		if(array_key_exists($key, $array)){
			if($array[$key]==$value){
				$results[]=$array;	
			}
		}
		foreach ($array as $subarray) {
			search_r($subarray,$key,$value,$results);
		}
	}

	function consultaMultipleBgpt($base,$ips,$fecha_ini,$fecha_fin){
		$resultado=array();
		$resultadoIni=array();
		$resultadoFin=array();
		$resultMTB=array();
		$resultMTB2=array();
		foreach ($ips as $subarray) {
			$query="select * from consulta_stv where 
			fecha_consulta<'$fecha_ini' and ip_man='".$subarray['ip_man']."' 
			order by fecha_consulta desc limit 1";
		 	$resultMTB = conexion('bgpt',$base,$query);
		 	if($resultMTB[0]["evento"]=="correcto"){
		 		if($resultMTB[0]["numRegs"]>0)
		 			$resultadoIni=array_merge($resultadoIni,array($subarray['ip_man']=>$resultMTB[1]));
		 	}else{
		 		echo $resultMTB[0]["msg"];
				$correcto=false;	
		 	}
		 	$query="select * from consulta_stv where 
			fecha_consulta>'$fecha_fin' and ip_man='".$subarray['ip_man']."' 
			order by fecha_consulta asc limit 1";
		 	$resultMTB2 = conexion('bgpt',$base,$query);
		 	if($resultMTB2[0]["evento"]=="correcto"){
		 		if($resultMTB2[0]["numRegs"]>0)
		 			$resultadoFin=array_merge($resultadoFin,array($subarray['ip_man']=>$resultMTB2[1]));
		 	}else{
		 		echo $resultMTB2[0]["msg"];
				$correcto=false;	
		 	}
		}
		$resultado[]=$resultadoIni;
		$resultado[]=$resultadoFin;
        return $resultado;
	}

	


	if($correcto){
		$result_asr=array();
		$asr="";
		$query="SELECT * FROM asr WHERE proyecto = '".$proyecto." T'";
		$result_asr=conexion('logs','vlan',$query);

		if($result_asr[0]["evento"]=="correcto")
		{
			$asr="";
			for($i=1; $i<=$result_asr[0]["numRegs"];$i++){
				if($asr!=""){
					$asr.=",";
				}
				$asr.="'".$result_asr[$i]['ip_asr']."'";
			}
		}else{
			echo $result_asr[0]["msg"];
			$correcto=false;
		}
	}
	if($correcto){
		$result_stv=array();
		$c2Centro=array();
		$c2Sur=array();
		$c2Norte=array();
		$c2Oriente=array();
		$c2Poniente=array();

		if($proyecto=='8k')
			$query="SELECT ip_man,vlan,c2_conexion from stvs_".$proyecto." natural join (stvs_".$proyecto."_detalles)";
		else
			$query="SELECT ip_man,vlan_trabajo vlan,c2 c2_conexion from stvs_".$proyecto." natural join (stvs_".$proyecto."_detalles)";

		$result_stv = conexion('logs','vlan',$query);
		if($result_stv[0]["evento"]=="correcto")
		{
			if($result_stv[0]["numRegs"]>0){
				$c2Centro=search($result_stv,'c2_conexion','C2 CENTRO');
				$c2Sur=search($result_stv,'c2_conexion','C2 SUR');
				$c2Norte=search($result_stv,'c2_conexion','C2 NORTE');
				$c2Oriente=search($result_stv,'c2_conexion','C2 ORIENTE');
				$c2Poniente=search($result_stv,'c2_conexion','C2 PONIENTE');
			}
		}else{
			echo $result_stv[0]["msg"];
			$correcto=false;
		}
		/***/
	}
	
	if($correcto){
		$result_bgp_ini=array();
		$result_bgp_fin=array();
		$shm_data=array();
		$shm_data =consultaMultipleBgpt("cnt_bgp".$proyecto,$c2Centro,$fecha_ini,$fecha_fin);
		$result_bgp_ini = array_merge($result_bgp_ini, $shm_data[0]);
		$result_bgp_fin = array_merge($result_bgp_fin, $shm_data[1]);
		$shm_data =consultaMultipleBgpt("sur_bgp".$proyecto,$c2Sur,$fecha_ini,$fecha_fin);
		$result_bgp_ini = array_merge($result_bgp_ini, $shm_data[0]);
		$result_bgp_fin = array_merge($result_bgp_fin, $shm_data[1]);
		$shm_data =consultaMultipleBgpt("nte_bgp".$proyecto,$c2Norte,$fecha_ini,$fecha_fin);
		$result_bgp_ini = array_merge($result_bgp_ini, $shm_data[0]);
		$result_bgp_fin = array_merge($result_bgp_fin, $shm_data[1]);
		$shm_data =consultaMultipleBgpt("ote_bgp".$proyecto,$c2Oriente,$fecha_ini,$fecha_fin);
		$result_bgp_ini = array_merge($result_bgp_ini, $shm_data[0]);
		$result_bgp_fin = array_merge($result_bgp_fin, $shm_data[1]);
		$shm_data =consultaMultipleBgpt("pte_bgp".$proyecto,$c2Poniente,$fecha_ini,$fecha_fin);
		$result_bgp_ini = array_merge($result_bgp_ini, $shm_data[0]);
		$result_bgp_fin = array_merge($result_bgp_fin, $shm_data[1]);
		
	}
	
	
	if($correcto){
		$busqueda=array();
		
		$query = "SELECT *,
					(case 	when fecha_inicio<='$fecha_ini' and fecha_termino<='$fecha_fin' then
							time_to_sec(TIMEDIFF(fecha_termino,'$fecha_ini')*100)/$rango
							when fecha_inicio>'$fecha_ini' and fecha_termino<'$fecha_fin' then
							time_to_sec(TIMEDIFF(fecha_termino,fecha_inicio)*100)/$rango
							when fecha_inicio<'$fecha_ini' and fecha_termino>'$fecha_fin' then
							time_to_sec(TIMEDIFF('$fecha_fin','$fecha_ini')*100)/$rango
							when fecha_inicio>'$fecha_ini' and fecha_termino>'$fecha_fin' then
							time_to_sec(TIMEDIFF('$fecha_fin',fecha_inicio)*100)/$rango
					end) as disp FROM detalle_log WHERE  ip_asr in ($asr) and fecha_inicio<='$fecha_fin' and fecha_termino>='$fecha_ini'";

		$result_eventolog = conexion('logs','logs',$query);
		if($result_eventolog[0]["evento"]=="error"){
			echo $result_eventolog[0]["msg"];
			$correcto=false;
		}
	}
	/*
	if($correcto){
		//Se escribe los encabezados de la tabla 
		echo "<div class='dataTable_wrapper'>";
		echo "<table class='table table-reflow table-striped table-bordered table-hover' id='dispo8k'>";
		echo "<thead>";
			echo "<tr>";
	            echo "<th class='text-center'>IP MAN</th>";
	            echo "<th class='text-center'>VLAN</th>";
	            echo "<th class='text-center'>C2</th>";
	            echo "<th class='text-center'>No. DESCONEXIONES</th>";
	            echo "<th class='text-center'>% CONECTADO</th>";
	            echo "<th class='text-center'>CONCLUSION</th>";
	            echo "<th class='text-center'>STATUS</th>";
                echo "<th class='text-center'>TIME</th>";
                echo "<th class='text-center'>DATE</th>";
                echo "<th class='text-center'>STATUS</th>";
                echo "<th class='text-center'>TIME</th>";
                echo "<th class='text-center'>DATE</th>";
		echo "</tr>";
        echo "</thead>";
		echo "<tbody>";
		//Se escribe el cuerpo de la tabla 
		for($i=1;$i<=$result_stv[0]['numRegs'];$i++){
			$eventosLog=array();
			$eventosBGPIni=array();
			$eventosBGPFin=array();
			$ips=array();
			$tiempo_desc=0.0;
			$desconexiones=0;
			$conclusion="";
			$ips=$result_stv[$i];

			//Se obtienen los eventos de los logs
			$eventosLog=search($result_eventolog,'ip_man',$ips['ip_man']);
			//
			//Se obtienen los eventos de los Bgps
			if(array_key_exists($ips['ip_man'], $result_bgp_fin)){
				$eventosBGPFin=$result_bgp_fin[$ips['ip_man']];
			}
			if(array_key_exists($ips['ip_man'], $result_bgp_ini)){
				$eventosBGPIni=$result_bgp_ini[$ips['ip_man']];
			}
			//
			
			if(count($eventosLog)>0){
			//Se calcula el tiempo de desconexion de los logs
				$desconexiones=count($eventosLog);
				foreach ($eventosLog as $key => $value) {
					$tiempo_desc .= floatval($value['disp']);
				}
			//
			}else{
				//Se obtiene el status del bgp si no hay logs
				if(count($eventosBGPFin)>0 && count($eventosBGPIni)>0){
					if(!is_numeric($eventosBGPFin['estatus'])){
							$desconexiones=1;
							$tiempo_desc=100;
					}else{
						if($eventosBGPFin['up_time']>$eventosBGPIni['up_time']){
							$tiempo_desc=0;	
						}else{
							$conclusion = "ANALIZAR FALLA";
						}
						
					}
				}else{
					$tiempo_desc=0;
				}
			}

			$tiempo_desc = 100-$tiempo_desc;
			if($conclusion==""){
				if($tiempo_desc > 99.95){
						$conclusion = "CUMPLE NATURAL";
				}
				elseif (($tiempo_desc<=99.95) && ($tiempo_desc>99.90)) {
					$conclusion = "ULTIMA MILLA";
				}
				else{
					$conclusion = "ANALIZAR FALLA";
				}
			}

			switch ($conclusion) {
				case 'CUMPLE NATURAL':
					echo "<tr class='success'>";
					break;
				case 'ULTIMA MILLA':
					echo "<tr class='warning'>";
					break;
				case 'ANALIZAR FALLA':
					echo "<tr class='danger'>";
					break;
			}
				echo "<td>".$ips['ip_man']."</td>";
				echo "<td>".$ips['vlan']."</td>";
				echo "<td>".$ips["c2"]."</td>";
				echo "<td>".$desconexiones."</td>";
				echo "<td>".$tiempo_desc."</td>";
				echo "<td>".$conclusion."</td>";
				if(count($eventosBGPIni)>0){
					echo "<td>".$eventosBGPIni["estatus"]."</td>";
					echo "<td>".$eventosBGPIni["up_down"]."</td>";
					echo "<td>".$eventosBGPIni["fecha_consulta"]."</td>";
				}else{
					echo "<td>-</td>";
					echo "<td>-</td>";
					echo "<td>-</td>";
				}
				if(count($eventosBGPFin)>0){
					echo "<td>".$eventosBGPFin["estatus"]."</td>";
					echo "<td>".$eventosBGPFin["up_down"]."</td>";
					echo "<td>".$eventosBGPFin["fecha_consulta"]."</td>";
				}else{
					echo "<td>-</td>";
					echo "<td>-</td>";
					echo "<td>-</td>";
				}
			echo"</tr>";
		}
		echo "</tbody>";
		echo "</table>";
		echo "</div>";
	}
	*/
	
?>

