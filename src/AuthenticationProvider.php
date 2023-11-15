<?php

namespace src;

use Exception;
use JetBrains\PhpStorm\NoReturn;

/**
 * Class AuthenticationProvider
 * @package src
 * This class handles all authentication related tasks, like login, token validation, user creation, user deletion, password update, user data update and user data read.
 * CRUD Users
 */
class AuthenticationProvider
{
    # DO NOT CHANGE THE SALT AFTER INSTALLATION, Login will fail for existing users.
    private static string $salt = "238947";

    /**
     * @throws Exception
     */
    #[NoReturn] public function login($username, $password): void
    {
        # Get database connection
        $database_connection = DatabaseSingleton::getInstance();
        # salt the pw and hash it
        $salted_pw = $password . self::$salt;
        $hashed_pw = hash("sha256", $salted_pw);
        # Check if user exists with correct hashed pw (check password)
        $result = $database_connection->perform_query("SELECT COUNT(user_id) as usercount, user_id FROM t4_users WHERE user_email = ? AND user_pwhash = ?", [$username, $hashed_pw]);
        $userstatus = $result->fetch_assoc();
        if ($userstatus["usercount"] == 0) {
            # If not, return 401
            $res = new Response("401", ["message" => "Login failed, please try again with correct credentials"]);
            ResponseController::respondJson($res);
        }
        # Insert token and fetch it (if user logged in successfully)
        $token_generated = $database_connection->perform_query("INSERT INTO t4_sessions VALUES (default, default, ?, default, current_timestamp() + INTERVAL 1 DAY)", [$userstatus["user_id"]]);
        if (!$token_generated) {
            $res = new Response("500", ["message" => "Error while creating authentication token"]);
            ResponseController::respondJson($res);
        }
        # Return 200 with token, fetch the token from db (latest token by the user, always the right token)
        $token_fetch_id = $database_connection->get_last_inserted_id();
        $token_fetch = $database_connection->perform_query("SELECT session_token FROM t4_sessions WHERE session_id = ?", [$token_fetch_id]);
        $res = new Response("200", ["message" => "authorized", "user_id" => $userstatus["user_id"], "token" => $token_fetch->fetch_assoc()["session_token"]]);
        ResponseController::respondJson($res);
    }

    /**
     * @throws Exception
     * Checks if a token is valid, for every request. Also provide the answer directly.
     */
    public static function validatetoken($token, $needs_response = false): void
    {
        $database_connection = DatabaseSingleton::getInstance();
        # Check if token exists
        $db_fetch = $database_connection->perform_query("SELECT COUNT(session_id) as sessioncount, session_expires as sessionexpiry FROM t4_sessions WHERE session_token = ?", [$token]);
        $data = $db_fetch->fetch_assoc();
        $sessioncount = $data["sessioncount"];
        $session_expire = $data["sessionexpiry"];
        if ($sessioncount > 0) {
            # check if token is expired
            if ($session_expire < date("Y-m-d H:i:s")) {
                # if expired return false
                $res = new Response("401", ["message" => "Token expired"]);
                ResponseController::respondJson($res);
            }
            # if not expired check if a response is needed
            if ($needs_response) {
                # if response is needed return true
                $user_id = AuthenticationProvider::getUserIdByToken($token);
                $res = new Response("200", ["message" => "Token is valid", "id" => $user_id]);
                ResponseController::respondJson($res);
            }
        } else {
            # if not exists
            $res = new Response("401", ["message" => "Invalid token"]);
            ResponseController::respondJson($res);
        }
    }

    /**
     * @param $email
     * @param $password
     * @param $firstname
     * @param $lastname
     * @return void
     * Creates a new user with the given parameters
     * @throws Exception
     */
    public function createUser($email, $password, $firstname, $lastname)
    {
        $database_connection = DatabaseSingleton::getInstance();
        $salted_pw = $password . self::$salt;
        $hashed_pw = hash("sha256", $salted_pw);
        # check if e-mail is already in use
        $usercount_email = $database_connection->perform_query("SELECT COUNT(user_id) as usercount FROM t4_users WHERE user_email = ?", [$email])->fetch_assoc()["usercount"];
        if ($usercount_email > 0) {
            $res = new Response("400", ["message" => "E-Mail already in use"]);
            ResponseController::respondJson($res);
        }
        # insert user
        $user_insert = $database_connection->perform_query("INSERT INTO t4_users VALUES (default, ?, ?, default, CURRENT_TIMESTAMP, ?, ?)", [$email, $hashed_pw, $firstname, $lastname]);
        if (!$user_insert) {
            $res = new Response("500", ["message" => "Error while creating user"]);
        } else {
            $res = new Response("200", ["message" => "User created successfully", "user_id" => $database_connection->get_last_inserted_id()]);
        }
        ResponseController::respondJson($res);
    }

    /**
     * Create deleteuser function
     * @throws Exception
     */
    #[NoReturn] public function deleteUser($token, $user_id_provided): void {
        # check if token is valid
        AuthenticationProvider::validatetoken($token);
        $db_connection = DatabaseSingleton::getInstance();
        # get user id from token
        $user_id = $db_connection->perform_query("SELECT session_user FROM t4_sessions WHERE session_token = ?", [$token])->fetch_assoc()["session_user"];
        // check if user id provided in parameter matches the one from database
        if ($user_id_provided != $user_id) {
            $res = new Response("400", ["message" => "User id does not match"]);
            ResponseController::respondJson($res);
        }
        # Delete all session tokens of the user
        $delete_sessions = $db_connection->perform_query("DELETE FROM t4_sessions WHERE session_user = ?", [$user_id]);
        $delete_deltas = $db_connection->perform_query("DELETE FROM t4_deltas WHERE delta_owner = ?", [$user_id]);
        # delete shares
        $delete_shares = $db_connection->perform_query("DELETE FROM t4_shared WHERE user_id = ?", [$user_id]);
        $delete_documents = $db_connection->perform_query("DELETE FROM t4_documents WHERE document_owner = ?", [$user_id]);
        if (!$delete_sessions) {
            $res = new Response("500", ["message" => "Error while deleting user sessions"]);
            ResponseController::respondJson($res);
        }
        # delete user
        $delete_user = $db_connection->perform_query("DELETE FROM t4_users WHERE user_id = ?", [$user_id]);
        if (!$delete_user) {
            $res = new Response("500", ["message" => "Error while deleting user"]);
        } else {
            $res = new Response("200", ["message" => "User deleted successfully"]);
        }
        ResponseController::respondJson($res);
    }

    /**
     * Update a user with the given parameters
     * @throws Exception
     */
    public function updatePassword($token, $user_id_provided, $oldpassword, $newpassword): void
    {
        # check if the token is valid
        AuthenticationProvider::validatetoken($token);
        # get database connection
        $database_connection = DatabaseSingleton::getInstance();
        # get user id from token
        $user_id = $database_connection->perform_query("SELECT session_user FROM t4_sessions WHERE session_token = ?", [$token])->fetch_assoc()["session_user"];
        // check if user id provided in parameter matches the one from database
        if ($user_id_provided != $user_id) {
            $res = new Response("400", ["message" => "User id does not match"]);
            ResponseController::respondJson($res);
        }
        # get old password-hash
        $old_salted = $oldpassword . self::$salt;
        $oldpassword_hashed = hash("sha256", $old_salted);
        # get password hash from db
        $oldpassword_hashed_db = $database_connection->perform_query("SELECT user_pwhash FROM t4_users WHERE user_id = ?", [$user_id])->fetch_assoc()["user_pwhash"];
        # check if old password is correct
        if ($oldpassword_hashed != $oldpassword_hashed_db) {
            $res = new Response("400", ["message" => "Old password is incorrect"]);
            ResponseController::respondJson($res);
        }
        # salt and hash new password
        $newpassword_hashed = hash("sha256", $newpassword . self::$salt);
        # update password
        $update_password = $database_connection->perform_query("UPDATE t4_users SET user_pwhash = ? WHERE user_id = ?", [$newpassword_hashed, $user_id]);
        if (!$update_password) {
            $res = new Response("500", ["message" => "Error while updating password"]);
        } else {
            $res = new Response("200", ["message" => "Password updated successfully"]);
        }
        ResponseController::respondJson($res);
    }

    /**
     * @throws Exception
     */
    #[NoReturn] public function updateUserdata($token, $user_id_provided, $firstname, $lastname, $email): void
    {
        # check if token is valid
        AuthenticationProvider::validatetoken($token);
        $db_connection = DatabaseSingleton::getInstance();
        # get user id from token
        $user_id = $db_connection->perform_query("SELECT session_user FROM t4_sessions WHERE session_token = ?", [$token])->fetch_assoc()["session_user"];
        // check if user id provided in parameter matches the one from database
        if ($user_id_provided != $user_id) {
            $res = new Response("400", ["message" => "User id does not match"]);
            ResponseController::respondJson($res);
        }
        # check if email is already in use
        $emailcount = $db_connection->perform_query("SELECT COUNT(user_id) as usercount FROM t4_users WHERE user_email = ? AND NOT user_id = ?", [$email, $user_id])->fetch_assoc()["usercount"];
        if ($emailcount > 0) {
            $res = new Response("400", ["message" => "E-Mail already in use"]);
            ResponseController::respondJson($res);
        }
        # update user data
        $update_user = $db_connection->perform_query("UPDATE t4_users SET user_firstname = ?, user_lastname = ?, user_email = ? WHERE user_id = ?", [$firstname, $lastname, $email, $user_id]);
        if (!$update_user) {
            $res = new Response("500", ["message" => "Error while updating user data"]);
        } else {
            $res = new Response("200", ["message" => "User data updated successfully"]);
        }
        ResponseController::respondJson($res);
    }

    /**
     * @throws Exception
     */
    #[NoReturn] public function readUserdata($token, $user_id_provided): void
    {
        # check if token is valid
        AuthenticationProvider::validatetoken($token);
        $db_connection = DatabaseSingleton::getInstance();
        # get user id from token
        $user_id = $db_connection->perform_query("SELECT session_user FROM t4_sessions WHERE session_token = ?", [$token])->fetch_assoc()["session_user"];
        // check if user id provided in parameter matches the one from database
        if ($user_id_provided != $user_id) {
            $res = new Response("400", ["message" => "User id does not match"]);
            ResponseController::respondJson($res);
        }
        # get full user dataset from token
        $user_data = $db_connection->perform_query("SELECT user_id, user_firstname, user_lastname, user_email FROM t4_users WHERE user_id = ?", [$user_id])->fetch_assoc();
        $res = new Response("200", ["message" => "User data fetched successfully", "data" => $user_data]);
        ResponseController::respondJson($res);
    }

    /**
     * @throws Exception
     * Function to fetch a user_id by token, returns the user_id as int
     */
    public static function getUserIdByToken($token): int {
        # check if token is valid
        $db_connection = DatabaseSingleton::getInstance();
        # get user id from token
        $idnull = $db_connection->perform_query("SELECT session_user FROM t4_sessions WHERE session_token = ?", [$token])->fetch_assoc()["session_user"];
        if ($idnull == null) {
            $res = new Response("500", ["message" => "Error while fetching user id"]);
            ResponseController::respondJson($res);
        }
        return $idnull;
    }


}