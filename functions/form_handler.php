<?php
if ($_SERVER['REQUEST_METHOD'] == "POST") {
    $request_type = $_POST['request_type'] ?? '';

    //login
    $u_input = $_POST['user_input'] ?? '';
    $u_pass = $_POST['password'] ?? ''; //also in register

    //register
    $u_name = $_POST['username'] ?? '';
    $u_email = $_POST['email'] ??'';
    $u_vpass = $_POST['valid_password'] ?? '';

    if ($request_type == "login") {
        if (login($dbcon, $u_input, $u_pass)) {
            header('location: ../auth-success?type=login');
            exit;
        } else {
            $login_error = "Invalid username or password.";
        }
    }
    
    if ($request_type == "signup") {
        $signup_result = signup($dbcon, $u_name, $u_email, $u_pass, $u_vpass);

        if ($signup_result === true) {
            login($dbcon, $u_name, $u_pass);
            header("location: ../auth-success?type=signup");
            exit;
        } else {
            $signup_err = $signup_result;
        }
    }
}

function check_login_mode(){

}

//asks can both ask for username and user email
function login($dbcon, $user_input, $password) {

    if (filter_var($user_input, FILTER_VALIDATE_EMAIL)) {
        // Search by email
        $sql = "SELECT * FROM users WHERE user_email = ?";
    } else {
        // Search by username
        $sql = "SELECT * FROM users WHERE user_name = ?";
    }

    $statement = $dbcon->prepare($sql);
    if (!$statement) return false; 
    
    $statement->bind_param("s", $user_input);
    $statement->execute();
    $result = $statement->get_result();

    if ($row = $result->fetch_assoc()) {
        if (password_verify($password, $row['user_password'])) {
            $_SESSION['logged_in'] = true;
            $_SESSION['user_name'] = $row['user_name'];
            $_SESSION['user_id'] = $row['user_id'] ?? null; 
            
            $statement->close();
            return true;
        }
    } 
    $statement->close();
    return false;
}

function signup($dbcon, $username, $email, $password, $confirm_password) {
    $check_st = $dbcon->prepare("SELECT user_email FROM users WHERE user_email = ?");
    
    if (!$check_st) {
        return "SQL Error: " . $dbcon->error; 
    }

    $check_st->bind_param("s", $email);
    $check_st->execute();
    $result = $check_st->get_result();

    if ($result->num_rows > 0){
        $check_st->close();
        return "An account using this email already exsists.";
    }

    if ($password != $confirm_password) {
        $check_st->close();
        return "Passwords do not match";
    }

    $check_st->close();

    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    $insert_st = $dbcon->prepare('INSERT INTO users (user_name, user_email, user_password) VALUES (? , ?, ?)');
    
    // Catch SQL errors on the INSERT query
    if (!$insert_st) {
        return "SQL Error: " . $dbcon->error;
    }

    $insert_st->bind_param('sss', $username, $email, $hashed_password);

    if ($insert_st->execute()) {
        $insert_st->close();
        return true; 
    } else {
        $insert_st->close();
        return "Database error during registration: " . $dbcon->error;
    }
}
?>