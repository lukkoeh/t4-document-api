<?php

namespace src;

use Exception;
use JetBrains\PhpStorm\NoReturn;

class DocumentProvider
{
    /**
     * @throws Exception
     * provides a list of all documents by a user
     */
    #[NoReturn] public function readDocumentMetaCollection($token, $user_id_provided): void
    {
        # validate token
        AuthenticationProvider::validatetoken($token);
        $db = DatabaseSingleton::getInstance();
        # get user id
        $userId = AuthenticationProvider::getUserIdByToken($token);
        // check if user id provided in parameter matches the one from database
        if ($user_id_provided != $userId) {
            $res = new Response("400", ["message" => "User id does not match"]);
            ResponseController::respondJson($res);
        }
        # also get all documents that are shared with the user
        $result = $db->perform_query("SELECT * FROM t4_documents WHERE document_id IN (SELECT document_id FROM t4_shared WHERE user_id = ?) ORDER BY document_created DESC", [$userId]);
        if ($result->num_rows == 0) {
            $r = new Response("404", ["message" => "No documents available"]);
        } else {
            # Iterate though array to build a assoc array with all rows.
            $documents = [];
            while ($row = $result->fetch_assoc()) {
                $row["document_shared"] = false;
                $documents[] = $row;
            }
            # print out the document data
            $r = new Response("200", $documents);
        }
        ResponseController::respondJson($r);
    }

    /**
     * @throws Exception
     * provides a single document by id
     */
    #[NoReturn] public function readDocumentMetaById($token, $documentid): void
    {
        # validate token
        AuthenticationProvider::validatetoken($token);
        $db = DatabaseSingleton::getInstance();
        # get user id
        $userId = AuthenticationProvider::getUserIdByToken($token);
        $result = $db->perform_query("SELECT * FROM t4_documents WHERE document_owner = ? AND document_id = ?", [$userId, $documentid]);
        if ($result->num_rows == 0) {
            # look if there is a shared document with the user
            $result_shared = $db->perform_query("SELECT * FROM t4_documents WHERE document_id IN (SELECT document_id FROM t4_shared WHERE user_id = ?) AND document_id = ?", [$userId, $documentid]);
            if ($result_shared->num_rows == 0) {
                $r = new Response("404", ["message" => "Document not found"]);
            } else {
                $result_shared = $result_shared->fetch_assoc();
                $result_shared["document_shared"] = true;
                $r = new Response("200", $result_shared);
            }
        } else {
            $result = $result->fetch_assoc();
            $result["document_shared"] = false;
            # print out the document data
            $r = new Response("200", $result);
        }
        ResponseController::respondJson($r);
    }

    public function createDocument($token, $documentname = null)
    {
        AuthenticationProvider::validatetoken($token);
        $user_id = AuthenticationProvider::getUserIdByToken($token);
        $db = DatabaseSingleton::getInstance();
        if ($documentname == null) {
            $documentname = "Untitled Document";
        }
        $result = $db->perform_query("INSERT INTO t4_documents (document_title, document_owner) VALUES (?, ?)", [$documentname, $user_id]);
        $document_id = $db->get_last_inserted_id();
        # also share it with the user
        $result_share = $db->perform_query("INSERT INTO t4_shared (user_id, document_id) VALUES (?, ?)", [$user_id, $document_id]);
        if ($result) {
            $r = new Response("200", ["message" => "Document created successfully", "id" => $document_id]);
        } else {
            $r = new Response("500", ["message" => "Document could not be created"]);
        }
        ResponseController::respondJson($r);
    }

    /**
     * @throws Exception
     */
    #[NoReturn] public function updateDocument($token, $document_id, $newtitle): void
    {
        AuthenticationProvider::validatetoken($token);
        $db_connection = DatabaseSingleton::getInstance();
        $user_id = AuthenticationProvider::getUserIdByToken($token);
        $result = $db_connection->perform_query("UPDATE t4_documents SET document_title = ? WHERE document_owner = ? AND document_id = ?", [$newtitle, $user_id, $document_id]);
        if ($result) {
            $r = new Response("200", ["message" => "Document updated"]);
        } else {
            $r = new Response("500", ["message" => "Document could not be updated"]);
        }
        ResponseController::respondJson($r);
    }

    /**
     * @throws Exception
     */
    #[NoReturn] public function deleteDocument($token, $document_id): void
    {
        AuthenticationProvider::validatetoken($token);
        $db_connection = DatabaseSingleton::getInstance();
        $user_id = AuthenticationProvider::getUserIdByToken($token);
        # check if the document is owned or shared with the user specified by the token
        $result = $db_connection->perform_query("SELECT COUNT(document_id) as doccount FROM t4_documents WHERE document_id IN (SELECT document_id FROM t4_shared WHERE user_id = ?) AND document_id = ?", [$user_id, $document_id])->fetch_assoc()["doccount"];
        if ($result == 0) {
            $r = new Response("404", ["message" => "Document not found"]);
            ResponseController::respondJson($r);
        }
        $delta_delete = $db_connection->perform_query("DELETE FROM t4_deltas WHERE delta_document = ?", [$document_id]);
        # Remove all shared of this document
        $shared_delete = $db_connection->perform_query("DELETE FROM t4_shared WHERE document_id = ?", [$document_id]);
        if (!$delta_delete || !$shared_delete) {
            $r = new Response("500", ["message" => "Failed while deleting deltas for the document"]);
            ResponseController::respondJson($r);
        }
        # Remove the document
        $result = $db_connection->perform_query("DELETE FROM t4_documents WHERE document_id = ?", [$document_id]);
        if ($result) {
            $r = new Response("200", ["message" => "Document deleted"]);
        } else {
            $r = new Response("500", ["message" => "Document could not be deleted"]);
        }
        ResponseController::respondJson($r);
    }

    /**
     * @throws Exception
     */
    #[NoReturn] public function shareDocument($token, $document_id, $user_id_target): void {
        AuthenticationProvider::validatetoken($token);
        $db_connection = DatabaseSingleton::getInstance();
        $user_id = AuthenticationProvider::getUserIdByToken($token);
        # check if the document exists
        $result = $db_connection->perform_query("SELECT COUNT(document_id) as doccount FROM t4_documents WHERE document_owner = ? AND document_id = ?", [$user_id, $document_id])->fetch_assoc()["doccount"];
        if ($result == 0) {
            $r = new Response("404", ["message" => "Document not found"]);
            ResponseController::respondJson($r);
        }
        # check if the user exists
        $result = $db_connection->perform_query("SELECT COUNT(user_id) as usercount FROM t4_users WHERE user_id = ?", [$user_id_target])->fetch_assoc()["usercount"];
        if ($result == 0) {
            $r = new Response("404", ["message" => "User not found"]);
            ResponseController::respondJson($r);
        }
        # check if the user already has access to the document
        $result = $db_connection->perform_query("SELECT COUNT(user_id) as usercount FROM t4_shared WHERE user_id = ? AND document_id = ?", [$user_id_target, $document_id])->fetch_assoc()["usercount"];
        if ($result != 0) {
            $r = new Response("400", ["message" => "User already has access to the document"]);
            ResponseController::respondJson($r);
        }
        # share the document
        $result = $db_connection->perform_query("INSERT INTO t4_shared (user_id, document_id) VALUES (?, ?)", [$user_id_target, $document_id]);
        if ($result) {
            $r = new Response("200", ["message" => "Document shared"]);
        } else {
            $r = new Response("500", ["message" => "Document could not be shared"]);
        }
        ResponseController::respondJson($r);
    }

    /**
     * @throws Exception
     */
    #[NoReturn] public function unshareDocument($token, $document_id, $user_id_target): void {
        AuthenticationProvider::validatetoken($token);
        $db_connection = DatabaseSingleton::getInstance();
        $user_id = AuthenticationProvider::getUserIdByToken($token);
        # check if the document exists
        $result = $db_connection->perform_query("SELECT COUNT(document_id) as doccount FROM t4_documents WHERE document_owner = ? AND document_id = ?", [$user_id, $document_id])->fetch_assoc()["doccount"];
        if ($result == 0) {
            $r = new Response("404", ["message" => "Document not found"]);
            ResponseController::respondJson($r);
        }
        # check if the user exists
        $result = $db_connection->perform_query("SELECT COUNT(user_id) as usercount FROM t4_users WHERE user_id = ?", [$user_id_target])->fetch_assoc()["usercount"];
        if ($result == 0) {
            $r = new Response("404", ["message" => "User not found"]);
            ResponseController::respondJson($r);
        }
        # unshare the document
        $result = $db_connection->perform_query("DELETE FROM t4_shared WHERE user_id = ? AND document_id = ?", [$user_id_target, $document_id]);
        if ($result) {
            $r = new Response("200", ["message" => "Document unshared"]);
        } else {
            $r = new Response("500", ["message" => "Document could not be unshared"]);
        }
        ResponseController::respondJson($r);
    }

}