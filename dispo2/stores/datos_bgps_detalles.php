<?php
	include("conexion.php");
	include("func.php");

	function consultaMultipleBgptEventos($base,$fechaIni,$fechaFin,$ip){
		$resultMTB=array();
			$query = "SELECT a.ip_man
							,b.estatus
							,a.fecha_inicio
							,a.fecha_termino
				,(case 	when fecha_inicio<='$fechaIni' and fecha_termino<='$fechaFin' then
						time_to_sec(TIMEDIFF(fecha_termino,'$fechaIni'))
						when fecha_inicio>'$fechaIni' and fecha_termino<'$fechaFin' then
						time_to_sec(TIMEDIFF(fecha_termino,fecha_inicio))
						when fecha_inicio<'$fechaIni' and fecha_termino>'$fechaFin' then
						time_to_sec(TIMEDIFF('$fechaFin','$fechaIni'))
						when fecha_inicio>'$fechaIni' and fecha_termino>'$fechaFin' then
						time_to_sec(TIMEDIFF('$fechaFin',fecha_inicio))
				end) as tiempo  FROM bgps_detalles a 
					inner join evento_bgp b 
					on a.fecha_inicio=b.fecha_consulta
					and a.ip_man=b.ip_man
					WHERE a.ip_man='$ip' and fecha_inicio<='$fechaFin' and fecha_termino>='$fechaIni' ";
		 	$resultMTB = conexion('bgpt',$base,$query);
		 	if($resultMTB[0]["evento"]=="error"){
		 		echo $resultMTB[0]["msg"];
				exit;
		 	}else{
		 		if($resultMTB[0]["numRegs"]==0){
		 			$query="select ip_man, estatus,fecha_consulta, up_time seg,time_to_sec(TIMEDIFF('$fechaFin','$fechaIni')) tiempo
		 					from estatus_bgp where ip_man='$ip'";
		 			$resultMTB = conexion('bgpt',$base,$query);
				 	if($resultMTB[0]["evento"]=="error"){
				 		echo $resultMTB[0]["msg"];
						exit;
					}
					$resultMTB[0]["detalle"]=0;
		 		}else{
		 			$resultMTB[0]["detalle"]=1;
		 		}
		 	}

        return $resultMTB;
	}
	function crearTabla($result_bgp_eventos,$rango){
		$ipsC=$result_bgp_eventos[0]['numRegs'];
		if($result_bgp_eventos[0]['detalle']==1){
			echo "<tr>";
	        echo "<th class='text-center'>IP MAN</th>";
	        echo "<th class='text-center'>STATUS</th>";
	        echo "<th class='text-center'>FECHA DESCONEXION</th>";
	        echo "<th class='text-center'>FECHA CONEXION</th>";
	        echo "<th class='text-center'>TIEMPO</th>";
	        echo "<th class='text-center'>PORCENTAJE</th>";
			echo "</tr>";
	        echo "</thead>";
			echo "<tbody>";
			for($i=1;$i<=$ipsC;$i++){
					echo "<td>".$result_bgp_eventos[$i]['ip_man']."</td>";
					echo "<td>".$result_bgp_eventos[$i]['estatus']."</td>";
					echo "<td>".$result_bgp_eventos[$i]['fecha_inicio']."</td>";
					echo "<td>".$result_bgp_eventos[$i]['fecha_termino']."</td>";
					echo "<td>".segundos($result_bgp_eventos[$i]['tiempo'])."</td>";
					echo "<td>".round((floatval($result_bgp_eventos[$i]['tiempo'])*100)/$rango,4)."</td>";
					
				echo"</tr>";
			}
		}else{
			echo "<tr>";
	        echo "<th class='text-center'>IP MAN</th>";
	        echo "<th class='text-center'>STATUS</th>";
	        echo "<th class='text-center'>FECHA DEL EVENTO</th>";
	        echo "<th class='text-center'>TIEMPO</th>";
	        echo "<th class='text-center'>PORCENTAJE</th>";
			echo "</tr>";
	        echo "</thead>";
			echo "<tbody>";
			for($i=1;$i<=$ipsC;$i++){
					echo "<td>".$result_bgp_eventos[$i]['ip_man']."</td>";
					echo "<td>".$result_bgp_eventos[$i]['estatus']."</td>";
					echo "<td>".$result_bgp_eventos[$i]['fecha_consulta']."</td>";
					echo "<td>".segundos($result_bgp_eventos[$i]['seg'])."</td>";
					echo "<td>".round((floatval($result_bgp_eventos[$i]['tiempo'])*100)/$rango,4)."</td>";
					
				echo"</tr>";
			}
		}
	}

	$mes=0;
	$c2="";
	$fechaFin="";
	$diaPart="";
	$rango=0;
	if(isset($_REQUEST["ip"]) && trim($_REQUEST["ip"])!="" && isset($_REQUEST["asr"]) && trim($_REQUEST["asr"])!=""
		&& isset($_REQUEST["fechaIni"]) && trim($_REQUEST["fechaIni"])!="" && isset($_REQUEST["fechaFin"]) && trim($_REQUEST["fechaFin"])!=""){
		$ip=$_REQUEST["ip"];
		$c2=$_REQUEST["asr"];
		$fechaIni=$_REQUEST["fechaIni"];
		$fechaFin=$_REQUEST["fechaFin"];
		$rango = (strtotime($fechaFin) - strtotime($fechaIni));
	}else{
		die();
	}

	$result_bgp_fin=array();
	$result_bgp_eventos=array();
	$shm_data=array();

	//Se obtiene los estatus actuales
	switch ($c2) {
		case 'C2 CENTRO 8k':
			$result_bgp_eventos =consultaMultipleBgptEventos("cnt_bgp8k",$fechaIni,$fechaFin,$ip);
			break;
		case 'C2 SUR 8k':
			$result_bgp_eventos =consultaMultipleBgptEventos("sur_bgp8k",$fechaIni,$fechaFin,$ip);
			break;
		case 'C2 NORTE 8k':
			$result_bgp_eventos =consultaMultipleBgptEventos("nte_bgp8k",$fechaIni,$fechaFin,$ip);
			break;
		case 'C2 ORIENTE 8k':
			$result_bgp_eventos =consultaMultipleBgptEventos("ote_bgp8k",$fechaIni,$fechaFin,$ip);
			break;
		case 'C2 PONIENTE 8k':
			$result_bgp_eventos =consultaMultipleBgptEventos("pte_bgp8k",$fechaIni,$fechaFin,$ip);
			break;
		case 'C2 CENTRO 7k':
			$result_bgp_eventos =consultaMultipleBgptEventos("cnt_bgp7k",$fechaIni,$fechaFin,$ip);
			break;
		case 'C2 SUR 7k':
			$result_bgp_eventos =consultaMultipleBgptEventos("sur_bgp7k",$fechaIni,$fechaFin,$ip);
			break;
		case 'C2 NORTE 7k':
			$result_bgp_eventos =consultaMultipleBgptEventos("nte_bgp7k",$fechaIni,$fechaFin,$ip);
			break;
		case 'C2 ORIENTE 7k':
			$result_bgp_eventos =consultaMultipleBgptEventos("ote_bgp7k",$fechaIni,$fechaFin,$ip);
			break;
		case 'C2 PONIENTE 7k':
			$result_bgp_eventos =consultaMultipleBgptEventos("pte_bgp7k",$fechaIni,$fechaFin,$ip);
			break;
	}

	//Se escribe los encabezados de la tabla 

		echo "<div class='row'>";
        echo "<div class='col-lg-12'>";
		echo "<div class='dataTable_wrapper'>";
		echo "<table class='table table-reflow table-striped table-bordered table-hover' id='dispo8k'>";
		echo "<thead>";
			
		crearTabla($result_bgp_eventos,$rango);
		echo "</tbody>";
		echo "</table>";
		echo "</div>";
		echo "</div>";
		echo "</div>";

?>

