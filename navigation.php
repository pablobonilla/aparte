<!-- Navigation -->
<nav class="navbar navbar-default navbar-static-top" role="navigation" style="margin-bottom: 0">
    <div class="navbar-header">
                <button type="button" class="navbar-toggle" data-toggle="collapse" data-target=".navbar-collapse">
                    <span class="sr-only">Toggle navigation</span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                </button>
        <a class="navbar-brand navbar-center" href=" ../?c=sample&m=show&l=list">MyDoctorWeb v1.0</a>
    </div>
    <!-- /.navbar-header -->

    <ul class="nav navbar-top-links navbar-right">
        <!--        <li class="dropdown">
                    
                    </ul>
                     /.dropdown-messages 
                </li>-->
        <!-- /.dropdown -->
        <!-- /.dropdown-tasks -->

        <!-- /.dropdown -->

        <!-- /.dropdown -->
        <li class="dropdown">
            <a class="dropdown-toggle" data-toggle="dropdown" href="#">
                <i class="fa fa-user fa-fw"></i>  <i class="fa fa-caret-down"></i>
            </a>
            <ul class="dropdown-menu dropdown-user">
                <li><a href="#"><i class="fa fa-user fa-fw"></i> User Profile</a>
                </li>
                <li><a href="#"><i class="fa fa-gear fa-fw"></i> Settings</a>
                </li>
                <li class="divider"></li>
                <li><a href="logout.php"><i class="fa fa-sign-out fa-fw"></i> Logout</a>
                </li>
            </ul>
            <!-- /.dropdown-user -->
        </li>
        <!-- /.dropdown -->
    </ul>
    <!-- /.navbar-top-links -->

    <div class="navbar-default sidebar" role="navigation">
        <div class="sidebar-nav navbar-collapse">
            <ul class="nav" id="side-menu">
                <li class="sidebar-search">
                    <div class="input-group custom-search-form">
                        <input type="text" class="form-control" placeholder="Search...">
                        <span class="input-group-btn">
                            <button class="btn btn-default" type="button">
                                <i class="fa fa-search"></i>
                            </button>
                        </span>
                    </div>
                    <!-- /input-group -->
                </li>
                <li>
                    <a href="./?c=sample&m=show&l=list#"><i class="fa fa-medkit"></i> Portada</a>
                </li>
                <li>
                    <a href="./?c=patient&m=show&l=list"><i class="fa fa-user-md"></i> Pacientes</a>
                </li>

                <li>
                    <a href="./?c=patient&m=show&l=list"><i class="fa fa-list"></i> Consultas</a>
                </li>


                <li>
                    <a href="#"><i class="fa fa-usd fa-fw"></i> Contabilidad<span class="fa arrow"></span></a>
                    <ul class="nav nav-second-level">
                        <li>
                            <a href="./?c=cierre&m=show&l=list">Cierre del Día</a>
                        </li>
                        <li>
                            <a href="./?c=pagos&m=show&l=list">Pagos</a>
                        </li>
                        <li>
                            <a href="morris.html">Divendendos por Fecha</a>
                        </li>


                    </ul>
                    <!-- /.nav-second-level -->
                </li>
                <!--                <li>
                                    <a href="tables.html"><i class="fa fa-table fa-fw"></i> Tables</a>
                                </li>
                                <li>
                                    <a href="forms.html"><i class="fa fa-edit fa-fw"></i> Forms</a>
                                </li>-->
                <li>
                    <a href="#"><i class="fa fa-file-text fa-fw"></i> Reportes<span class="fa arrow"></span></a>
                    <ul class="nav nav-second-level">
                        <li>
                            <a href="http://www.mydoctor.local/?c=patient&m=show&l=report#">Listado General de Pacientes</a>
                        </li>
                        <li>
                            <a href="#">Consultas por Fecha</a>
                        </li>
                        <li>
                            <a href="./?c=reporte1&m=show&l=list">Consultas por Motivo</a>
                        </li>

                    </ul>
                    <!-- /.nav-second-level -->
                </li>
                <li>
                    <a href="#"><i class="fa fa-calendar fa-fw"></i> Citas<span class="fa arrow"></span></a>
                    <ul class="nav nav-second-level">
                        <li>
                            <a href="./?c=citas&m=cale&l=calendario#">Visualizar</a>
                            
                        </li>
                        <li>
                            <a href="./?c=citas&m=show&l=list">Agregar / Modificar / Borrar</a>
                        </li>
                        <!--                        <li>
                                                    <a href="#">Third Level <span class="fa arrow"></span></a>
                                                    <ul class="nav nav-third-level">
                                                        <li>
                                                            <a href="#">Third Level Item</a>
                                                        </li>
                                                        <li>
                                                            <a href="#">Third Level Item</a>
                                                        </li>
                                                        <li>
                                                            <a href="#">Third Level Item</a>
                                                        </li>
                                                        <li>
                                                            <a href="#">Third Level Item</a>
                                                        </li>
                                                    </ul>
                                                     /.nav-third-level 
                                                </li>-->
                    </ul>
                    <!-- /.nav-second-level -->
                </li>
                <li>
                    <a href="#"><i class="fa fa-files-o fa-fw"></i> Paginas Relacionadas<span class="fa arrow"></span></a>
                    <ul class="nav nav-second-level">
                        <li>
                            <a href="http://www.heartfailurerisk.org/" target="_blank">Heart Failure Risk Calculator</a>
                        </li>

                        <li>
                            <a href="http://www.mdcalc.com/timi-risk-score-for-uanstemi/" target="_blank">TIMI Risk Score for UA/NSTEMI</a>
                        </li>

                        <li>
                            <a href="login.html" >Login Page</a>
                        </li>
                    </ul>
                    <!-- /.nav-second-level -->
                </li>
                
                <li>
                    <a href="logout.php#"><i class="fa fa-home fa-fw"></i> SALIR<span class=""></span></a>
                    
                </li>
                
            </ul>
        </div>
        <!-- /.sidebar-collapse -->
    </div>
    <!-- /.navbar-static-side -->
</nav>