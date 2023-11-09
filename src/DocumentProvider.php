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
    #[NoReturn] public function readDocumentMetaCollection($token): void
    {
        # validate token
        AuthenticationProvider::validatetoken($token);
        $db = DatabaseSingleton::getInstance();
        # get user id
        $userId = AuthenticationProvider::getUserIdByToken($token);
        $result = $db->perform_query("SELECT * FROM t4_documents WHERE document_owner = ? ORDER BY document_created DESC", [$userId]);
        if ($result->num_rows == 0) {
            $r = new Response("404", ["message" => "Document not found"]);
        } else {
            # Iterate though array to build a assoc array with all rows.
            $documents = [];
            while ($row = $result->fetch_assoc()) {
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
            $r = new Response("404", ["message" => "Document not found"]);
        } else {
            # print out the document data
            $r = new Response("200", $result->fetch_assoc());
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
        # retrieve the document id
        $document_id = $db->get_last_inserted_id();
        if ($result) {
            $r = new Response("200", ["message" => "Document created", "document_id" => $document_id]);
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
        # Remove all deltas for the document id
        $delta_delete = $db_connection->perform_query("DELETE FROM t4_deltas WHERE delta_owner = ? AND delta_document = ?", [$user_id, $document_id]);
        if (!$delta_delete) {
            $r = new Response("500", ["message" => "Failed while deleting deltas for the document"]);
            ResponseController::respondJson($r);
        }
        # Remove the document
        $result = $db_connection->perform_query("DELETE FROM t4_documents WHERE document_owner = ? AND document_id = ?", [$user_id, $document_id]);
        if ($result) {
            $r = new Response("200", ["message" => "Document deleted"]);
        } else {
            $r = new Response("500", ["message" => "Document could not be deleted"]);
        }
        ResponseController::respondJson($r);
    }
}