<?php
	function dispoBasica($baseDatosBas){

		$query="SELECT * from basica";

		$result_asr=conexion('bgpr',"logs_".$baseDatosBas,$query);

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
		if(file_exists("Disponibilidad_bas.sql"))
		{
			unlink("Disponibilidad_bas.sql");
		}
		$fp=fopen("Disponibilidad_bas.sql","a");
		foreach ($result_asr as $key => $value) {
			$rango=0;
			if($key==0){
				continue;
			}
			if($value['evento']==''){
				if(($ip!=$value['ip_man']) && $value['tipo']=='Up'){
					//$fecha_t=$fecha_i;
					$value['fecha_termino']=$value['fecha_inicio'];
					$value['fecha_inicio']='';
					$value['num_ter']=$value['num_ini'];
					$value['num_ini']='';
					$value['id_ter']=$value['id_ini'];
					$value['id_ini']='';
					$value['evento']='INICIO DESCONECTADO';

				}else{
					if($value['tipo']=='Up'){
						$value['fecha_termino']=$value['fecha_inicio'];
						$value['fecha_inicio']='';
						$value['num_ter']=$value['num_ini'];
						$value['num_ini']='';
						$value['id_ter']=$value['id_ini'];
						$value['id_ini']='';
					}
					$value['evento']='EVENTO INCOMPLETO';				
				}
			}

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
				$rango = strtotime($value['fecha_termino']) - strtotime($value['fecha_inicio']);
			}
			
			//if($value['evento']!='EVENTO INCOMPLETO'){
				$lineaInsert="\t$value[ip_man]\t$value[num_ini]\t$value[fecha_inicio]\t$value[num_ter]\t$value[fecha_termino]\t$value[evento]\t$rango\t$value[id_ini]\t$value[id_ter]\t$value[ip_asr]\t$value[repisa]\t$value[cuenta_ips]\t$value[ip_man_respaldo]\n";
				fputs($fp,$lineaInsert);
			//}
			$ip=$value['ip_man'];	
		}
		fclose($fp);
		
		$query="TRUNCATE TABLE disponibilidad";
		$result =conexion("bgpr","logs_".$baseDatosBas,$query);
		if($result[0]['evento']=='error'){
			echo($result[0]['msg']);
		}
		$query="LOAD DATA LOCAL INFILE '/var/www/html/logsys2/Disponibilidad_bas.sql' INTO TABLE disponibilidad";
		$result =conexion("bgpr","logs_".$baseDatosBas,$query);
		if($result[0]['evento']=='error'){
			echo($result[0]['msg']);
		}
	}
?>

