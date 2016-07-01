<?php
include("conexion.php");

include("func.php");


$qry="select * from  consulta_stv where fecha_consulta >= '2016-04-30' and fecha_consulta < '2016-05-01' order  by ip_man asc, fecha_consulta asc limt 10000";

$result = conexion($conexiones['bgpt'],'pte_bgp8k',$query);

for($i=1; $i<count($result);$i++){
		$fpip=fopen("".$result[$i]['ip_man'].".txt","a");
			fputs($fpip,$result[$i]['ip_man']."	".$result[$i]['spk']."	".$result[$i]['as_num']."	".$result[$i]['msg_rcvd']."	".$result[$i]['msg_sent']."	".$result[$i]['table_version']."	".$result[$i]['intq']."	".$result[$i]['outq']."	".$result[$i]['up_down']."	".$result[$i]['estatus']."	".$uptimeSegundos(result[$i]['up_down'])."	"."RP/0/RSP0/CPU0:C2_PTE_ASR_T_B_902"."	".$result[$i]['fecha_consulta']."	"."10.199.1.248\n");
		fclose($fpip);
	}
?>