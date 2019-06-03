<?php

class InteractWithPersistentStorage
{
    private $PersistentStorageFunctions;
    private $UserID;
    private $Action_ID;
    
    public function __construct(PersistentStorageFunctions $PersistentStorageFunctions)
    {   
        $this->PersistentStorageFunctions = $PersistentStorageFunctions;
        $this->setUserID();
        $this->setAction_ID();
    }
    
    private function setUserID()
    {
        if(isset($_SESSION["LoggedInUser"])) {
            $LoggedInUser = $_SESSION["LoggedInUser"];
            $this->UserID = $LoggedInUser->UserDetails['ID'];
        } else {
            $this->UserID = 'SYSTEM';
        }
    }
    
    private function setAction_ID()
    {
        $this->Action_ID = $_SESSION["Action_ID"];
    }
    
    public function transaction_begin() {
        $this->PersistentStorageFunctions->transaction_begin();
    }
    public function transaction_rollback() {
        $this->PersistentStorageFunctions->transaction_rollback();
    }
    public function transaction_commit() {
        $this->PersistentStorageFunctions->transaction_commit();
    }
    
    public function row_add($TableName, $Columns, $Values)
    {
        $ID = $this->PersistentStorageFunctions->row_add($TableName.'IDs', '', '');
        
        if(sizeof($Columns) != sizeof($Values)) {
            throw new Exception('Number of Columns does not match number of Values.');
        }
        
        if(is_string($Columns)) {
            throw new Exception();
        }
        
        array_unshift($Columns, 'ID');
        array_unshift($Values, $ID);
        
        $size = sizeof($Columns);
        $Columns[$size] = 'Version_User_ID';
        $Values[$size] = $this->UserID;
        $size++;
        $Columns[$size] = 'Version_Action_ID';
        $Values[$size] = $this->Action_ID;
            
        $this->PersistentStorageFunctions->row_add($TableName, $Columns, $Values);
        
        return $ID;
    }
    
    // Pass the Row_ID as we can only edit one row at a time due to having to put the old version into the archives first and then updating the version number.
    public function row_edit($TableName, $Columns, $Values, $Row_ID)
    {
        /*
        This function will check if any of the newvalues of the row have changed compared to the current values in the persistant storage, and if so then it will move the current version of the row to the archives, update the version number and action id of the current version, and update any changed values.
        */
        
        if(sizeof($Columns) != sizeof($Values)) {
            throw new Exception('Number of Columns does not match number of Values.');   
        }
        
        $Updated = 'No';
        
        $QueryWhere['Groups'][0]['Conditions'][0]['FieldName'] = 'ID';
        $QueryWhere['Groups'][0]['Conditions'][0]['Comparison'] = '=';
        $QueryWhere['Groups'][0]['Conditions'][0]['Value'] = $Row_ID;
        
        $Results = $this->PersistentStorageFunctions->row_read($TableName, $Columns, $QueryWhere);
        if(sizeof($Results) != 1) {
            throw new Exception('Row ID does not exist.');
        }
        $OldValues = $Results[0];
        
        $FieldsEditedCounter = 0;
        
        for($i=0; $i<sizeof($Columns); $i++)
        {
            // Has to be !== rather than != otherwise is incorrect for a string containing numbers and string, e.g. shows '883' and '883s' as equal.
            if($OldValues[$Columns[$i]] !== $Values[$i])
            {  
                $Updated = 'Yes';
                $Changed[$i] = 'Yes';
                
                $FieldsEdited[$FieldsEditedCounter]['Name'] = $Columns[$i];
                $FieldsEdited[$FieldsEditedCounter]['PreviousValue'] = $OldValues[$Columns[$i]];
                $FieldsEditedCounter++;
            }
            else
            {
                $Changed[$i] = 'No';
            }
        }

        if($Updated == 'Yes')
        {
            $QueryColumns[0] = 'Version';
            $Result = $this->PersistentStorageFunctions->row_read($TableName, $QueryColumns, $QueryWhere)[0];
            $NewVersion = $Result['Version'] + 1;
            unset($QueryColumns);
            
            $this->PersistentStorageFunctions->row_copyFromNonArchivesToArchives($TableName, $Row_ID);
                
            $QueryColumns[0] = 'Version_User_ID'; $QueryColumns[1] = 'Version_Action_ID'; $QueryColumns[2] = 'Version';
            $QueryValues[0] = $this->UserID; $QueryValues[1] = $this->Action_ID; $QueryValues[2] = $NewVersion;
            $this->PersistentStorageFunctions->row_edit($TableName, $QueryColumns, $QueryValues, $QueryWhere);
            unset($QueryColumns);
            unset($QueryValues);

            $j = 0;
            for($i=0; $i<sizeof($Columns); $i++)
            {
                if($Changed[$i] == 'Yes')
                {
                    $QueryColumns[$j] = $Columns[$i];
                    $QueryValues[$j] = $Values[$i];
                    $j++;
                }
            }
            $this->PersistentStorageFunctions->row_edit($TableName, $QueryColumns, $QueryValues, $QueryWhere);
            
            return $FieldsEdited;
        }
    }

    // This function should be used under extreme caution.
    public function row_directEdit($TableName, $Columns, $Values, $Row_ID)
    {
        $Where['Groups'][0]['Conditions'][0]['FieldName'] = 'ID';
        $Where['Groups'][0]['Conditions'][0]['Comparison'] = '=';
        $Where['Groups'][0]['Conditions'][0]['Value'] = $Row_ID;
        $this->PersistentStorageFunctions->row_edit($TableName, $Columns, $Values, $Where);
        
    }
    
    // We pass the Row_ID as we can only delete one row at a time due to having to put the old version into the archives and marking it as deleted.
    public function row_delete($TableName, $Row_ID)
    {
        // This function will copy the row to archives, update the archives to have Deleted=yes and the Deleted_Action_ID, and then delete the row.
        
        $ArchiveRow_ID = $this->PersistentStorageFunctions->row_copyFromNonArchivesToArchives($TableName, $Row_ID);
        
        $Columns[0] = 'Deleted_User_ID';
        $Columns[1] = 'Deleted_Action_ID';
        $Columns[2] = 'Deleted';
        $Values[0] = $this->UserID;
        $Values[1] = $this->Action_ID;
        $Values[2] = 'Yes';
        $Where1['Groups'][0]['Conditions'][0]['FieldName'] = 'ArchiveRow_ID';
        $Where1['Groups'][0]['Conditions'][0]['Comparison'] = '=';
        $Where1['Groups'][0]['Conditions'][0]['Value'] = $ArchiveRow_ID;
        // Go directly to the PersistentStorageFunctions for row_edit here, rather than via this->row_edit, as I am editing an archive record.
        $this->PersistentStorageFunctions->row_edit($TableName.'Archives', $Columns, $Values, $Where1);
        
        $Where2['Groups'][0]['Conditions'][0]['FieldName'] = 'ID';
        $Where2['Groups'][0]['Conditions'][0]['Comparison'] = '=';
        $Where2['Groups'][0]['Conditions'][0]['Value'] = $Row_ID;
        $this->PersistentStorageFunctions->row_delete($TableName, $Where2);
    }
    
    public function row_restore($TableName, $Row_ID)
    {
        $Columns1[0] = 'Version';
        $Columns1[1] = 'ArchiveRow_ID';
        $Where1['Groups'][0]['Conditions'][0]['FieldName'] = 'ID';
        $Where1['Groups'][0]['Conditions'][0]['Comparison'] = '=';
        $Where1['Groups'][0]['Conditions'][0]['Value'] = $Row_ID;
        $Where1['Groups'][0]['Conditions'][1]['FieldName'] = 'Deleted';
        $Where1['Groups'][0]['Conditions'][1]['Comparison'] = '=';
        $Where1['Groups'][0]['Conditions'][1]['Value'] = 'Yes';
        $Result = $this->row_read($TableName.'Archives', $Columns1, $Where1)[0];
        $Version = $Result['Version'];
        $ArchiveRow_ID = $Result['ArchiveRow_ID'];
        $Version++;
        
        // Copy the deleted record back into the regular table so it's then exactly as it was before it was deleted.
        $this->PersistentStorageFunctions->row_copyFromArchivesToNonArchives($TableName, $ArchiveRow_ID);

        // Increment the version in the non-archives table, and update the version_userid, _action and _datetime on it accordingly.
        $Columns2[0] = 'Version_User_ID'; $Columns2[1] = 'Version_Action_ID'; $Columns2[2] = 'Version';
        $Values2[0] = $this->UserID; $Values2[1] = $this->Action_ID; $Values2[2] = $Version;
        $Where2['Groups'][0]['Conditions'][0]['FieldName'] = 'ID';
        $Where2['Groups'][0]['Conditions'][0]['Comparison'] = '=';
        $Where2['Groups'][0]['Conditions'][0]['Value'] = $Row_ID;
        $this->PersistentStorageFunctions->row_edit($TableName, $Columns2, $Values2, $Where2);
            
        // Update 'Deleted' from 'Yes' to 'Restored' on the deleted Archives record.
        $Columns3[0] = 'Deleted';
        $Values3[0] = 'Restored';
        $Where3['Groups'][0]['Conditions'][0]['FieldName'] = 'ArchiveRow_ID';
        $Where3['Groups'][0]['Conditions'][0]['Comparison'] = '=';
        $Where3['Groups'][0]['Conditions'][0]['Value'] = $ArchiveRow_ID;
        // Go directly to the PersistentStorageFunctions for row_edit here, rather than via this->row_edit, as I am editing an archive record.
        $this->PersistentStorageFunctions->row_edit($TableName.'Archives', $Columns3, $Values3, $Where3);
    }
    
    public function row_read($TableName, $Columns, $Where, $OrderBy = '', $OrderByAscOrDesc = '', $Limit = '', $Offset = '', $Join = '')
    {   
        return $this->PersistentStorageFunctions->row_read($TableName, $Columns, $Where, $OrderBy, $OrderByAscOrDesc, $Limit, $Offset, $Join);
    }
    
    public function row_getNumberOfRecords($TableName, $Where)
    {
        return $this->PersistentStorageFunctions->row_getNumberOfRows($TableName, $Where);
    }
    
    public function table_create($TableName)
    {
        $this->PersistentStorageFunctions->table_create($TableName, 'Regular');
        $this->PersistentStorageFunctions->table_create($TableName.'Archives', 'Archive');
        $this->PersistentStorageFunctions->table_create($TableName.'IDs', 'IDs');
    }
    
    public function table_delete($TableName)
    {
        $this->PersistentStorageFunctions->table_delete($TableName);
        $this->PersistentStorageFunctions->table_delete($TableName.'Archives');
    }
    
    public function table_updateName($OldTableName, $NewTableName)
    {
        $this->PersistentStorageFunctions->table_updateName($OldTableName, $NewTableName);
        $this->PersistentStorageFunctions->table_updateName($OldTableName.'Archives', $NewTableName.'Archives');
    }

    public function table_addColumn($TableName, $ColumnName, $StorageType, $ColumnLength)
    {
        $this->PersistentStorageFunctions->table_addColumn($TableName, $ColumnName, $StorageType, $ColumnLength);
        $this->PersistentStorageFunctions->table_addColumn($TableName.'Archives', $ColumnName, $StorageType, $ColumnLength);
    }
    
    public function table_addIndexToColumn($TableName, $ColumnName)
    {
        $this->PersistentStorageFunctions->table_addIndexToColumn($TableName, $ColumnName);
        $this->PersistentStorageFunctions->table_addIndexToColumn($TableName.'Archives', $ColumnName);
    }
    
    public function table_doesIndexExistOnColumn($TableName, $ColumnName)
    {
        return $this->PersistentStorageFunctions->table_doesIndexExistOnColumn($TableName, $ColumnName);
    }
    
    public function table_deleteColumn($TableName, $ColumnName)
    {
        $this->PersistentStorageFunctions->table_deleteColumn($TableName, $ColumnName);
        $this->PersistentStorageFunctions->table_deleteColumn($TableName.'Archives', $ColumnName);
    }
    
    public function table_updateColumnName($TableName, $OldColumnName, $NewColumnName, $StorageType, $ColumnLength)
    {
        $this->PersistentStorageFunctions->table_updateColumnName($TableName, $OldColumnName, $NewColumnName, $StorageType, $ColumnLength);
        $this->PersistentStorageFunctions->table_updateColumnName($TableName.'Archives', $OldColumnName, $NewColumnName, $StorageType, $ColumnLength);
    }

    public function table_updateColumnStorageTypeIfRequired($TableName, $ColumnName, $StorageType, $ColumnLength)
    {
        $StorageTypeAndColumnMaxLength = $this->PersistentStorageFunctions->table_getColumnStorageType($TableName, $ColumnName);
        
        $StorageTypeAndColumnMaxLength['StorageType'] = strtolower($StorageTypeAndColumnMaxLength['StorageType']);
        $StorageType = strtolower($StorageType);
        
        if($StorageTypeAndColumnMaxLength['StorageType'] != $StorageType || $StorageTypeAndColumnMaxLength['ColumnMaxLength'] != $ColumnLength)
        {
            $this->PersistentStorageFunctions->table_updateColumnStorageType($TableName, $ColumnName, $StorageType, $ColumnLength);
            $this->PersistentStorageFunctions->table_updateColumnStorageType($TableName.'Archives', $ColumnName, $StorageType, $ColumnLength);
        }
    }
    
    public function table_reorderColumnsIfRequired($TableName, $RequiredColumnOrder)
    {
        $RequiredColumnOrder[0] = 'ID';
        $RequiredColumnOrder[sizeof($RequiredColumnOrder)] = 'Date_Created';
        $RequiredColumnOrder[sizeof($RequiredColumnOrder)] = 'Version_Datetime';
        $RequiredColumnOrder[sizeof($RequiredColumnOrder)] = 'Version_User_ID';
        $RequiredColumnOrder[sizeof($RequiredColumnOrder)] = 'Version_Action_ID';
        $RequiredColumnOrder[sizeof($RequiredColumnOrder)] = 'Version';
        $this->table_reorderColumnsIfRequired_HelperFunction($TableName, $RequiredColumnOrder);
        
        $RequiredColumnOrder[sizeof($RequiredColumnOrder)] = 'Deleted_User_ID';
        $RequiredColumnOrder[sizeof($RequiredColumnOrder)] = 'Deleted_Action_ID';
        $RequiredColumnOrder[sizeof($RequiredColumnOrder)] = 'Deleted';
        $RequiredColumnOrder[sizeof($RequiredColumnOrder)] = 'ArchiveRow_ID';
        $RequiredColumnOrder[sizeof($RequiredColumnOrder)] = 'Archive_Date_Created';
        $this->table_reorderColumnsIfRequired_HelperFunction($TableName.'Archives', $RequiredColumnOrder);
    }
        
    private function table_reorderColumnsIfRequired_HelperFunction($TableName, $RequiredColumnOrder)
    {
        ksort($RequiredColumnOrder, 1); // Sort by key into numeric order.
        
        $NotYetDone = 0;
        while($NotYetDone == 0)
        {
            $CurrentColumnOrder = $this->PersistentStorageFunctions->table_getColumnOrder($TableName);
        
            if(sizeof($CurrentColumnOrder) != sizeof($RequiredColumnOrder))
            {
                throw new Exception('Size of CurrentColumnOrder does not equal size of RequiredColumnOrder for Table ' . $TableName);
            }
            
            $Size = sizeof($RequiredColumnOrder) - 1;
            
            for($i=0; $i<=$Size; $i++)
            {
                if($RequiredColumnOrder[$i] != $CurrentColumnOrder[$i])
                {
                    if($i == 0)
                    {
                        throw new Exception('First column should always be ID');
                    }
                    else
                    {
                        $StorageType = $this->PersistentStorageFunctions->table_getColumnStorageTypeAndTypeData($TableName, $RequiredColumnOrder[$i]);
                        $this->PersistentStorageFunctions->table_reorderColumn($TableName, $RequiredColumnOrder[$i], $StorageType, $RequiredColumnOrder[$i-1]);
                    }
                    break;
                }
                
                if($i == $Size)
                {
                    $NotYetDone = 1;   
                }
            }
        }
    }

    public function table_checkTableExists($TableName)
    {
        return $this->PersistentStorageFunctions->table_checkTableExists($TableName);
    }
    
    public function table_checkColumnExists($TableName, $ColumnName)
    {
        return $this->PersistentStorageFunctions->table_checkColumnExists($TableName, $ColumnName);
    }
    
    public function database_checkDatabaseExists($DatabaseName)
    {
        return $this->PersistentStorageFunctions->database_checkDatabaseExists($DatabaseName);
    }
}