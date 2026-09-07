<?php
// 'use LDAP\Result;' has been removed

if ($_SERVER['REQUEST_METHOD'] == "POST") {		
    $u_name = $_POST['username'] ?? '';
    $u_pass = $_POST['password'] ?? '';
    $request_type = $_POST['request_type'] ?? '';

    if ($request_type == "login") {
        if (login($dbcon, $u_name, $u_pass)) {
            header('location: ../shop');
            exit;
        } else {
            $login_error = "Invalid username or password.";
        }
    }
    
    if ($request_type == "signup") {
        $signup_result = signup($dbcon, $u_name, $u_pass);

        if ($signup_result === true) {
            login($dbcon, $u_name, $u_pass);
            header("location: ../shop");
            exit;
        } else {
            $signup_err = $signup_result;
        }
    }
}

function login($dbcon, $user, $password) {
    $statement = $dbcon->prepare("SELECT * FROM users WHERE user_name = ?");
    if (!$statement) return false; // Prevent crash if SQL prepare fails
    
    $statement->bind_param("s", $user);
    $statement->execute();
    $result = $statement->get_result();

    if ($row = $result->fetch_assoc()) {
        if (password_verify($password, $row['user_password'])) {
            $_SESSION['logged_in'] = true;
            $_SESSION['user_name'] = $row['user_name'];
            $_SESSION['user_id'] = $row['id'] ?? null; 
            
            $statement->close();
            return true;
        }
    } 
    $statement->close();
    return false;
}

function signup($dbcon, $user, $password) {
    $check_st = $dbcon->prepare("SELECT user_name FROM users WHERE user_name = ?");
    
    if (!$check_st) {
        return "SQL Error: " . $dbcon->error; 
    }

    $check_st->bind_param("s", $user);
    $check_st->execute();
    $result = $check_st->get_result();

    if ($result->num_rows > 0){
        $check_st->close();
        return "Username already exists!";
    }
    $check_st->close();

    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    $insert_st = $dbcon->prepare('INSERT INTO users (user_name, user_password) VALUES (? , ?)');
    
    // Catch SQL errors on the INSERT query
    if (!$insert_st) {
        return "SQL Error: " . $dbcon->error;
    }

    $insert_st->bind_param('ss', $user, $hashed_password);

    if ($insert_st->execute()) {
        $insert_st->close();
        return true; 
    } else {
        $insert_st->close();
        return "Database error during registration: " . $dbcon->error;
    }
}
?>