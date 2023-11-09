<?php

namespace src;

use Exception;
use JetBrains\PhpStorm\NoReturn;

class DeltaProvider
{
    /**
     * @throws Exception
     * provides a list of all deltas by a user and document, old to new.
     */
    #[NoReturn] public function readDocumentDeltas($token, $documentid): void
    {
        # validate token
        AuthenticationProvider::validatetoken($token);
        $db = DatabaseSingleton::getInstance();
        # get user id
        $userId = AuthenticationProvider::getUserIdByToken($token);
        $result = $db->perform_query("SELECT * FROM t4_deltas WHERE delta_owner = ? AND delta_document = ? ORDER BY delta_creation", [$userId, $documentid]);
        if ($result->num_rows == 0) {
            $r = new Response("404", ["message" => "No deltas found"]);
        } else {
            $deltas = [];
            while ($row = $result->fetch_assoc()) {
                $deltas[] = $row;
            }
            # print out the document data
            $r = new Response("200", $deltas);
        }
        ResponseController::respondJson($r);
    }

    public function readDelta($token, $deltaid) : void {
        AuthenticationProvider::validatetoken($token);
        $db = DatabaseSingleton::getInstance();
        # fetch the user_id
        $userId = AuthenticationProvider::getUserIdByToken($token);
        $result = $db->perform_query("SELECT * FROM t4_deltas WHERE delta_owner = ? AND delta_id = ?", [$userId, $deltaid]);
        if ($result->num_rows == 0) {
            $r = new Response("404", ["message" => "Delta not found"]);
        } else {
            # print out the document data
            $r = new Response("200", $result->fetch_assoc());
        }
        ResponseController::respondJson($r);
    }

    /**
     * @throws Exception
     */
    #[NoReturn] public function createDelta($token, $documentid, $deltacontent) : void {
        AuthenticationProvider::validatetoken($token);
        $db = DatabaseSingleton::getInstance();
        # fetch the user_id
        $userId = AuthenticationProvider::getUserIdByToken($token);
        # check if the document exists
        $result = $db->perform_query("SELECT COUNT(document_id) as doccount FROM t4_documents WHERE document_owner = ? AND document_id = ?", [$userId, $documentid])->fetch_assoc()["doccount"];
        if ($result == 0) {
            $r = new Response("404", ["message" => "Document in possesion not found"]);
            ResponseController::respondJson($r);
        }
        # create the delta
        $result = $db->perform_query("INSERT INTO t4_deltas (delta_owner, delta_document, delta_content) VALUES (?, ?, ?)", [$userId, $documentid, $deltacontent]);
        if ($result == 0) {
            $r = new Response("500", ["message" => "Delta could not be created"]);
        } else {
            $r = new Response("200", ["message" => "Delta created", "delta_id" => $db->get_last_inserted_id()]);
        }
        ResponseController::respondJson($r);
    }

    /**
     * @throws Exception
     */
    #[NoReturn] public function updateDelta($token, $deltaid, $newcontent) : void {
        AuthenticationProvider::validatetoken($token);
        $db = DatabaseSingleton::getInstance();
        # fetch user_id
        $userId = AuthenticationProvider::getUserIdByToken($token);
        # check if the delta exists
        $result = $db->perform_query("SELECT COUNT(delta_id) as deltacount FROM t4_deltas WHERE delta_owner = ? AND delta_id = ?", [$userId, $deltaid])->fetch_assoc()["deltacount"];
        if ($result == 0) {
            $r = new Response("404", ["message" => "Delta not found"]);
            ResponseController::respondJson($r);
        }
        # update the delta
        $result = $db->perform_query("UPDATE t4_deltas SET delta_content = ? WHERE delta_owner = ? AND delta_id = ?", [$newcontent, $userId, $deltaid]);
        if ($result == 0) {
            $r = new Response("500", ["message" => "Delta could not be updated"]);
        } else {
            $r = new Response("200", ["message" => "Delta updated", "newcontent" => $newcontent]);
        }
        ResponseController::respondJson($r);
    }

    /**
     * @throws Exception
     */
    #[NoReturn] public function deleteDelta($token, $deltaid) : void {
        AuthenticationProvider::validatetoken($token);
        $db = DatabaseSingleton::getInstance();
        # fetch user_id
        $userId = AuthenticationProvider::getUserIdByToken($token);
        # check if the delta exists
        $result = $db->perform_query("SELECT COUNT(delta_id) as deltacount FROM t4_deltas WHERE delta_owner = ? AND delta_id = ?", [$userId, $deltaid])->fetch_assoc()["deltacount"];
        if ($result == 0) {
            $r = new Response("404", ["message" => "Delta not found"]);
            ResponseController::respondJson($r);
        }
        # delete the delta
        $result = $db->perform_query("DELETE FROM t4_deltas WHERE delta_owner = ? AND delta_id = ?", [$userId, $deltaid]);
        if ($result == 0) {
            $r = new Response("500", ["message" => "Delta could not be deleted"]);
        } else {
            $r = new Response("200", ["message" => "Delta deleted"]);
        }
        ResponseController::respondJson($r);
    }
}