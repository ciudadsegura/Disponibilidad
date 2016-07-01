<?php

	include("conexion.php");
	include("func.php");

	$fecha=$_POST["fecha"];
	//$fecha="2016-04-28";
	//$proyecto=$_POST["proyecto"];

	$fecha_ini=$fecha." 00:00:00";
	$fecha_fin=$fecha." 23:59:59";

	$rango = (strtotime($fecha_fin) - strtotime($fecha_ini));

	$query="SELECT * FROM asr WHERE proyecto = '8k T'";
	$result_asr=conexion($conexiones['logs'],'vlan',$query);

	if($result_asr[0]["evento"]=="error")
	{
		echo $result_asr[0]["msg"];
	}

	$asr8K="";

	for($i=1; $i<count($result_asr);$i++){
			if($asr8K!=""){
				$asr8K.=",";
			}
			$asr8K.="'".$result_asr[$i]['ip_asr']."'";
	}
	//print_r($asr8K);

	$query="SELECT ip_man,vlan,c2_conexion from stvs_8k natural join (stvs_8k_detalles) /*WHERE ip_man ='10.203.12.130'*/";
	//echo $query."\n";
	$result = conexion($conexiones['logs'],'vlan',$query);

	if($result[0]["evento"]=="correcto")
	{
		$rows = array();
		//for($i=1; $i<3;$i++){
		for($i=1; $i<count($result);$i++){
		//echo $result[$i]['ip_man']."\n";

			$result_bgpini = NULL;
			$result_bgpfin = NULL;

			$query="select * from consulta_stv 
				WHERE ip_man= '".$result[$i]['ip_man']."'
				and fecha_consulta < '$fecha_ini'
				order by fecha_consulta desc limit 1";

				switch($result[$i]['c2_conexion'])
			    {
			    	case 'C2 CENTRO':
					$result_bgpini = conexion($conexiones['bgpt'],'cnt_bgp8k',$query);
					break;
					case 'C2 NORTE':
					$result_bgpini = conexion($conexiones['bgpt'],'nte_bgp8k',$query);
					break;
					case 'C2 ORIENTE':
					$result_bgpini = conexion($conexiones['bgpt'],'ote_bgp8k',$query);
					break;
					case 'C2 PONIENTE':
					$result_bgpini = conexion($conexiones['bgpt'],'pte_bgp8k',$query);
					break;
					case 'C2 SUR':
					$result_bgpini = conexion($conexiones['bgpt'],'sur_bgp8k',$query);
					break;
				}

			$query="select * from consulta_stv 
				WHERE ip_man= '".$result[$i]['ip_man']."'
				and fecha_consulta > '$fecha_fin'
				order by fecha_consulta asc limit 1";

				switch($result[$i]['c2_conexion'])
			    {
			    	case 'C2 CENTRO':
					$result_bgpfin = conexion($conexiones['bgpt'],'cnt_bgp8k',$query);
					break;
					case 'C2 NORTE':
					$result_bgpfin = conexion($conexiones['bgpt'],'nte_bgp8k',$query);
					break;
					case 'C2 ORIENTE':
					$result_bgpfin = conexion($conexiones['bgpt'],'ote_bgp8k',$query);
					break;
					case 'C2 PONIENTE':
					$result_bgpfin = conexion($conexiones['bgpt'],'pte_bgp8k',$query);
					break;
					case 'C2 SUR':
					$result_bgpfin = conexion($conexiones['bgpt'],'sur_bgp8k',$query);
					break;
				}

				

				$query = "SELECT *,
					(case 	when fecha_inicio<='$fecha_ini' and fecha_termino<='$fecha_fin' then
							time_to_sec(TIMEDIFF(fecha_termino,'$fecha_ini')*100)/$rango
							when fecha_inicio>'$fecha_ini' and fecha_termino<'$fecha_fin' then
							time_to_sec(TIMEDIFF(fecha_termino,fecha_inicio)*100)/$rango
							when fecha_inicio<'$fecha_ini' and fecha_termino>'$fecha_fin' then
							time_to_sec(TIMEDIFF('$fecha_fin','$fecha_ini')*100)/$rango
							when fecha_inicio>'$fecha_ini' and fecha_termino>'$fecha_fin' then
							time_to_sec(TIMEDIFF('$fecha_fin',fecha_inicio)*100)/$rango
					end) as disp FROM detalle_log WHERE ip_man= '".$result[$i]['ip_man']."' and ip_asr in ($asr8K) and fecha_inicio<='$fecha_fin' and fecha_termino>='$fecha_ini'";

				$result_eventolog = NULL;
				$result_eventolog = conexion($conexiones['logs'],'logs',$query);

				$tiempo_desc = 0.0;

				for($j=1;$j<count($result_eventolog);$j++){
					$tiempo_desc .= floatval($result_eventolog[$j]['disp']);
				}

				$tiempo_desc = 100-$tiempo_desc;

				if($tiempo_desc > 99.95){
					$conclusion = "CUMPLE NATURAL";
				}
				elseif (($tiempo_desc<=99.95) && ($tiempo_desc>99.90)) {
					$conclusion = "ULTIMA MILLA";
				}
				else{
					$conclusion = "ANALIZAR FALLA";

				}

				if((count($result_bgpfin)>1)&&(count($result_bgpini)>1))
					{
						$pribgp=uptimeSegundos($result_bgpini[1]['up_down']);
						$ultbgp=uptimeSegundos($result_bgpfin[1]['up_down']);

						if(($result_bgpfin[1]['estatus']==1) && ($pribgp>$ultbgp)){
							$conclusion = "ANALIZAR FALLA";
						}

						if(($result_bgpfin[1]['estatus']==1) && ($rango<$ultbgp)){
							$conclusion = "CUMPLE NATURAL";
						}


					}
				else{
					if(count($result_bgpfin)==1){

						$result_bgpfin[]=array('estatus'=>'-','up_down'=>'-','fecha_consulta'=>'-');
					}
					if(count($result_bgpini)==1){

						$result_bgpini[]=array('estatus'=>'-','up_down'=>'-','fecha_consulta'=>'-');
					}
				}
				

				$rows[]=array(
					'ip_man' => $result[$i]['ip_man'], 
					'vlan' => $result[$i]['vlan'], 
					'c2' => $result[$i]['c2_conexion'], 
					'desconexiones' => (count($result_eventolog)-1), 
					'conectado' => $tiempo_desc, 
					'conclusion' => $conclusion,
					'estatus_ini'=>$result_bgpini[1]['estatus'],
					'time_ini'=>$result_bgpini[1]['up_down'],
					'date_ini'=>$result_bgpini[1]['fecha_consulta'],
					'estatus_fin'=>$result_bgpfin[1]['estatus'],
					'time_fin'=>$result_bgpfin[1]['up_down'],
					'date_fin'=>$result_bgpfin[1]['fecha_consulta']);

			/*echo $query."\n";
			print_r($result_bgpini);
			print_r($result_bgpfin);
			print_r($result_eventolog);*/


		}

		//print_r($rows);
	}



	
	if(count($rows)>0){
		echo "<div class='dataTable_wrapper'>";
		echo "<table class='table table-reflow table-striped table-bordered table-hover' id='dispo8k'>";
		echo "<thead>";
			echo "<tr>";
                echo "<th rowspan='2' class='text-center'>IP MAN</th>";
                echo "<th rowspan='2' class='text-center'>VLAN</th>";
                echo "<th rowspan='2' class='text-center'>C2</th>";
                echo "<th rowspan='2' class='text-center'>No. DESCONEXIONES</th>";
                echo "<th rowspan='2' class='text-center'>% CONECTADO</th>";
                echo "<th rowspan='2' class='text-center'>CONCLUSION</th>";
                echo "<th colspan='3' class='text-center'>PRIMERA CONSULTA BGP DEL MES</th>";
                echo "<th colspan='3' class='text-center'>ULTIMA CONSULTA BGP DEL MES</th>";
            echo "</tr>";
            echo "<tr>";
                echo "<th class='text-center'>STATUS</th>";
                echo "<th class='text-center'>TIME</th>";
                echo "<th class='text-center'>DATE</th>";
                echo "<th class='text-center'>STATUS</th>";
                echo "<th class='text-center'>TIME</th>";
                echo "<th class='text-center'>DATE</th>";
            echo "</tr>";
        echo "</thead>";
		echo "<tbody>";
		for ($i=0;$i<count($rows);$i++){
			$row=$rows[$i];

			switch ($row["conclusion"]) {
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
				echo "<td>".$row["ip_man"]."</td>";
				echo "<td>".$row["vlan"]."</td>";
				echo "<td>".$row["c2"]."</td>";
				echo "<td>".$row["desconexiones"]."</td>";
				echo "<td>".$row["conectado"]."</td>";
				echo "<td>".$row["conclusion"]."</td>";
				echo "<td>".$row["estatus_ini"]."</td>";
				echo "<td>".$row["time_ini"]."</td>";
				echo "<td>".$row["date_ini"]."</td>";
				echo "<td>".$row["estatus_fin"]."</td>";
				echo "<td>".$row["time_fin"]."</td>";
				echo "<td>".$row["date_fin"]."</td>";
			echo "</tr>";
		}
		echo "</tbody>";
		echo "</table>";
		echo "</div>";
	}
?>