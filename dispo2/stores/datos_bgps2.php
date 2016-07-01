<?php
	set_time_limit(0);

	include("conexion.php");
	include("func.php");
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
	function consultaMultiplebgptEventos($base,$fechaIni,$fechaFin,$rango){
		$resultMTB=array();
			$query = "SELECT *,
				(case 	when fecha_inicio<='$fechaIni' and fecha_termino<='$fechaFin' then
						time_to_sec(TIMEDIFF(fecha_termino,'$fechaIni'))
						when fecha_inicio>'$fechaIni' and fecha_termino<'$fechaFin' then
						time_to_sec(TIMEDIFF(fecha_termino,fecha_inicio))
						when fecha_inicio<'$fechaIni' and fecha_termino>'$fechaFin' then
						time_to_sec(TIMEDIFF('$fechaFin','$fechaIni'))
						when fecha_inicio>'$fechaIni' and fecha_termino>'$fechaFin' then
						time_to_sec(TIMEDIFF('$fechaFin',fecha_inicio))
				end) as tiempo  FROM bgps_detalles WHERE  fecha_inicio<='$fechaFin' and fecha_termino>='$fechaIni' ";
		 	$resultMTB = conexion('bgpt',$base,$query);
		 	if($resultMTB[0]["evento"]=="error"){
		 		echo $resultMTB[0]["msg"];
				exit;
		 	}

        return $resultMTB;
	}
	function crearTabla($result_asr,$result_bgp_eventos,$rango,$c2,$fechaIni,$fechaFin){
		$ipsC=$result_asr[0]['numRegs'];
		for($i=1;$i<=$ipsC;$i++){
		//if($i<12760)
		//	continue;

			$eventosLog=array();
			$eventosBGPFin=array();
			$ips=array();
			$porcentaje_desc=0.0;
			$porcentaje_con=0.0;
			$tiempo_desc=0;
			$tiempo_con=0;
			$desconexiones=0;
			$conclusion="";
			$ips=$result_asr[$i];

			//Se obtienen los eventos de los logs
			$eventosLog=search($result_bgp_eventos,'ip_man',$ips['ip_man']);
			//print_r($eventosLog);
			//
			//
			
			if(count($eventosLog)>0){
			//Se calcula el porcentaje de desconexion de los logs
				$desconexiones=count($eventosLog);
				foreach ($eventosLog as $key => $value) {
					$tiempo_desc+= intval($value['tiempo']);
					$porcentaje_desc += (floatval($value['tiempo'])*100)/$rango;
				}
			//
			}else{
				//Se obtiene el status del bgp si no hay logs
				if(!is_numeric($ips['estatus'])){
						$desconexiones=1;
						$porcentaje_desc=100;
						$tiempo_desc=$rango;
				}else{
					$porcentaje_desc=0;	
					$tiempo_desc=0;
				}
			}
			$porcentaje_con = 100-$porcentaje_desc;
			$tiempo_con = $rango-$tiempo_desc;
			if($conclusion==""){
				if($porcentaje_con > 99.95){
						$conclusion = "CUMPLE NATURAL";
				}
				elseif (($porcentaje_con<=99.95) && ($porcentaje_con>99.90)) {
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
				echo "<td><a href='disponibilidad_detalles.php?ip=".$ips['ip_man']."&asr=$c2&fechaIni=$fechaIni&fechaFin=$fechaFin' target='_blank'>".$ips['ip_man']."</a></td>";
				echo "<td>".$ips["asr"]."</td>";
				echo "<td>".$ips["estatus"]."</td>";
				echo "<td>".segundos($ips["up_time"])."</td>";
				echo "<td>".$ips["fecha_consulta"]."</td>";
				echo "<td>".$desconexiones."</td>";
				echo "<td>".round($porcentaje_con,4)."</td>";
				echo "<td>".segundos($tiempo_con)."</td>";
				echo "<td>".round($porcentaje_desc,4)."</td>";
				echo "<td>".segundos($tiempo_desc)."</td>";
				echo "<td>".$conclusion."</td>";
				
			echo"</tr>";
		}
	}

	$mes=0;
	$c2="";
	$fechaFin="";
	$diaPart="";
	$rango=0;
	$particion=0;
	if(isset($_REQUEST["fecha"]) && trim($_REQUEST["fecha"])!=""){
		$mes=$_REQUEST["fecha"];
		$diasMes=cal_days_in_month(CAL_GREGORIAN,explode("-", $mes)[1], date("Y"));
		$fechaFin=( date("$mes-$diasMes 23:59:59"));	
		$diaPart=$diasMes;
		if(strtotime($fechaFin)>strtotime(date("Y-m-d H:i:s"))){
			$particion="p".date("Ymd");
		}else{
			$particion="p". str_replace("-", "", $mes).$diaPart;
		}
	}else{
		$mes=date("Y-m");
		$fechaFin=( date("Y-m-d H:i:s"));	
		$diaPart=date("d");
		$particion="p". str_replace("-", "", $mes).$diaPart;
	}
	if(isset($_REQUEST["asr"])){
		$c2=$_REQUEST["asr"];
	}
	
	$fechaIni=( date("$mes-01 00:00:00"));
	echo "Periodo $fechaIni - $fechaFin";

	
	$rango = (strtotime($fechaFin) - strtotime($fechaIni));
	
	
	//Consulta las ips
	$query="CALL ips_estatus('$particion','$c2')";
	$result_asr=conexion('bgpt','energia',$query);

	if($result_asr[0]["evento"]=="correcto")
	{
		if($result_asr[0]["numRegs"]==0){
			exit;	
		}
	}else{
		echo $result_asr[0]["msg"];
		exit;
	}

	$result_bgp_fin=array();
	$result_bgp_eventos=array();
	$shm_data=array();

	//Se obtiene los estatus actuales
	switch ($c2) {
		case 'C2 CENTRO 8k':
			//$c2Centro8k=search($result_asr,'asr','C2 CENTRO 8k');
			$result_bgp_eventos =consultaMultiplebgptEventos("cnt_bgp8k",$fechaIni,$fechaFin,$rango);
			break;
		case 'C2 SUR 8k':
			//$c2Sur8k=search($result_asr,'asr','C2 SUR 8k');
			$result_bgp_eventos =consultaMultiplebgptEventos("sur_bgp8k",$fechaIni,$fechaFin,$rango);
			break;
		case 'C2 NORTE 8k':
			//$c2Norte8k=search($result_asr,'asr','C2 NORTE 8k');
			$result_bgp_eventos =consultaMultiplebgptEventos("nte_bgp8k",$fechaIni,$fechaFin,$rango);
			break;
		case 'C2 ORIENTE 8k':
			//$c2Oriente8k=search($result_asr,'asr','C2 ORIENTE 8k');
			$result_bgp_eventos =consultaMultiplebgptEventos("ote_bgp8k",$fechaIni,$fechaFin,$rango);
			break;
		case 'C2 PONIENTE 8k':
			//$c2Poniente8k=search($result_asr,'asr','C2 PONIENTE 8k');
			$result_bgp_eventos =consultaMultiplebgptEventos("pte_bgp8k",$fechaIni,$fechaFin,$rango);
			break;
		case 'C2 CENTRO 7k':
			//$c2Centro7k=search($result_asr,'asr','C2 CENTRO 7k');
			$result_bgp_eventos =consultaMultiplebgptEventos("cnt_bgp7k",$fechaIni,$fechaFin,$rango);
			break;
		case 'C2 SUR 7k':
			//$c2Sur7k=search($result_asr,'asr','C2 SUR 7k');
			$result_bgp_eventos =consultaMultiplebgptEventos("sur_bgp7k",$fechaIni,$fechaFin,$rango);
			break;
		case 'C2 NORTE 7k':
			//$c2Norte7k=search($result_asr,'asr','C2 NORTE 7k');
			$result_bgp_eventos =consultaMultiplebgptEventos("nte_bgp7k",$fechaIni,$fechaFin,$rango);
			break;
		case 'C2 ORIENTE 7k':
			//$c2Oriente7k=search($result_asr,'asr','C2 ORIENTE 7k');
			$result_bgp_eventos =consultaMultiplebgptEventos("ote_bgp7k",$fechaIni,$fechaFin,$rango);
			break;
		case 'C2 PONIENTE 7k':
			//$c2Poniente7k=search($result_asr,'asr','C2 PONIENTE 7k');
			$result_bgp_eventos =consultaMultiplebgptEventos("pte_bgp7k",$fechaIni,$fechaFin,$rango);
			break;
	}

	//Se escribe los encabezados de la tabla 

		echo "<div class='row'>";
        echo "<div class='col-lg-12'>";
		echo "<div class='dataTable_wrapper'>";
		echo "<table class='table table-reflow table-striped table-bordered table-hover' id='dispo8k'>";
		echo "<thead>";
			echo "<tr>";
	            echo "<th class='text-center'>IP MAN</th>";
	            echo "<th class='text-center'>C2</th>";
	            echo "<th class='text-center'>ESTATUS</th>";
                echo "<th class='text-center'>TIEMPO BGP</th>";
                echo "<th class='text-center'>FECHA DEL ULTIMO BGP</th>";
	            echo "<th class='text-center'>No. DESCONEXIONES</th>";
	            echo "<th class='text-center'>% CONECTADO</th>";
	            echo "<th class='text-center'>TIEMPO CONECTADO</th>";
	            echo "<th class='text-center'>% DESCONECTADO</th>";
	            echo "<th class='text-center'>TIEMPO DESCONECTADO</th>";
	            echo "<th class='text-center'>CONCLUSION</th>";
                //echo "<th class='text-center'>DATE</th>";
		echo "</tr>";
        echo "</thead>";
		echo "<tbody>";
		crearTabla($result_asr,$result_bgp_eventos,$rango,$c2,$fechaIni,$fechaFin);
		echo "</tbody>";
		echo "</table>";
		echo "</div>";
		echo "</div>";
		echo "</div>";
?>

