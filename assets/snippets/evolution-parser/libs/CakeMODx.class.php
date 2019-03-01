<?php
/**
 * Some different MODx Evolution APIs for easier actions.
 * 
 * @version 1.3 beta
 * @author Amir Hossein Hodjati Pour (AHHP) <Boplo.ir>
 * @date 2009/07/09
 * @license GPL 
 * @desc The class logs error messages in errorlog.html.
 *  
 * THIS CLASS IS IN DEVELOPMENT SO THERE IS NO WARRANTY FOR MISSING DATA.
 * CODES ARE CLEAN AND DOCUMENTAED SO AUTHOR (AHHP) IS NOT RESPONSIBLE FOR ANY ISSUE!
 */
 
class CakeMODx {

	/**
	 * DBAPI object
	 * @var object
	 */
	var $db;
	
	
	
	
    /**
     * The CakeMODx Constructor.
     *
     * Setting class properties.
     * @return void
     */
	function CakeMODx()
	{
		global $modx;
		$this->db = $modx->db;
	}




/* ============================================================= */
/*                                                                                                                                           */
/* DOCUMENT management methods                                                                                        */
/*                                                                                                                                           */
/* ============================================================= */
	
    /**
     * Creates a new MODx document.
     *
     * @param array $fields An associative array of `site_content` table.
     * @return mixed ID of new document as integer on success and FALSE on failure.
     */
	function newDocument($fields=array())
	{
		if(! $id = $this->db->insert($fields, $this->getFullTableName('site_content')))
			$this->logError('newDocument', $this->db->getLastError(), time());
		
		return $id;
	}
	
	
	
    /**
     * Update/Edit a MODx document.
     *
     * @desc First and/or third parameter must be used.
     * @param integer $id ID of document need to edit.
     * @param array $update An associative array of `site_content` table.
     * @param string $where A valid WHERE statement directly uses in SQL.
     * @return mixed ID of edited document as integer on success and FALSE on failure.
     */
	function updateDocument($id=0, $update=array(), $where='')
	{
		$where = (($id != 0) ? "`id`='$id'" : "") . (($id != 0 && $where != '') ? " OR " : "") . $where;
		if(! $upd = $this->db->update($update, $this->getFullTableName('site_content'), $where) )
			$this->logError('updateDocument', $this->db->getLastError(), time());
		
		return $upd;
	}
	
	
	
    /**
     * Delete a MODx document with all details. NO RECOVERY IS POSSIBLE AFTER IT!
     *
     * @desc First and/or second parameter must be used.
     * @param integer $id ID of document need to delete.
     * @param string $where A valid WHERE statement directly uses in SQL.
     * @return boolean TRUE on success and FALSE on failure.
     */
	function deleteDocument($id=0, $where='')
	{
		$sc_table = $this->getFullTableName('site_content');
		
		if($where != '')
		{
			// get IDs that is necessary for other tables
			$select = $this->db->select("id", $sc_table, $where);
			$IDs = array();
			while($r = $this->db->getRow($select))	$IDs[] = $r['id'];
			
			if($id != 0)	$IDs[] = $id;
		}
		elseif($id != 0)
			$IDs = array($id);
		else
		{
			$this->logError("deleteDocument", "Arguments aren't valid", time());
			return false;
		}
		
		if(count($IDs) == 0)	return true;
		
		$where = '`id` IN ('. join(",",$IDs) .')';
		if(! $del = $this->db->query("DELETE FROM $sc_table WHERE $where") )
		{
			$this->logError('deleteDocument', "Couldn't remove document: ".$this->db->getLastError(), time());
			return false;
		}
		
		$where = '`contentid` IN ('. join(",",$IDs) .')';
		if(! $del = $this->db->query("DELETE FROM " .$this->getFullTableName('site_tmplvar_contentvalues'). "WHERE  $where") )
		{
			$this->logError('deleteDocument', "Couldn't remove document TVs but docs have been deleted from `site_content`: ".$this->db->getLastError(), time());
			return false;
		}
		
		$where = '`document` IN ('. join(",",$IDs) .')';
		if(! $del = $this->db->query("DELETE FROM " .$this->getFullTableName('document_groups'). "WHERE  $where") )
		{
			$this->logError('deleteDocument', "Couldn't remove document from documents and document groups but TVs have been deleted from `site_tmplvar_contentvalues`: ".$this->db->getLastError(), time());
			return false;
		}
		
		return true;
	}
	
	
	
    /**
     * Get fields from `site_content` table for one or more MODx document.
     *
     * @desc First and/or third parameter must be used.
     * @param integer $id ID of document.
     * @param string $fields Comma seperated column names directly uses in SQL.
     * @param string $where A valid WHERE statement directly uses in SQL.
     * @return mixed array on success and FALSE on failure.
     */
	function getDocField($id=0, $fields='*', $where='')
	{
		$where = (($id != 0) ? "`id`='$id'" : "") . (($id != 0 && $where != '') ? " OR " : "") . $where;
		$fields = (is_array($fields)) ? join(',', $fields) : $fields;
		
		if(! $select = $this->db->select($fields, $this->getFullTableName('site_content'), $where) )
		{
			$this->logError('getDocField', $this->db->getLastError(), time());
			return false;
		}
		
		if($this->db->getRecordCount($select) == 0)
			return false;
		if($this->db->getRecordCount($select) == 1)
			return $this->db->getRow($select);
		
		$output_arr = array();
		while($row = $this->db->getRow($select))
			$output_arr[] = $row;
		return $output_arr;
	}
	
	
	
    /**
     * Empty MODx recycle bin. NO RECOVERY IS POSSIBLE AFTER IT!
     *
     * @return boolean TRUE on success and FALSE on failure.
     */
	function emptyTrash()
	{
		if(! $res = $this->deleteDocument(0, "deleted=1"))
		{
			$this->logError("emptyTrash", "Couldn't empty the trash. Check logs for errors.", time());
			return false;
		}
		return true;
	}
	
	
	
    /**
     * Get count of all sub-document for one or more parent documents.
     *
     * @desc First and/or second parameter must be used.
     * @param integer $parent ID of parent.
     * @param string $where A valid WHERE statement directly uses in SQL.
     * @return integer Children count.
     */
	function getChildrenCount($parent=0, $where='')
	{
		$tbl = $this->getFullTableName('site_content');
		$where = (($parent == 0) ? "id>0" : "`parent`='$parent'") . ($where == '' ? "" : " AND ") . $where;
		$sql = "SELECT COUNT(id) FROM " .$this->getFullTableName('site_content'). " WHERE $where";
		$count = $this->db->getRow($this->db->query($sql));
		return $count['COUNT(id)'];
	}
	
	
	
    /**
     * Get count of all documents of site.
     *
     * @param string $where A valid WHERE statement directly uses in SQL.
     * @return integer Documents count.
     */
	function getSiteDocumentCount($where='published=1')
	{
		return $this->getChildrenCount(0, $where);
	}
	
	
	
    /**
     * Print output of a document.
     *
     * @desc Checking for published , deleted and etc depends on user!
     * @desc All MODx tags will be translated. Useful in AJAX.
     * @param integer $id ID of document.
     * @return void.
     */
	function getDocumentOutput($id)
	{
		global $modx;
		$_REQUEST['id'] = $id;
		$modx->executeParser();
	}
	
	
	
	
    /**
     * Renew the site cache.
     *
     * @return void.
     */
	function updateCache()
	{
		include_once MODX_BASE_PATH . "manager/processors/cache_sync.class.processor.php";
		$sync = new synccache();
		$sync->setCachepath(MODX_BASE_PATH . "assets/cache/");
		$sync->setReport(false);
		$sync->emptyCache();
	}




/* ============================================================= */
/*                                                                                                                                           */
/* TEMPLATE VARIABLE methods                                                                                               */
/*                                                                                                                                           */
/* ============================================================= */	
    
	/**
     * Set a TV value for a document.
     *
     * @param mixed $tv Name or ID of TV. It recognizes NUMERIC inputs as ID so Be careful with Numeric names!
     * @param integer $docId ID of document.
     * @param string $value Value of TV.
     * @return mixed New TV ID on success and FALSE on failure.
     */
	function setTV($tv, $docId, $value)
	{
		if( !is_numeric($tv) )
		{
			$arr = $this->TV_name2id(array($tv));
			$tv = $arr[$tv];
		}
		
		if( !is_numeric($tv) || $tv == 0 )
			return false;
		
		$insert = array("tmplvarid" => $tv, 'contentid' => $docId, 'value' => $value);
		if( ! $id = $this->db->insert($insert, $this->getFullTableName('site_tmplvar_contentvalues')) )
			return false;
		return $id;
	}
	
	
	
	/**
     * Get value of a TV.
     *
     * @param integer $docId ID of document.
     * @param mixed  $tvNames Names of TVs as array, single name as string. Leave it empty for all TVs.
     * @return array An associative array, Key is TV name and value is TV value.
     */
	function getTV($docId, $tvNames=null)
	{
		$tvIds = $tvFlags = $TV = array();
		$tbl = $this->getFullTableName('site_tmplvar_contentvalues');
		
		if(!$tvNames)
		{
			$sql = "
				SELECT tv.id, tv.name FROM ". $this->getFullTableName('site_tmplvars') ." tv
				INNER JOIN $tbl tvv
				WHERE tvv.contentid=$docId AND tv.id=tvv.tmplvarid
			";
			$select = $this->db->query($sql);
			while($r1 = $this->db->getRow($select))
			{
				$tvIds[] = $r1['id'];
				$tvFlags[$r1['id']] = $r1['name'];
			}
		}
		else
		{
			$tvNames = is_string($tvNames) ? array($tvNames) : $tvNames;
			$tvs_arr = $this->TV_name2id($tvNames);
			foreach($tvs_arr as $tvs_name => $tvs_id)
			{
				$tvIds[] = $tvs_id;
				$tvFlags["$tvs_id"] = $tvs_name;
			}
		}
		
		if(count($tvIds) == 0)	return array();
		
		$where = '`tmplvarid` IN (' .join(",",$tvIds). ') AND `contentid`='.$docId;
		$select = $this->db->select('tmplvarid, value',$tbl,$where);
		
		while($r = $this->db->getRow($select))
			$TV[$tvFlags[$r['tmplvarid']]] = $r['value'];
		
		return $TV;
	}
	
	
	
	/**
     * Delete a TV value of one or more documents.
     *
     * @desc Use it for delete a TV value for a number of documents.
     * @param string $tv TV name.
     * @param mixed $docIds Array contains document IDs or integer for a single ID. 
     * @return boolean TRUE on success and FALSE on failure.
     */
	function unsetTV($tv, $docIds=array())
	{
		$arr = $this->TV_name2id(array($tv));
		$tvId = $arr[$tv];
		$docIds = !is_array($docIds) ? array($docIds) : $docIds;
		
		if(count($docIds) == 0)	return true;
		
		$tbl = $this->getFullTableName('site_tmplvar_contentvalues');
		$where = "`tmplvarid`=$tvId AND `contentid` IN (" .join(",",$docIds). ")";
		$res = $this->db->delete($tbl,$where);
		return ($res ? true : false);
	}
	
	
	
	/**
     * Update/Edit a TV value.
     *
     * @param mixed $tv Name of ID of TV. It recognizes NUMERIC inputs as ID so Be careful with Numeric names!
     * @param integer $docId ID of document.
     * @param string $value Value of TV.
     * @return mixed New TV ID on success and FALSE on failure.
     */
	function updateTV($tv, $docId, $value)
	{
		if( !is_numeric($tv) )
		{
			$arr = $this->TV_name2id(array($tv));
			$tv = (integer) $arr[$tv];
		}
		
		if( !is_numeric($tv) || $tv == 0 )
			return false;
		
		$update = array("tmplvarid" => $tv, 'contentid' => $docId, 'value' => $value);
		if( ! $id = $this->db->update($update, $this->getFullTableName('site_tmplvar_contentvalues'), "tmplvarid=$tv AND contentid = $docId") )
			return false;
		return $id;
	}




/* ============================================================= */
/*                                                                                                                                           */
/* WEB USER methods                                                                                                              */
/*                                                                                                                                           */
/* ============================================================= */

	/**
     * Create new web user.
     *
     * @param string $user Username.
     * @param string $pass Password. Method encodes parameter by MD5() function!
     * @param array $attributes An associative array for `web_user_attributes` table. Key is column name and value is column value.
     * @param array $customTable A multi dimentional array for other custom tables.
	 * Key is table name (without prefix) and value is an associative array, Key is column name and value is column value.
	 * The user ID will be put in all other tables in `internalKey` column. example:
	 * <code>
	 *	$customTable = array(
	 *		"table1" => array(
	 *				"col1-1" => "val1-1",
	 *				"col1-2" => "val1-2"
	 *		),
	 *	 	"table2" => array(
	 *				"col2-1" => "val2-1",
	 *				"col2-2" => "val2-2"
	 *		)
	 *	);
	 * </code>
     * @param string $cachepwd `cachepwd` column value in `web_users` table.
     * @return mixed User ID on success and FALSE on failure.
     */
	function newUser($user, $pass, $attributes=null, $customTable=null, $cachepwd=null)
	{
		$web_users = array("username" => $user, "password" => md5($pass));
		if($cachepwd)	$web_users['cachepwd'] = $cachepwd;
		
		if(! $userId = $this->db->insert($web_users, $this->getFullTableName("web_users")) )
			return false;
		
		if($attributes)
		{
			$attributes['internalKey'] = $userId;
			if(! $attrib = $this->db->insert($attributes, $this->getFullTableName("web_user_attributes")) )
				$this->logError("newUser", "Couldn't insert $user's attributes: ".$this->db->getLastError(), time());
		}
		
		if($customTable)
		{
			foreach($customTable as $tbl => $params)
			{
				$params['internalKey'] = $userId;
				if(! $cstm = $this->db->insert($params, $this->getFullTableName($tbl)) )
					$this->logError("newUser", "Couldn't insert $user's attributes in \"$tbl\": ".$this->db->getLastError(), time());
			}
		}
		
		return $userId;
	}
	
	
	
	/**
     * Update/Edit profile for one/more user(s).
     *
     * @desc First and/or fourth parameter must be used.
     * @param integer $id ID of user.
     * @param string $table Name of table needs to be updated.
     * @param array $update An associative array, Key is column name and value is column value.
     * @param string $where A valid WHERE statement directly uses in SQL.
     * @return void.
     */
	function updateUserProfile($id=0, $table, $update=array(), $where='')
	{
		$idCol = ($table == 'web_users') ? "id" : "internalKey";
		$where = (($id != 0) ? "`$idCol`='$id'" : "") . (($id != 0 && $where != '') ? " OR " : "") . $where;
		
		if(! $updt = $this->db->update($update, $this->getFullTableName($table), $where) )
			$this->logError("updateUserProfile", "Couldn't update $user's profile in \"$table\": ".$this->db->getLastError(), time());
		return $updt;
	}
	
	
	
	/**
     * Delete one/more user(s). NO RECOVERY IS POSSIBLE AFTER IT!
     *
     * @desc First and/or second parameter must be used.
     * @param integer $id ID of user.
     * @param string $where A valid WHERE statement directly uses in SQL.
     * @param string $customTable See same parameter in {@link CakeMODx::newUser()} method.
     * @return boolean TRUE on success and FALSE on failure.
     */
	function unsetUser($id=0, $where='', $customTable=null)
	{
		if($where != '')
		{
			// get IDs that is necessary for other tables
			$select = $this->db->select("id", $this->getFullTableName('web_users'), $where);
			$IDs = array();
			while($r = $this->db->getRow($select))	$IDs[] = $r['id'];
			
			if($id != 0)	$IDs[] = $id;
		}
		elseif($id != 0)
			$IDs = array($id);
		else
		{
			$this->logError("unsetUser", "Arguments aren't valid", time());
			return false;
		}
		
		if(count($IDs) == 0)	return true;
		
		// `web_users` row
		$where = '`id` IN ('. join(",",$IDs) .')';
		if(! $del = $this->db->delete($this->getFullTableName('web_users'), $where) )
		{
			$this->logError('unsetUser', "Couldn't remove user: ".$this->db->getLastError(), time());
			return false;
		}
		
		// `web_user_attributes` row
		$where = '`internalKey` IN ('. join(",",$IDs) .')';
		if(! $del = $this->db->delete($this->getFullTableName('web_user_attributes'), $where) )
			$this->logError('unsetUser', "Couldn't remove user's attributes but user's account have been deleted from `web_users`: ".$this->db->getLastError(), time());
		
		// `web_groups` row
		$where = '`webuser` IN ('. join(",",$IDs) .')';
		if(! $del = $this->db->delete($this->getFullTableName('web_groups'), $where) )
			$this->logError('unsetUser', "Couldn't remove user from groups but user's account and attributes have been deleted: ".$this->db->getLastError(), time());
		
		// `web_user_settings` row
		$where = '`webuser` IN ('. join(",",$IDs) .')';
		if(! $del = $this->db->delete($this->getFullTableName('web_user_settings'), $where) )
			$this->logError('unsetUser', "Couldn't remove user's settings but user's account and attributes have been deleted: ".$this->db->getLastError(), time());
		
		// custom tables rows
		if($customTable)
		{
			$where = '`internalKey` IN ('. join(",",$IDs) .')';
			foreach($customTable as $tbl)
				// `$tbl` row
				if(! $del = $this->db->delete($this->getFullTableName($tbl), $where) )
					$this->logError('unsetUser', "Couldn't remove user's data from `$tbl` but user's account and attributes and settings have been deleted: ".$this->db->getLastError(), time());
		}
		
		return true;
	}
	
	
	
	/**
     * Get data from one/more user(s).
     *
     * @desc First and/or fourth parameter must be used.
     * @param integer $id ID of user.
     * @param string $fields Comma seperated column names to use directly in SQL.
     * @param string $table Name of table name.
     * @return array Associative array, Key is column name and value is column value.
     */
	function getUserField($id=0, $fields='*', $table, $where='')
	{
		$idCol = ($table == 'web_users') ? "id" : "internalKey";
		$where = (($id != 0) ? "`$idCol`='$id'" : "") . (($id != 0 && $where != '') ? " OR " : "") . $where;
		$select = $this->db->select($fields, $this->getFullTableName($table), $where);
		
		if($this->db->getRecordCount($select) == 0)
			return false;
		if($this->db->getRecordCount($select) == 1)
			return $this->db->getRow($select);
		
		$output_arr = array();
		while($row = $this->db->getRow($select))
			$output_arr[] = $row;
		
		return $output_arr;
	}
	

	
	/**
     * Get document which the user has been assigned to them in his/her webgroups.
     *
     * @param integer $id ID of user.
     * @return array An array contains documents IDs.
     */
	function getAssignedDocs($userId)
	{
		return $this->getPrivateDocs($userId, true);
	}
	
	
	
	/**
     * Get document which the user has NOT been assigned to them in his/her webgroups.
     *
     * @param integer $id ID of user.
     * @return array An array contains documents IDs.
     */
	function getForbiddenDocs($userId)
	{
		return $this->getPrivateDocs($userId, false);
	}




/* ============================================================= */
/*                                                                                                                                           */
/* WEB/DOC GROUP methods                                                                                                   */
/*                                                                                                                                           */
/* ============================================================= */
	
	/**
     * Create new Document Group.
     *
     * @param string $name Name for Document Group.
     * @return mixed ID of new Docgroup on success and FALSE on failure.
     */
	function newDocgroup($name)
	{
		return $this->newGroup($name, 'document');
	}
	
	
	
	/**
     * Rename a Document Group.
     *
     * @param string $newname New name for Document Group.
     * @param string $oldname Old name of Document Group.
     * @return mixed ID of renamed Docgroup on success and FALSE on failure.
     */
	function renameDocgroup($newname, $oldname)
	{
		return $this->renameGroup($newname, $oldname, 'document');
	}
	
	
	
	/**
     * Delete a Document Group and ALL links to it's members. NO RECOVERY IS POSSIBLE AFTER IT!
     *
     * @param string $name Name for Document Group.
     * @return boolean TRUE on success and FALSE on failure.
     */
	function unsetDocgroup($name)
	{
		return $this->unsetGroup($name, 'document');
	}
	
	
	
	/**
     * Join document(s) to a Docgroup.
     *
     * @param string $groupname Name for Document Group.
     * @param integer _unlimited_ ID of documents to join as member.
     * @return boolean TRUE on success and FALSE on failure.
     */
	function joinDocgroup($groupname)
	{
		$args = func_get_args();
		unset($args[0]);
		
		return $this->joinGroup($groupname, 'document', $args);
	}
	
	
	
	/**
     * Get all members of a Docgroup.
     *
     * @param string $groupname Name for Document Group.
     * @return mixed An array contains document IDs on success and FALSE on failure.
     */
	function getDocgroupMembers($groupname)
	{
		return $this->getGroupMembers($groupname, 'document');
	}
	
	
	
	/**
     * Create new Web Group.
     *
     * @param string $name Name for Web Group.
     * @return mixed ID of new Webgroup on success and FALSE on failure.
     */
	function newWebgroup($name)
	{
		return $this->newGroup($name, 'web');
	}
	
	
	
	/**
     * Rename a Webument Group.
     *
     * @param string $newname New name for Web Group.
     * @param string $oldname Old name of Web Group.
     * @return mixed ID of renamed Webgroup on success and FALSE on failure.
     */
	function renameWebgroup($newname, $oldname)
	{
		return $this->renameGroup($newname, $oldname, 'web');
	}
	
	
	
	/**
     * Delete a Web Group and ALL links to it's members. NO RECOVERY IS POSSIBLE AFTER IT!
     *
     * @param string $name Name for Web Group.
     * @return boolean TRUE on success and FALSE on failure.
     */
	function unsetWebgroup($name)
	{
		return $this->unsetGroup($name, 'web');
	}
	
	
	
	/**
     * Join user(s) to a Webgroup.
     *
     * @param string $groupname Name for Web Group.
     * @param integer _unlimited_ ID of users to join as member.
     * @return boolean TRUE on success and FALSE on failure.
     */
	function joinWebgroup($groupname)
	{
		$args = func_get_args();
		unset($args[0]);
		
		return $this->joinGroup($groupname, 'web', $args);
	}
	
	
	
	/**
     * Get all members of a Webgroup.
     *
     * @param string $groupname Name for Web Group.
     * @return mixed An array contains document IDs on success and FALSE on failure.
     */
	function getWebgroupMembers($groupname)
	{
		return $this->getGroupMembers($groupname, 'web');
	}
	
	
	
	/**
     * Assign a Webgroup to a Docgroup.
     *
     * @param string $webgroupName Name for Web Group.
     * @param string $docgroupName Name for Document Group.
     * @return mixed ID of new assignment id in table on success and FALSE on failure.
     */
	function linkGroups($webgroupName, $docgroupName)
	{
		$wg = $this->group_name2id($webgroupName,'web');
		$dg = $this->group_name2id($docgroupName,'document');
		
		if( !$wg || !$dg)
		{
			$this->logError("linkGroups", "Couldn't link $webgroupName to $docgroupName. Names aren't valid.", time());
			return false;
		}
		
		$insert = array("webgroup"=>$wg , "documentgroup"=>$dg);
		if(! $id = $this->db->insert($insert, $this->getFullTableName('webgroup_access')) )
		{
			$this->logError("linkGroups", "Couldn't link $webgroupName to $docgroupName: ". $this->db->getLastError(), time());
			return false;
		}
		return $id;
	}
	
	
	
	/**
     * Unlink a Webgroup from a Docgroup.
     *
     * @param string $webgroupName Name for Web Group.
     * @param string $docgroupName Name for Document Group.
     * @return mixed ID of new assignment id in table on success and FALSE on failure.
     */
	function unlinkGroups($webgroupName, $docgroupName)
	{
		$wg = $this->group_name2id($webgroupName,'web');
		$dg = $this->group_name2id($docgroupName,'document');
		
		if( !$wg || !$dg)
		{
			$this->logError("unlinkGroups", "Couldn't unlink $webgroupName to $docgroupName. Names aren't valid.", time());
			return false;
		}
		
		$where = "`webgroup`=$wg AND `documentgroup`=$dg";
		if(! $id = $this->db->delete($this->getFullTableName('webgroup_access'), $where) )
		{
			$this->logError("unlinkGroups", "Couldn't unlink $webgroupName to $docgroupName: ". $this->db->getLastError(), time());
			return false;
		}
		return $id;
	}




/* ============================================================= */
/*                                                                                                                                           */
/* JOT COMMENT methods                                                                                                        */
/*                                                                                                                                           */
/* ============================================================= */
	
	/**
     * Get a comment by document container.
     *
     * @param integer $doc Document ID.
     * @param string $tagid Tagid parameter.
     * @param mixed $fields Fields as array and single field as string.
     * @return mixed Array of comment data on success and FALSE on failure.
     */
	function comment_byDoc($doc, $fields='content', $tagid='')
	{
		if(is_string($fields))		$fields = array($fields);
		$fields = "jot." . join(', jot.' , $fields);
		$tagid_where = ($tagid == '') ? '' : " AND jot.tagid='$tagid'";
		
		$sql = "
			SELECT $fields , jfld.label AS fieldLabel, jfld.content AS fieldValue FROM " .$this->getFullTableName('jot_content'). " jot
			LEFT JOIN " .$this->getFullTableName('jot_fields')." jfld ON jot.id=jfld.id WHERE jot.uparent=$doc $tagid_where
		";
		return $this->comment_query($sql);
	}
	
	
	
	/**
     * Get a comment by parent of document containers.
     *
     * @param integer $parent Parent ID.
     * @param mixed $fields Fields as array and single field as string.
     * @return mixed Array of comment data on success and FALSE on failure.
     */
	function comment_byParent($parent, $fields='content')
	{
		if(is_string($fields))		$fields = array($fields);
		$fields = "jot." . join(', jot.' , $fields);
		
		$sql = "
			SELECT $fields , jfld.label AS fieldLabel, jfld.content AS fieldValue FROM " .$this->getFullTableName('jot_content'). " jot
			INNER JOIN " .$this->getFullTableName('site_content'). " sc ON sc.id=jot.uparent AND sc.parent=$parent
			LEFT JOIN " .$this->getFullTableName('jot_fields'). " jfld ON jot.id=jfld.id
		";
		return $this->comment_query($sql);
	}
	
	
	
	/**
     * Get a comment by SQL WHERE statement.
     *
     * @param string $where Valid SQL WHERE statement. ALL fields have 'jot' prefix (e.g. jot.id).
     * @return mixed Array of comment data on success and FALSE on failure.
     */
	function comment_byWhere($where, $fields='content')
	{
		if(is_string($fields))		$fields = array($fields);
		$fields = "jot." . join(', jot.' , $fields);
		
		$sql = "
			SELECT $fields , jfld.label AS fieldLabel, jfld.content AS fieldValue FROM " .$this->getFullTableName('jot_content'). " jot
			LEFT JOIN " .$this->getFullTableName('jot_fields'). " jfld ON jot.id=jfld.id WHERE $where
		";
		return $this->comment_query($sql);
	}
	
	
	
	/**
     * Get a comments count by document container.
     *
     * @param integer $doc Document ID.
     * @param string $tagid Tagid parameter.
     * @return mixed Comments count as integer on success and FALSE on failure.
     */
	function commentCount_byDoc($doc, $tagid='')
	{
		$tagid_where = ($tagid == '') ? '' : " AND jot.tagid='$tagid'";
		$sql = "SELECT COUNT(id) FROM " .$this->getFullTableName('jot_content'). " WHERE uparent=$doc $tagid_where";
		$count = $this->comment_query($sql);
		return $count['COUNT(id)'];
	}
	
	
	
	/**
     * Get a comments count by parent of document containers.
     *
     * @param integer $parent Parent ID.
     * @return mixed Comments count as integer on success and FALSE on failure.
     */
	function commentCount_byParent($parent)
	{
		$sql = "
			SELECT COUNT(jot.id) FROM " .$this->getFullTableName('jot_content'). " jot
			INNER JOIN " .$this->getFullTableName('site_content'). " sc ON sc.id=jot.uparent AND sc.parent=$parent
		";
		$count = $this->comment_query($sql);
		return $count['COUNT(jot.id)'];
	}
	
	
	
	/**
     * Get a comments count by SQL WHERE statement.
     *
     * @param string $where Valid SQL WHERE statement.
     * @return mixed Comments count as integer on success and FALSE on failure.
     */
	function commentCount_byWhere($where)
	{
		$sql = "SELECT COUNT(id) FROM " .$this->getFullTableName('jot_content'). " WHERE $where";
		$count = $this->comment_query($sql);
		return $count['COUNT(id)'];
	}
	
	
	
	/**
     * Get a subscriptions by document container.
     *
     * @param integer $doc Document ID.
     * @param string $tagid Tagid parameter.
     * @return mixed Array of subscriptions data on success and FALSE on failure.
     */
	function comment_subscriptions_byDoc($doc, $tagid='')
	{
		$tagid_where = ($tagid == '') ? '' : " AND subs.tagid='$tagid'";
		$sql = "
			SELECT subs.userid FROM " .$this->getFullTableName('jot_subscriptions'). " subs
			INNER JOIN " .$this->getFullTableName('jot_content'). " jot
			WHERE subs.id=jot.id AND jot.uparent=$doc $tagid_where
		";
		return $this->comment_query($sql);
	}
	
	
	
	/**
     * Get a subscriptions by parent of document containers.
     *
     * @param integer $parent Parent ID.
     * @return mixed Array of subscriptions data on success and FALSE on failure.
     */
	function comment_subscriptions_byParent($parent)
	{
		$sql = "
			SELECT subs.userid FROM " .$this->getFullTableName('jot_subscriptions'). " subs
			INNER JOIN " .$this->getFullTableName('jot_content'). " jot
			INNER JOIN " .$this->getFullTableName('site_content'). " sc ON sc.id=jot.uparent AND subs.id=jot.id AND sc.parent=$parent
		";
		return $this->comment_query($sql);
	}
	
	
	
	/**
     * Get a subscriptions by SQL WHERE statement.
     *
     * @param string $where Valid SQL WHERE statement. ALL fields have prefix.
	 * Prefixes are "subs" for `jot_subscriptions` and "jot" for `jot_content`.
     * @return mixed Array of subscriptions data on success and FALSE on failure.
     */
	function comment_subscriptions_byWhere($where)
	{
		$sql = "
			SELECT subs.userid FROM " .$this->getFullTableName('jot_subscriptions'). " subs
			INNER JOIN " .$this->getFullTableName('jot_content'). " jot
			ON subs.id=jot.id WHERE $where
		";
		return $this->comment_query($sql);
	}
	
	
	
	/**
     * Create a new comment.
     *
     * @param array $fields An associative array contains `jot_content` columns as KEY to insert in DB.
     * @param array $customFields A multidimentional array contains label and value of custom fields like below
	 * <code>
	 * 	$customFields = array(
	 * 		"name" => "testMan",
	 * 		"email" => "ex@m.ple",
	 * 		"age" => "999",
	 * 		"label1" => "value1",
	 * 		"label2" => "value2",
	 * 		"label3" => "value3"
	 * 	);
	 * </code>
     * @param array $subscriptions A multidimentional array contains `jot_subscriptions` fields like below
	 * @note `uparent` is ID of document that Jot call placed there.
	 * <code>
	 * 	$subscriptions = array(
	 * 		array("uparent" => "14" , "tagid" => "" , "userid" => "7"),
	 * 		array("label" => "14" , "tagid" => "" , "userid" => "8")
	 * 	);
	 * </code>
     * @return mixed New comment ID as Integer on success and FALSE on failure.
     */
	function comment_new($fields, $customFields=null, $subscriptions=null)
	{
		global $modx;
		
		$newFields = array();
		foreach($fields as $col => $val)
			if(!empty($val))
				$newFields[$col] = $this->stripModxTags($this->escape( htmlspecialchars($val) ));
		
		// Necessary fields
		$newFields["mode"] = array_key_exists("mode", $newFields) ? $newFields["mode"] : "0";
		$newFields["sechash"] = array_key_exists("sechash", $newFields) ? $newFields["sechash"] : md5("CakeMODx_" . time());
		$newFields["secip"] = array_key_exists("secip", $newFields) ? $newFields["secip"] : $this->getIP();
		$newFields["tagid"] = array_key_exists("tagid", $newFields) ? $newFields["tagid"] : "";
		
		$commentId = $this->db->insert($newFields, $this->getFullTableName('jot_content'));
		
		if($customFields)
		{
			$newCustomFields = array();
			foreach($customFields as $cFields => $cfValue)
			{
				if(!empty($cfValue))
				{
					$label = $this->escape($cFields);
					$content = trim( $this->escape( $this->stripModxTags($cfValue) ) );
					$this->db->query("INSERT INTO {$this->getFullTableName('jot_fields')} (id , label , content) VALUES('$commentId' , '$label' , '$content');");
				}
			}
		}
		
		if($subscriptions)
		{
			$newSubscriptions = array();
			foreach($subscriptions as $subs)
			{
				$subs["tagid"] = array_key_exists("tagid", $subs) ? $subs["tagid"] : "";
				$this->db->query("INSERT INTO {$this->getFullTableName('jot_subscriptions')} (uparent , tagid , userid) VALUES('{$subs['uparent']}' , '{$subs['tagid']}' , '{$subs['userid']}');");
			}
		}
		
		return $commentId;
	}
	
	
	
	/**
     * Move comments to another document.
     *
     * @param mixed $commentId Contains comment IDs. Single ID as integer and more as array. 
     * @param integer $toDoc Document ID that comment should moved there.
     * @param integer $tagId Tagid that comments in new document must be in (IMPORTANT!).
     * @return void.
     */
	function comment_move($commentId , $toDoc, $tagId=null)
	{
		$where = is_array($commentId) ? " `id` IN(" .join(',',$commentId). ")" : " `id`='$commentId'";
		$update = ($tagId) ? array("uparent" => "$toDoc" , "tagid" => "$tagId") : "uparent=$toDoc";
		$this->db->update($update, $this->getFullTableName("jot_content"), $where);
	}




/* ============================================================= */
/*                                                                                                                                           */
/* MISC methods                                                                                                                     */
/*                                                                                                                                           */
/* ============================================================= */
	
	/**
     * Get pagetitle by document ID.
     *
     * @desc Uses {@link CakeMODx::getDocField()}.
     * @param integer $id Document ID.
     * @return mixed Document pagetitle on success and FALSE on failure.
     */
	function doc_id2title($id)
	{
		return $this->getDocField($id, $fields='pagetitle');
	}
	
	
	
	/**
     * Get ID of TV by it's name.
     *
     * @param array $tvNames TV names as array.
     * @return array An associative array. Key is TV name and value is TV ID.
     */
	function TV_name2id($tvNames=array())
	{
		if(count($tvNames) == 0)	return array();
		$sql = "SELECT id, name FROM ".$this->getFullTableName('site_tmplvars')." WHERE name IN('". join("','",$tvNames)."')";
		$select = $this->db->query($sql);
		
		$TVs = array();
		while($row = $this->db->getRow($select))
			$TVs[$row['name']] = $row['id'];
		
		return $TVs;
	}
	
	
	
	/**
     * Get fullname by User ID.
     *
     * @desc Uses {@link CakeMODx::getUserField()}.
     * @param integer $id User ID.
     * @return mixed User's fullname on success and FALSE on failure.
     */
	function user_id2fullname($id)
	{
		return $this->getUserField($id, 'fullname', 'web_user_attributes');
	}
	
	
	
	/**
	 * Highlight a string in text without corrupting HTML tags.
	 *
	 * @author      Aidan Lister <aidan@php.net>
	 * @version     3.1.1
	 * @link        http://aidanlister.com/repos/v/function.str_highlight.php
	 * @param       string		$text           Haystack - The text to search
	 * @param       array|string		$needle         Needle - The string to highlight
	 * @param       bool		$options        Bitwise set of options
	 * @param       array		$highlight      Replacement string
	 * @param       integer		$STR_HIGHLIGHT_SIMPLE      Perform a simple text replace. This should be used when the string does not contain HTML
	 * @param       integer		$STR_HIGHLIGHT_WHOLEWD      Only match whole words in the string.
	 * @param       integer		$STR_HIGHLIGHT_CASESENS      Case sensitive matching.
	 * @param       integer		$STR_HIGHLIGHT_STRIPLINKS      Overwrite links if matched. This should be used when the replacement string is a link
	 * 
	 * @return      Text with needle highlighted
	 */
	function str_highlight($text, $needle, $options = null, $highlight = null, $STR_HIGHLIGHT_SIMPLE = null, $STR_HIGHLIGHT_WHOLEWD = null, $STR_HIGHLIGHT_CASESENS = null, $STR_HIGHLIGHT_STRIPLINKS = null)
	{
	    // Default highlighting
	    if ($highlight === null) {
	        $highlight = '<strong>\1</strong>';
	    }
		
	    // Select pattern to use
	    if ($options & $STR_HIGHLIGHT_SIMPLE) {
	        $pattern = '#(%s)#';
	        $sl_pattern = '#(%s)#';
	    } else {
	        $pattern = '#(?!<.*?)(%s)(?![^<>]*?>)#';
	        $sl_pattern = '#<a\s(?:.*?)>(%s)</a>#';
	    }
		
	    // Case sensitivity
	    if (!($options & $STR_HIGHLIGHT_CASESENS)) {
	        $pattern .= 'i';
	        $sl_pattern .= 'i';
	    }
		
		$needle = (array) $needle;
		foreach ($needle as $needle_s) {
	        $needle_s = preg_quote($needle_s);
			
	        // Escape needle with optional whole word check
	        if ($options & $STR_HIGHLIGHT_WHOLEWD) {
	            $needle_s = '\b' . $needle_s . '\b';
	        }
			
	        // Strip links
	        if ($options & $STR_HIGHLIGHT_STRIPLINKS) {
	            $sl_regex = sprintf($sl_pattern, $needle_s);
	            $text = preg_replace($sl_regex, '\1', $text);
	        }
			
	        $regex = sprintf($pattern, $needle_s);
			$text = preg_replace($regex, $highlight, $text);
		}
		
	    return $text;
	}
	
	
	
	/**
     * Escape strings to use in SQL commands.
     *
     * @desc It is DBAPI::escape with magic_quotes checking.
     * @param string $str String to escape.
     * @return string Escaped string.
     */
	function escape($str)
	{
		if(get_magic_quotes_gpc())
			$str = stripslashes($str);
		
		if(function_exists('mysql_real_escape_string') && $this->db->conn)
			return mysql_real_escape_string($str, $this->db->conn);
		else
			return mysql_escape_string($str);
   }
	
	
	
	/**
     * Escape array values for SQL commands.
     *
     * @desc Uses {@link CakeMODx::escape()}. Useful for escaping $_GET and $_POST vars.
     * @param array $array Array to escape.
     * @param boolean $escapeKeys Escape array KEYs.
     * @return array Escaped array.
     */
	function arr_escape($array, $escapeKeys=false)
	{
		$output = array();
		foreach($array as $key => $val)
		{
			$key = (is_string($key) && $escapeKeys) ? $this->escape($key) : $key;
			$val= is_string($val) ?  $this->escape($val) : $val;
			
			if(is_array($val))
				$val = $this->arr_escape($val, $escapeKeys);
			
			$output[$key] = $val;
		}
		return $output;
	}
	
	
	
	/**
     * Send email to some addresses.
     *
     * @desc Uses {@link CakeMODx::PHPMailer()} from PHPMailer class.
     * @param mixed $emails Email addresses as array or string for a single address.
	 * @param string $subject Email title.
     * @param string $body Message body.
     * @param boolean $isHTML Set TRUE for text/html contentType and FALSE to text/plain.
     * @param string $charset Email character set.
     * @param string $from From email address. Usually is set to $modx->config['mailsender'].
     * @param string $fromName From Name. Usually is set to $modx->config['site_name'].
     * @return boolean TRUE on success sending and FALSE on failure.
     */
	function sendMail($emails, $subject='', $body, $isHTML=false, $charset='utf-8', $from='', $fromName='')
	{
		$mail = $this->PHPMailer();
		$mail->Body = $body;
		$mail->isHTML($isHTML);
		$mail->CharSet = $charset;
		$mail->From = $from;
		$mail->FromName = $fromName;
		$mail->Subject = $subject;
		
		if(is_array($emails))
		{
			foreach($emails as $name => $email)
			{
				$name = (is_string($name)) ? $name : '';
				$mail->AddAddress($email, $name);
			}
		}
		elseif(is_string($emails))
			$mail->AddAddress($emails);
		
		return ($mail->Send() ? true : false);
	}
	
	
	
	/**
     * Get PHPMailer object.
     *
     * @desc Used in {@link CakeMODx::sendMail()}.
     * @return object PHPMailer object.
     */
	function PHPMailer()
	{
		global $modx;
		include_once $modx->config['base_path'] . "manager/includes/controls/class.phpmailer.php";
		return new PHPMailer;
	}
	
	
	
	/**
     * Strip MODx tags.
     *
     * @desc Codes have come from DocumentParser::striptags but it doesn't strip HTML tags!
     * @param string $str String to strip.
     * @param boolean $stripHTML Strip HTML tags.
     * @param string $allowableTags When $stripHTML is TRUE, this parameter will be second parameter of strip_tags() function.
     * @return string Stripped string.
     */
	function stripModxTags($str, $stripHTML=false, $allowableTags="")
	{
        $str = preg_replace('~\[\*(.*?)\*\]~', "", $str); //tv
        $str = preg_replace('~\[\[(.*?)\]\]~', "", $str); //snippet
        $str = preg_replace('~\[\!(.*?)\!\]~', "", $str); //snippet
        $str = preg_replace('~\[\((.*?)\)\]~', "", $str); //settings
        $str = preg_replace('~\[\+(.*?)\+\]~', "", $str); //placeholders
        $str = preg_replace('~{{(.*?)}}~', "", $str); //chunks
        return ($stripHTML ? strip_tags($str, $allowableTags) : $str);
	}
	
	
	
	/**
     * Get IP
     *
     * @return string IP.
     */
	function getIP()
	{
        return (getenv('HTTP_CLIENT_IP')) ? getenv('HTTP_CLIENT_IP') : getenv('REMOTE_ADDR');
	}




/* ============================================================= */
/*                                                                                                                                           */
/* BUILT-IN methods                                                                                                                */
/*                                                                                                                                           */
/* ============================================================= */
	
	/**
     * Exactly DocumentParser::getFullTableName to decrease code lines.
     *
     */
	function getFullTableName($tableName)
	{
		global $modx;
		return $modx->getFullTableName($tableName);
	}
	
	
	/**
     * Creat new web/doc group.
	 * Used in {@link CakeMODx::newDocgroup()} and {@link CakeMODx::newWebgroup()}.
     *
     */
	function newGroup($name, $groupType='document')
	{
		if($groupType != 'document' && $groupType != 'web')
			return false;
		
		if(! $id = $this->db->insert(array("name"=>$name), $this->getFullTableName($groupType . 'group_names')) )
			$this->logError("new{$groupType}group", "Couldn't create $name {$groupType}group in \"{$groupType}group_names\": ".$this->db->getLastError(), time());
		return $id;
	}
	
	
	/**
     * Rename a web/doc group.
	 * Used in {@link CakeMODx::renameDocgroup()} and {@link CakeMODx::renameWebgroup()}.
     *
     */
	function renameGroup($newname, $oldname, $groupType='document')
	{
		if($groupType != 'document' && $groupType != 'web')
			return false;
		
		if(! $id = $this->db->update(array("name"=>$newname), $this->getFullTableName($groupType . 'group_names'), "`name`='$oldname'") )
			$this->logError("rename{$groupType}group", "Couldn't rename $name {$groupType}group in \"{$groupType}group_names\": ".$this->db->getLastError(), time());
		return $id;
	}
	
	
	/**
     * Delete a web/doc group.
	 * Used in {@link CakeMODx::unsetDocgroup()} and {@link CakeMODx::unsetWebgroup()}.
     *
     */
	function unsetGroup($name, $groupType='document')
	{
		if($groupType != 'document' && $groupType != 'web')
			return false;
		
		if(! $id = $this->group_name2id($name, $groupType) )
			return false;
		
		if(! $res = $this->db->delete($this->getFullTableName($groupType . 'group_names'), "`id`='$id'") )
		{
			$this->logError("unset{$groupType}group", "Couldn't remove {$groupType}group from \"{$groupType}group_names\": ".$this->db->getLastError(), time());
			return false;
		}
		
		$groupField = ($groupType == 'document') ? 'document_group' : 'webgroup';
		if(! $res = $this->db->delete($this->getFullTableName($groupType . '_groups'), "`$groupField`='$id'") )
		{
			$this->logError("unset{$groupType}group", "Couldn't remove {$groupType}group members from \"{$groupType}group_names\": ".$this->db->getLastError(), time());
			return false;
		}
		
		if($groupType == 'document')
		{
			$tbl = $this->getFullTableName('document_groups');
			$sc = $this->getFullTableName('site_content');
			$members = $this->getGroupMembers($name);
			
			foreach($members as $member)
			{
				if($this->db->getValue($this->db->query("SELECT COUNT(id) FROM $tbl WHERE document = $member")) == 0)
				{
					if(! $this->db->update(array("private_memgroup" => 0), $sc, "id=$member") )
					{
						$this->logError("unset{$groupType}group", 'Couldn\'t set `site_content`.`private_memgroup` to 0 : ' . $this->db->getLastError(), time());
						$fail = true;
					}
				}
			}
		}
		
		return ($fail ? false : true);
	}
	
	
	/**
     * Join member(s) to a group.
	 * Used in {@link CakeMODx::joinDocgroup()} and {@link CakeMODx::joinWebgroup()}.
     *
     */
	function joinGroup($groupname, $groupType='document', $members)
	{
		if($groupType != 'document' && $groupType != 'web')		return false;
		if(! $groupId = $this->group_name2id($groupname, $groupType) )		return false;
		
		$groupField = ($groupType == 'document') ? 'document_group' : 'webgroup';
		$memberField = ($groupType == 'document') ? 'document' : 'webuser';
		$privateName = ($groupType == 'document') ? 'private_memgroup' : 'private_webgroup';
		
		$table = $this->getFullTableName($groupType . '_groups');
		$sc = $this->getFullTableName('site_content');
		
		
		foreach($members as $memId)
		{
			if($groupType == 'document')
			{
				if(! $this->db->update(array("privateweb" => 1) , $sc, "id=$memId") )
				{
					$this->logError("join{$groupType}group", "Couldn't set `site_content`.`privateweb` for $memId to $groupname $groupType: ".$this->db->getLastError(), time());
					continue;
				}
			}
			
			if(! $this->db->insert(array("$groupField"=>$groupId, "$memberField"=>$memId) , $table) )
			{
				$this->logError("join{$groupType}group", "Couldn't join member $memId to $groupname $groupType: ".$this->db->getLastError(), time());
				$fail = true;
			}
		}
		
		if($groupType == 'document')
		{
			if(! $this->db->update(array("$privateName"=>1) , $this->getFullTableName('documentgroup_names'), "name='$groupname'") )
			{
				$this->logError("join{$groupType}group", "Couldn't set  `{$groupType}group_names`.`$privateName` for $groupname $groupType: ".$this->db->getLastError(), time());
				$fail = true;
			}
		}
		
		return ($fail ? false : true);
	}
	
	
	/**
     * Get members of a web/doc group.
     * Used in {@link CakeMODx::getDocgroupMembers()} and {@link CakeMODx::getWebgroupMembers()}.
     *
     */
	function getGroupMembers($groupname, $groupType='document')
	{
		if($groupType != 'document' && $groupType != 'web')
			return false;
		
		if(! $id = $this->group_name2id($groupname, $groupType) )
			return false;
		
		$groupField = ($groupType == 'document') ? 'document_group' : 'webgroup';
		$getField = ($groupType == 'document') ? 'document' : 'webuser';
		$members = array();
		
		$select = $this->db->select($getField, $this->getFullTableName($groupType . '_groups'), "`$groupField`=$id");
		while($Row = $this->db->getRow($select, 'num'))
			$members[] = $Row[0];
		
		return $members;
	}
	
	
	/**
     * Get web/doc group ID from it's name.
     *
     */
	function group_name2id($name, $groupType='document')
	{
		if($groupType != 'document' && $groupType != 'web')
			return false;
		
		if(! $id = $this->db->getValue($this->db->select("id", $this->getFullTableName($groupType .'group_names'), "`name`='$name'")) )
			return false;
		return $id;
	}
	
	
	/**
     * Get documents in document groups that have related to a user.
	 * Used in {@link CakeMODx::getAssignedDocs()} and {@link CakeMODx::getForbiddenDocs()}.
     *
     */
	function getPrivateDocs($userId, $isMember)
	{
		$dg_tbl = $this->getFullTableName('document_groups');
		$access_tbl = $this->getFullTableName('webgroup_access');
		$wg_tbl = $this->getFullTableName('web_groups');
		$assigned = $unassigned = $docs = array();
		
		$select = $this->db->select("webgroup", $wg_tbl, "webuser = $userId");
		while($wgs = $this->db->getRow($select, 'num'))
			$assigned[] = $wgs[0];
		$IN = join(",", $assigned);
		
		if(count($IN) == 0)	return array();
		
		if($isMember == false)
		{
			$select = $this->db->select("webgroup", $wg_tbl, "webuser NOT IN ($IN)");
			while($wgs = $this->db->getRow($select, 'num'))
				$unassigned[] = $wgs[0];
			$IN = join(",", $unassigned);
		}
		
		if(count($IN) == 0)	return array();
		
		$select = $this->db->query("
			SELECT DISTINCT dg.document FROM $dg_tbl dg 
			INNER JOIN $access_tbl acc 
			WHERE dg.document_group=acc.documentgroup AND acc.webgroup IN ($IN)
		");
		while($Row = $this->db->getRow($select, 'num'))
			$docs[] = $Row[0];
		
		return $docs;
	}
	
	
	/**
     * Query action for Comment methods.
     *
     */
	function comment_query($sql)
	{
		$select = $this->db->query($sql);
		$counts = $this->db->getRecordCount($select);
		if($counts == 1)	return $this->db->getRow($select);
		if($counts == 0)	return "0";
		
		$comments = array();
		while($Row = $this->db->getRow($select))	$comments[] = $Row;
		return $comments;
	}
	
	
	/**
     * Log errors in /errorlogs.html
     *
     */
	function logError($method, $msg, $time)
	{
		$file =  dirname(__FILE__) . "\errorlog.html";
		$row = "
			<tr>" . "\n" . 
				"\t\t\t\t\t<td class=\"small\">$method</td>" . "\n" . 
				"\t\t\t\t\t<td>$msg</td>" . "\n" . 
				"\t\t\t\t\t<td class=\"small\">" .date("Y/m/d H:i", $time). "</td>" . 
			"\n" . "\t\t\t\t</tr>" . 
			"\n" . "<!-- ROWS -->
		";
		
		$newContent = str_replace("<!-- ROWS -->", $row, file_get_contents($file));
		$f = fopen($file, "w");
		fwrite($f, $newContent);
		fclose($f);
	}

}
?>