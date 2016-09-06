<?php
include("configLogin.php");
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // username and password sent from form 

    $myusername = mysqli_real_escape_string($db, $_POST['username']);
    $mypassword = mysqli_real_escape_string($db, $_POST['password']);

    $sql = "SELECT id FROM usuarios WHERE user_name = '$myusername' and user_password = '$mypassword'";
    $result = mysqli_query($db, $sql);
    $row = mysqli_fetch_array($result, MYSQLI_ASSOC);
    $active = $row['active'];

    $count = mysqli_num_rows($result);
    // If result matched $myusername and $mypassword, table row must be 1 row
    //die;
    if ($count == 1) {
        $_SESSION['login_user'] = $myusername;

        header("location: ./?c=sample&m=show&l=list#");
    } else {
        $errMSG = "Error: Usuario o Contraseña Incorrectos";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">

        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <!-- The above 3 meta tags *must* come first in the head; any other head content must come *after* these tags -->
        <meta name="description" content="">
        <meta name="author" content="">


        <title>Sign in for MyDoctor</title>

        <!-- Bootstrap core CSS -->
        <link href="../bootstrap/css/bootstrap.min.css" rel="stylesheet">

        <!-- Custom styles for this template -->
        <link href="../bootstrap/css/signin.css" rel="stylesheet">

    </head>

    <body>

        <div class="container">

            <form class="form-signin" action="" method="post">
                <h3 class="form-signin-heading" style="color:blue">MyDoctorWeb -Registro-  </h3>
                
                <label for="username" class="sr-only">Usuario</label>
                <input type="text" id="username" class="form-control" name = "username" placeholder="Usuario" required autofocus>

                <label for="password" class="sr-only">Contraseña</label>
                <input type="password" id="password" class="form-control" name = "password" placeholder="Contraseña" required>

                <div class="checkbox">
                    <label>
                        <input type="checkbox" value="remember-me"> Remember me
                    </label>
                </div>
                <button class="btn btn-lg btn-primary btn-block" type="submit">Sign in</button> <br>

                <?php //para mostrar error de usuario o password
                if (isset($errMSG)) {
                    ?>
                    <div class="form-group">
                        <div class="alert alert-danger">
                            <span class="glyphicon glyphicon-info-sign"></span> <?php echo $errMSG; ?>
                        </div>
                    </div>
                    <?php
                }
                ?>


            </form>



        </div> <!-- /container -->


    </body>
</html>
