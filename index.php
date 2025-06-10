<?php
// Initialize the session
session_start();

// Check if the user is already logged in, if yes then redirect to tasks page
if(isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true){
    header("location: tasks.php");
    exit;
}

// Include database configuration
require_once "config/database.php";

// Define variables and initialize with empty values
$name = $email = $password = $password_confirmation = "";
$name_err = $email_err = $password_err = $password_confirmation_err = $login_err = "";

// Processing form data when form is submitted
if($_SERVER["REQUEST_METHOD"] == "POST"){
    if(isset($_POST["action"]) && $_POST["action"] == "login"){
        // Login processing
        if(empty(trim($_POST["email"]))) {
            $email_err = "Veuillez entrer votre email.";
        } else {
            $email = trim($_POST["email"]);
        }
        if(empty(trim($_POST["password"]))) {
            $password_err = "Veuillez entrer votre mot de passe.";
        } else {
            $password = trim($_POST["password"]);
        }
        if(empty($email_err) && empty($password_err)) {
            $sql = "SELECT id, name, email, password FROM users WHERE email = ?";
            if($stmt = mysqli_prepare($conn, $sql)){
                mysqli_stmt_bind_param($stmt, "s", $param_email);
                $param_email = $email;
                if(mysqli_stmt_execute($stmt)){
                    mysqli_stmt_store_result($stmt);
                    if(mysqli_stmt_num_rows($stmt) == 1){
                        mysqli_stmt_bind_result($stmt, $id, $name, $email, $hashed_password);
                        if(mysqli_stmt_fetch($stmt)){
                            if(password_verify($password, $hashed_password)){
                                session_start();
                                $_SESSION["loggedin"] = true;
                                $_SESSION["id"] = $id;
                                $_SESSION["name"] = $name;
                                $_SESSION["email"] = $email;
                                header("location: tasks.php");
                                exit;
                            } else {
                                $login_err = "Email ou mot de passe invalide.";
                            }
                        }
                    } else {
                        $login_err = "Email ou mot de passe invalide.";
                    }
                }
                mysqli_stmt_close($stmt);
            }
        }
    } elseif(isset($_POST["action"]) && $_POST["action"] == "register") {
        // Register processing
        if(empty(trim($_POST["name"]))) {
            $name_err = "Veuillez entrer un nom d'utilisateur.";
        } else {
            $name = trim($_POST["name"]);
        }
        if(empty(trim($_POST["email"]))) {
            $email_err = "Veuillez entrer un email.";
        } elseif(!filter_var(trim($_POST["email"]), FILTER_VALIDATE_EMAIL)) {
            $email_err = "Format d'email invalide.";
        } else {
            $email = trim($_POST["email"]);
            $sql = "SELECT id FROM users WHERE email = ?";
            if($stmt = mysqli_prepare($conn, $sql)){
                mysqli_stmt_bind_param($stmt, "s", $param_email);
                $param_email = $email;
                if(mysqli_stmt_execute($stmt)){
                    mysqli_stmt_store_result($stmt);
                    if(mysqli_stmt_num_rows($stmt) == 1){
                        $email_err = "Cet email est déjà utilisé.";
                    }
                }
                mysqli_stmt_close($stmt);
            }
        }
        if(empty(trim($_POST["password"]))) {
            $password_err = "Veuillez entrer un mot de passe.";
        } elseif(strlen(trim($_POST["password"])) < 6) {
            $password_err = "Le mot de passe doit contenir au moins 6 caractères.";
        } else {
            $password = trim($_POST["password"]);
        }
        if(empty(trim($_POST["password_confirmation"]))) {
            $password_confirmation_err = "Veuillez confirmer le mot de passe.";
        } elseif(trim($_POST["password"]) !== trim($_POST["password_confirmation"])) {
            $password_confirmation_err = "Les mots de passe ne correspondent pas.";
        } else {
            $password_confirmation = trim($_POST["password_confirmation"]);
        }
        if(empty($name_err) && empty($email_err) && empty($password_err) && empty($password_confirmation_err)) {
            $sql = "INSERT INTO users (name, email, password) VALUES (?, ?, ?)";
            if($stmt = mysqli_prepare($conn, $sql)){
                mysqli_stmt_bind_param($stmt, "sss", $param_name, $param_email, $param_password);
                $param_name = $name;
                $param_email = $email;
                $param_password = password_hash($password, PASSWORD_DEFAULT);
                if(mysqli_stmt_execute($stmt)){
                    $login_err = "Inscription réussie ! Connectez-vous.";
                }
                mysqli_stmt_close($stmt);
            }
        }
    }
    mysqli_close($conn);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="css/style.css">
    <title>connection Page</title>
</head>
<body>
    <div class="container">
        <div class="form-box login">
            <form method="POST" action="">
                <input type="hidden" name="action" value="login">
                <h1>Login</h1>
                <div class="input-box">
                    <input type="email" name="email" placeholder="Email" value="<?php echo htmlspecialchars($email); ?>" required>
                    <i class='bx bxs-user'></i>
                </div>
                <div class="input-box">
                    <input type="password" name="password" placeholder="Mot de passe" required>
                    <i class='bx bxs-lock-alt' ></i>
                </div>
                <div class="forget-link">
                    <a href="#">Mot de passe oublié ?</a>
                </div>
                <button type="submit" class="btn">Login</button>
                <?php if(!empty($login_err) || !empty($email_err) || !empty($password_err)) { ?>
                    <div style="color:red;">
                        <ul>
                            <?php if(!empty($login_err)) echo '<li>' . $login_err . '</li>'; ?>
                            <?php if(!empty($email_err)) echo '<li>' . $email_err . '</li>'; ?>
                            <?php if(!empty($password_err)) echo '<li>' . $password_err . '</li>'; ?>
                        </ul>
                    </div>
                <?php } ?>
                <p>Ou connectez-vous avec</p>
                <div class="social-icons">
                    <a href="#"><i class='bx bxl-google' ></i></a>
                    <a href="#"><i class='bx bxl-facebook-circle' ></i></a>
                    <a href="#"><i class='bx bxl-github' ></i></a>
                    <a href="#"><i class='bx bxl-linkedin' ></i></a>
                </div>
            </form>
        </div>
        <div class="form-box register">
            <form method="POST" action="">
                <input type="hidden" name="action" value="register">
                <h1>Inscription</h1>
                <div class="input-box">
                    <input type="text" name="name" placeholder="Nom d'utilisateur" value="<?php echo htmlspecialchars($name); ?>" required>
                    <i class='bx bxs-user'></i>
                </div>
                <div class="input-box">
                    <input type="email" name="email" placeholder="Email" value="<?php echo htmlspecialchars($email); ?>" required>
                    <i class='bx bxs-envelope'></i>
                </div>
                <div class="input-box">
                    <input type="password" name="password" placeholder="Mot de passe" required>
                    <i class='bx bxs-lock-alt' ></i>
                </div>
                <div class="input-box">
                    <input type="password" name="password_confirmation" placeholder="Confirmer le mot de passe" required>
                    <i class='bx bxs-lock-alt' ></i>
                </div>
                <button type="submit" class="btn">Register</button>
                <?php if(!empty($name_err) || !empty($email_err) || !empty($password_err) || !empty($password_confirmation_err)) { ?>
                    <div style="color:red;">
                        <ul>
                            <?php if(!empty($name_err)) echo '<li>' . $name_err . '</li>'; ?>
                            <?php if(!empty($email_err)) echo '<li>' . $email_err . '</li>'; ?>
                            <?php if(!empty($password_err)) echo '<li>' . $password_err . '</li>'; ?>
                            <?php if(!empty($password_confirmation_err)) echo '<li>' . $password_confirmation_err . '</li>'; ?>
                        </ul>
                    </div>
                <?php } ?>
                <p>Ou inscrivez-vous avec</p>
                <div class="social-icons">
                    <a href="#"><i class='bx bxl-google' ></i></a>
                    <a href="#"><i class='bx bxl-facebook-circle' ></i></a>
                    <a href="#"><i class='bx bxl-github' ></i></a>
                    <a href="#"><i class='bx bxl-linkedin' ></i></a>
                </div>
            </form>
        </div>
        <div class="toggle-box">
            <div class="toggle-panel toggle-left">
                <h1>Hello, Welcome!</h1>
                <p>Don't have an account?</p>
                <button class="btn register-btn">Register</button>
            </div>
             <div class="toggle-panel toggle-right">
                <h1>Welcome Back!</h1>
                <p>Already have an account?</p>
                <button class="btn login-btn">Login</button>
            </div>
        </div>
    </div>
    <script src="js/script.js"></script>
</body>
</html>