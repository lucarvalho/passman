<?php
/**
 * Created by PhpStorm.
 * User: wolfi
 * Date: 1/10/16
 * Time: 23:15
 */

namespace OCA\Passman\Db;


use Icewind\SMB\Share;
use OCA\Passman\Utility\Utils;
use OCP\AppFramework\Db\Mapper;
use OCP\IDBConnection;

class ShareRequestMapper extends Mapper {
    const TABLE_NAME = 'passman_share_request';

    public function __construct(IDBConnection $db, Utils $utils) {
        parent::__construct($db, self::TABLE_NAME);
        $this->utils = $utils;
    }

    public function createRequest(ShareRequest $request){
        return $this->insert($request);
    }

    /**
     * Obtains a request by the given item and vault GUID pair
     * @param $item_guid
     * @param $target_vault_guid
     * @return ShareRequest
     */
    public function getRequestByItemAndVaultGuid($item_guid, $target_vault_guid){
        $q = "SELECT * FROM *PREFIX*" . self::TABLE_NAME . " WHERE item_guid = ? AND target_vault_guid = ?";
        return $this->findEntity($q, [$item_guid, $target_vault_guid]);
    }

    /**
     * Get shared items for the given item_guid
     * @param $item_guid
     * @return ShareRequest[]
     */
    public function getRequestsByItemGuid($item_guid){
        $this->db->executeQuery("SET sql_mode = '';");
        $q = "SELECT *, target_user_id FROM *PREFIX*" . self::TABLE_NAME . " WHERE item_guid = ? GROUP BY target_user_id;";
        return $this->findEntities($q, [$item_guid]);
    }

    public function cleanItemRequestsForUser($item_id, $target_user_id){
		$q = "DELETE FROM *PREFIX*" . self::TABLE_NAME . " WHERE item_id = ? AND target_user_id = ?";
		$this->execute($q, [$item_id, $target_user_id]);
        return $this->execute($q, [$item_id, $target_user_id]);
    }

    /**
     * Obtains all pending share requests for the given user ID
     * @param $user_id
     * @return ShareRequest[]
     */
    public function getUserPendingRequests($user_id){
        $q = "SELECT * FROM *PREFIX*". self::TABLE_NAME ." WHERE target_user_id = ?";
        return $this->findEntities($q, [$user_id]);
    }

    public function deleteShareRequest(ShareRequest $shareRequest){
    	$this->delete($shareRequest);
	}

	public function getShareRequestById($id){
		$q = "SELECT * FROM *PREFIX*" . self::TABLE_NAME . " WHERE id = ?";
		return $this->findEntity($q, [$id]);
	}

	public function getShareRequestsByGuid($item_guid){
		$q = "SELECT * FROM *PREFIX*" . self::TABLE_NAME . " WHERE 	item_guid = ?";
		return $this->findEntities($q, [$item_guid]);
	}

	public function updateShareRequest(ShareRequest $shareRequest){
		return $this->update($shareRequest);
	}

	public function getPendingShareRequests($item_guid, $user_id){
		$q = "SELECT * FROM *PREFIX*" . self::TABLE_NAME . " WHERE 	item_guid = ? and target_user_id= ?";
		return $this->findEntities($q, [$item_guid, $user_id]);
	}

}