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

	function consultaMultipleBgpt($base,$particion,$ips,$fechaFin){
		$resultado=array();
		$resultadoFin=array();
		$resultMTB=array();
		foreach ($ips as $subarray) {
			$query="select * from consulta_stv where 
			fecha_consulta<'$fechaFin' and ip_man='".$subarray['ip_man']."' 
			order by fecha_consulta desc limit 1";
		 	$resultMTB = conexion('bgpt',$base,$query);
		 	if($resultMTB[0]["evento"]=="correcto"){
		 		if($resultMTB[0]["numRegs"]>0)
		 			$resultadoFin=array_merge($resultadoFin,array($subarray['ip_man']=>$resultMTB[1]));
		 	}else{
		 		echo $resultMTB[0]["msg"];
				exit;
		 	}
		}
        return $resultadoFin;
	}
	function consultaMultipleBgptEventos($base,$fechaIni,$fechaFin,$rango){
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
	function crearTabla($result_asr,$result_bgp_eventos,$result_bgp_fin,$rango){
		$ipsC=count($result_asr);
		for($i=0;$i<$ipsC;$i++){
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
			//Se obtienen los eventos de los Bgps
			$eventosBGPFin=$result_bgp_fin[$ips['ip_man']];
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
				if(count($eventosBGPFin)>0){
					if(!is_numeric($eventosBGPFin['estatus'])){
							$desconexiones=1;
							$porcentaje_desc=100;
							$tiempo_desc=$rango;
					}else{
						$porcentaje_desc=0;	
						$tiempo_desc=0;
					}
				}else{
					$desconexiones=1;
					$porcentaje_desc=100;
					$tiempo_desc=$rango;
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
				echo "<td><a href='detablles_disponibilidad.php?ip=".$ips['ip_man']."'>".$ips['ip_man']."</a></td>";
				echo "<td>".$ips["asr"]."</td>";
				if(count($eventosBGPFin)>0){
					echo "<td>".$eventosBGPFin["estatus"]."</td>";
					echo "<td>".$eventosBGPFin["up_down"]."</td>";
					echo "<td>".$eventosBGPFin["fecha_consulta"]."</td>";
				}else{
					echo "<td>-</td>";
					echo "<td>-</td>";
					echo "<td>-</td>";
				}
				echo "<td>".$desconexiones."</td>";
				echo "<td>".round($porcentaje_con,4)."</td>";
				echo "<td>".segundos($tiempo_con)."</td>";
				echo "<td>".round($porcentaje_desc,4)."</td>";
				echo "<td>".segundos($tiempo_desc)."</td>";
				echo "<td>".$conclusion."</td>";
				
			echo"</tr>";
		}
	}
//--------------------------------------------------------------------------------------------------------------------------------------------------------------//
	$mes=0;
	$c2="";
	$fechaFin="";
	$diaPart="";
	$rango=0;
	$tipo="";


	if(isset($_REQUEST["tipo"])){
		$tipo=$_REQUEST["tipo"];
	}

	if(isset($_REQUEST["fecha"]) && trim($_REQUEST["fecha"])!="" && ($_REQUEST["fecha"]!= date("Y-m"))){
		$mes=$_REQUEST["fecha"];
		$diasMes=cal_days_in_month(CAL_GREGORIAN,explode("-", $mes)[1], date("Y"));
		$fechaFin=( date("$mes-$diasMes 23:59:59"));	
		$diaPart=$diasMes;
	}else{
		$mes=date("Y-m");
		$fechaFin=( date("Y-m-d H:i:s"));	
		$diaPart=date("d");
	}

	$fechaIni=( date("$mes-01 00:00:00"));
	$particion="p". str_replace("-", "", $mes).$diaPart;
	$rango = (strtotime($fechaFin) - strtotime($fechaIni));

	if($tipo="Excel"){
		header("Content-Disposition:attachment; filename=\"Disponibilidad_".$fechaFin.".xls\"");
		header("Content-Type:application/vnd.ms-excel");
	}elseif($tipo="PDF"){
		header("Content-Type:application/octet-stream");
		header("Content-Disposition:attachment; filename=\"Disponibilidad_".$fechaFin.".pdf\"");
		header("Content-Transfer-Encoding:binary");
		header("Expires:0");
		header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
		header("Pragma: public");
	}	
//----------------------------------------------------------------------------------------------------------------------------------------------------------------//	
	//Consulta las ips
	$query="SELECT * FROM ips_asr
	 order by asr,ip_man asc";
	$result_asr=conexion('bgpt','energia',$query);

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
	$result_bgp_fin=array();
	$result_bgp_eventos=array();

	//Se obtiene los estatus actuales
	echo "<div class='dataTable_wrapper'>";
		echo "<table class='table table-reflow table-striped table-bordered table-hover' id='dispo8k'>";
		echo "<thead>";
			echo "<tr>";
	            echo "<th class='text-center'>IP MAN</th>";
	            echo "<th class='text-center'>C2</th>";
	            echo "<th class='text-center'>STATUS</th>";
                echo "<th class='text-center'>TIME</th>";
                echo "<th class='text-center'>DATE_LAST BGP</th>";
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
		

			$c2Centro8k=search($result_asr,'asr','C2 CENTRO 8k');
			$result_bgp_fin =consultaMultipleBgpt("cnt_bgp8k",$particion,$c2Centro8k,$fechaFin);
			$result_bgp_eventos =consultaMultipleBgptEventos("cnt_bgp8k",$fechaIni,$fechaFin,$rango);
			crearTabla($c2Centro8k,$result_bgp_eventos,$result_bgp_fin,$rango);

			$c2Sur8k=search($result_asr,'asr','C2 SUR 8k');
			$result_bgp_fin =consultaMultipleBgpt("sur_bgp8k",$particion,$c2Sur8k,$fechaFin);
			$result_bgp_eventos =consultaMultipleBgptEventos("sur_bgp8k",$fechaIni,$fechaFin,$rango);
			crearTabla($c2Sur8k,$result_bgp_eventos,$result_bgp_fin,$rango);

			$c2Norte8k=search($result_asr,'asr','C2 NORTE 8k');
			$result_bgp_fin =consultaMultipleBgpt("nte_bgp8k",$particion,$c2Norte8k,$fechaFin);
			$result_bgp_eventos =consultaMultipleBgptEventos("nte_bgp8k",$fechaIni,$fechaFin,$rango);
			crearTabla($c2Norte8k,$result_bgp_eventos,$result_bgp_fin,$rango);

			$c2Oriente8k=search($result_asr,'asr','C2 ORIENTE 8k');
			$result_bgp_fin =consultaMultipleBgpt("ote_bgp8k",$particion,$c2Oriente8k,$fechaFin);
			$result_bgp_eventos =consultaMultipleBgptEventos("ote_bgp8k",$fechaIni,$fechaFin,$rango);
			crearTabla($c2Oriente8k,$result_bgp_eventos,$result_bgp_fin,$rango);

			$c2Poniente8k=search($result_asr,'asr','C2 PONIENTE 8k');
			$result_bgp_fin =consultaMultipleBgpt("pte_bgp8k",$particion,$c2Poniente8k,$fechaFin);
			$result_bgp_eventos =consultaMultipleBgptEventos("pte_bgp8k",$fechaIni,$fechaFin,$rango);
			crearTabla($c2Poniente8k,$result_bgp_eventos,$result_bgp_fin,$rango);

			$c2Centro7k=search($result_asr,'asr','C2 CENTRO 7k');
			$result_bgp_fin =consultaMultipleBgpt("cnt_bgp7k",$particion,$c2Centro7k,$fechaFin);
			$result_bgp_eventos =consultaMultipleBgptEventos("cnt_bgp7k",$fechaIni,$fechaFin,$rango);
			crearTabla($c2Centro7k,$result_bgp_eventos,$result_bgp_fin,$rango);

			$c2Sur7k=search($result_asr,'asr','C2 SUR 7k');
			$result_bgp_fin =consultaMultipleBgpt("sur_bgp7k",$particion,$c2Sur7k,$fechaFin);
			$result_bgp_eventos =consultaMultipleBgptEventos("sur_bgp7k",$fechaIni,$fechaFin,$rango);
			crearTabla($c2Sur7k,$result_bgp_eventos,$result_bgp_fin,$rango);

			$c2Norte7k=search($result_asr,'asr','C2 NORTE 7k');
			$result_bgp_fin =consultaMultipleBgpt("nte_bgp7k",$particion,$c2Norte7k,$fechaFin);
			$result_bgp_eventos =consultaMultipleBgptEventos("nte_bgp7k",$fechaIni,$fechaFin,$rango);
			crearTabla($c2Norte7k,$result_bgp_eventos,$result_bgp_fin,$rango);

			$c2Oriente7k=search($result_asr,'asr','C2 ORIENTE 7k');
			$result_bgp_fin =consultaMultipleBgpt("ote_bgp7k",$particion,$c2Oriente7k,$fechaFin);
			$result_bgp_eventos =consultaMultipleBgptEventos("ote_bgp7k",$fechaIni,$fechaFin,$rango);
			crearTabla($c2Oriente7k,$result_bgp_eventos,$result_bgp_fin,$rango);

			$c2Poniente7k=search($result_asr,'asr','C2 PONIENTE 7k');
			$result_bgp_fin =consultaMultipleBgpt("pte_bgp7k",$particion,$c2Poniente7k,$fechaFin);
			$result_bgp_eventos =consultaMultipleBgptEventos("pte_bgp7k",$fechaIni,$fechaFin,$rango);
			crearTabla($c2Poniente7k,$result_bgp_eventos,$result_bgp_fin,$rango);	

		echo "</tbody>";
		echo "</table>";
		echo "</div>";
?>

