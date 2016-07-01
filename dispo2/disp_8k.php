<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="">
    <meta name="author" content="">

    <title>Disponibilidad</title>

    <!-- Bootstrap Core CSS -->
    <link href="bower_components/bootstrap/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- MetisMenu CSS -->
    <link href="bower_components/metisMenu/dist/metisMenu.min.css" rel="stylesheet">

    <!-- DataTables CSS -->
    <link href="bower_components/datatables-plugins/integration/bootstrap/3/dataTables.bootstrap.css" rel="stylesheet">

    <!-- DataTables Responsive CSS -->
    <link href="bower_components/datatables-responsive/css/dataTables.responsive.css" rel="stylesheet">

    <!-- Custom CSS -->
    <link href="dist/css/sb-admin-2.css" rel="stylesheet">

    <!-- Custom Fonts -->
    <link href="bower_components/font-awesome/css/font-awesome.min.css" rel="stylesheet" type="text/css">

    <!-- Datepicker -->
    <link href="css/bootstrap-datepicker3.min.css" rel="stylesheet" type="text/css">

    <!-- HTML5 Shim and Respond.js IE8 support of HTML5 elements and media queries -->
    <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
    <!--[if lt IE 9]>
        <script src="https://oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>
        <script src="https://oss.maxcdn.com/libs/respond.js/1.4.2/respond.min.js"></script>
    <![endif]-->
</head>

<body>
    <div id="wrapper">
        <?php
            include"menu.php";
        ?>
        <div id="page-wrapper">
            <div class="row">
                <div class="col-lg-12">
                    <h1 class="page-header">Disponibilidad 8K</h1>
                </div>
                <!-- /.col-lg-12 -->
            </div>
            <div class="row">
                <div class="col-lg-12">
                    <div class="panel panel-default">
                        <div class="panel-heading">
                            Fecha a Consultar
                        </div>
                        <div class="panel-body">
                            <div class="row">
                                <div class="col-lg-6">
                                    <form role="form" novalidate>
                                        <div class="control-group">
                                            <div id="date1">
                                                <div class="input-group date">
                                                    <input type="text" class="form-control" required data-validation-required-message="Selecciona una fecha"></input>
                                                    <span class="input-group-addon"><i class="glyphicon glyphicon-th"></i></span>
                                                </div>
                                                 <p class="help-block"></p>
                                            </div>
                                        </div>
                                        <button id="loaddata" type="submit" class="btn btn-default">Consultar</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-lg-12">
                    <div class="panel panel-default">
                        <div class="panel-heading">
                            Resultados Disponibilidad
                        </div>
                        <!-- /.panel-heading -->
                        <div class="panel-body" id="tbcontent">                        
                            
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- /#page-wrapper -->

    </div>
    <!-- /#wrapper -->

<!-- jQuery -->
    <script src="bower_components/jquery/dist/jquery.min.js"></script>

    <!-- Bootstrap Core JavaScript -->
    <script src="bower_components/bootstrap/dist/js/bootstrap.min.js"></script>

    <!-- Metis Menu Plugin JavaScript -->
    <script src="bower_components/metisMenu/dist/metisMenu.min.js"></script>

    <!-- DataTables JavaScript -->
    <script src="bower_components/datatables/media/js/jquery.dataTables.min.js"></script>
    <script src="bower_components/datatables-plugins/integration/bootstrap/3/dataTables.bootstrap.min.js"></script>

    <!-- Custom Theme JavaScript -->
    <script src="dist/js/sb-admin-2.js"></script>

    <!-- Datepicker -->
    <script src="js/bootstrap-datepicker.min.js"></script>
    <script src="locales/bootstrap-datepicker.es.min.js"></script>

    <!--Validate-->
    <script src="js/jqBootstrapValidation.js"></script>

    <!-- Page-Level Demo Scripts - Tables - Use for reference -->
    <script>
    $(document).ready(function() {
        $('#date1 .input-group.date').datepicker({
            format:"yyyy-mm-dd",
            language:"es",
            weekStart:7,
            autoclose: true
        });

        $("input").jqBootstrapValidation(
            {
                preventSubmit: true,
                submitError: function($form, event, errors) {
                    // Here I do nothing, but you could do something like display 
                    // the error messages to the user, log, etc.
                },
                submitSuccess: function($form, event) {

                    /*alert("OK");*/
                    event.preventDefault();

                    $("#loaddata").attr("disabled",true);
                    $("#tbcontent").html("<div class='col-lg-4'></div><div class='col-lg-4'><img src='images/loading2.gif'></div><div class='col-lg-4'></div>");
                    $.ajax({
                        url:'stores/datos8k_prueb.php',
                        type: 'POST',
                        data:{fecha:$('#date1 .input-group.date input').val(),proyecto:'8k'},
                        dataType:'HTML',
                        success: function(data){
                            $("#tbcontent").html(data);
                            $("#loaddata").attr("disabled",false);
                            $('#dispo8k').DataTable({
                                responsive: true
                            });
                        }
                    });
                },
                filter: function() {
                    return $(this).is(":visible");
                }
            }
        );
    });
    </script>
</body>

</html>